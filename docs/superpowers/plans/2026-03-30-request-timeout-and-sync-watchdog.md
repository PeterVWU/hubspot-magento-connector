# Request Timeout & Sync Watchdog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent the sync service from hanging indefinitely by adding per-request AbortController timeouts, HubSpot 429 retry caps, and a sync-level watchdog timer.

**Architecture:** Three independent timeout layers: (1) `withTimeout` utility wraps every HTTP call with an AbortController that hard-kills requests after a configurable duration, (2) HubSpot 429 interceptor caps retries at 3, (3) scheduler races the full sync against a 10-minute timer. Each layer catches a different failure mode.

**Tech Stack:** Node.js 22, axios (AbortController signal support), existing ESM codebase.

---

## File Map

| File | Action | Responsibility |
|------|--------|---------------|
| `src/utils/timeout.js` | Create | Shared `withTimeout(fn, ms)` utility |
| `src/config/index.js` | Modify | Add `hubspot.timeout`, `hubspot.maxRetries`, `sync.timeoutMinutes` |
| `src/api/magento.js` | Modify | Use `withTimeout` on all requests, remove axios `timeout` option |
| `src/api/hubspot.js` | Modify | Use `withTimeout` on all requests, add retry cap to 429 interceptor |
| `src/sync/scheduler.js` | Modify | Extract `runSyncBody()`, wrap with `Promise.race` timeout |

---

### Task 1: Create `withTimeout` utility

**Files:**
- Create: `src/utils/timeout.js`

- [ ] **Step 1: Create the utility file**

```js
// src/utils/timeout.js

/**
 * Wraps an async function that accepts an AbortSignal with a hard timeout.
 * If the function doesn't resolve within `ms` milliseconds, the signal aborts
 * and the returned promise rejects with an error.
 *
 * @param {(signal: AbortSignal) => Promise<T>} fn - async function receiving an AbortSignal
 * @param {number} ms - timeout in milliseconds
 * @returns {Promise<T>}
 */
export function withTimeout(fn, ms) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);
  return fn(controller.signal).finally(() => clearTimeout(timer));
}
```

- [ ] **Step 2: Commit**

```bash
git add src/utils/timeout.js
git commit -m "feat: add withTimeout utility for AbortController-based request timeouts"
```

---

### Task 2: Add new config values

**Files:**
- Modify: `src/config/index.js`

- [ ] **Step 1: Add hubspot timeout, maxRetries, and sync timeout config**

Add `timeout` and `maxRetries` to the `hubspot` section, and `timeoutMinutes` to the `sync` section:

```js
  hubspot: {
    accessToken: process.env.HUBSPOT_ACCESS_TOKEN,
    batchSize: parseInt(process.env.HUBSPOT_BATCH_SIZE || '100', 10),
    timeout: parseInt(process.env.HUBSPOT_TIMEOUT_MS || '30000', 10),
    maxRetries: parseInt(process.env.HUBSPOT_MAX_RETRIES || '3', 10),
  },
```

```js
  sync: {
    intervalMinutes: parseInt(process.env.SYNC_INTERVAL_MINUTES || '5', 10),
    timeoutMinutes: parseInt(process.env.SYNC_TIMEOUT_MINUTES || '10', 10),
    startDate: process.env.SYNC_START_DATE
      ? new Date(process.env.SYNC_START_DATE)
      : null,
    excludedSalesrepIds: process.env.EXCLUDED_SALESREP_IDS
      ? process.env.EXCLUDED_SALESREP_IDS.split(',').map(id => id.trim()).filter(Boolean)
      : [],
  },
```

- [ ] **Step 2: Commit**

```bash
git add src/config/index.js
git commit -m "feat: add hubspot timeout, max retries, and sync timeout config"
```

---

### Task 3: Add AbortController timeout to Magento client

**Files:**
- Modify: `src/api/magento.js`

- [ ] **Step 1: Import `withTimeout` and remove the axios `timeout` option**

At the top of the file, add:

```js
import { withTimeout } from '../utils/timeout.js';
```

Change the axios client creation to remove `timeout`:

```js
const client = axios.create({
  baseURL: config.magento.baseUrl,
  headers: {
    Authorization: `Bearer ${config.magento.token}`,
    'Content-Type': 'application/json',
  },
});
```

- [ ] **Step 2: Wrap `client.get()` in `fetchAllPages` with `withTimeout`**

In the `fetchAllPages` function, change line 57 from:

```js
    const { data } = await client.get(url);
```

to:

```js
    const { data } = await withTimeout(
      (signal) => client.get(url, { signal }),
      config.magento.timeout,
    );
```

- [ ] **Step 3: Wrap `client.get()` in `getCustomerById` with `withTimeout`**

Change:

```js
  const { data } = await client.get(`/customers/${customerId}`);
```

to:

```js
  const { data } = await withTimeout(
    (signal) => client.get(`/customers/${customerId}`, { signal }),
    config.magento.timeout,
  );
```

- [ ] **Step 4: Wrap `client.get()` in `getOrderById` with `withTimeout`**

Change:

```js
  const { data } = await client.get(`/orders/${orderId}`);
```

to:

```js
  const { data } = await withTimeout(
    (signal) => client.get(`/orders/${orderId}`, { signal }),
    config.magento.timeout,
  );
```

- [ ] **Step 5: Commit**

```bash
git add src/api/magento.js
git commit -m "feat: use AbortController timeout for all Magento API requests"
```

---

### Task 4: Add AbortController timeout and retry cap to HubSpot client

**Files:**
- Modify: `src/api/hubspot.js`

- [ ] **Step 1: Import `withTimeout` and remove the axios `timeout` option**

At the top of the file, add:

```js
import { withTimeout } from '../utils/timeout.js';
```

Change the axios client creation to remove `timeout`:

```js
const client = axios.create({
  baseURL: 'https://api.hubapi.com',
  headers: {
    Authorization: `Bearer ${config.hubspot.accessToken}`,
    'Content-Type': 'application/json',
  },
});
```

- [ ] **Step 2: Add retry cap to the 429 interceptor**

Replace the existing interceptor (lines 41-61) with:

```js
client.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const url = error.config?.url;

    if (status === 429) {
      const retries = (error.config._retryCount || 0) + 1;
      if (retries > config.hubspot.maxRetries) {
        logger.error('HubSpot rate limit retries exhausted', { url, retries });
        throw error;
      }
      error.config._retryCount = retries;
      const retryAfter = parseInt(error.response.headers['retry-after'] || '10', 10);
      logger.warn('HubSpot rate limited, retrying', { url, retryAfter, attempt: retries });
      await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      return client.request(error.config);
    }

    logger.error('HubSpot API error', {
      status,
      url,
      data: JSON.stringify(error.response?.data),
    });
    throw error;
  },
);
```

- [ ] **Step 3: Create a helper function for wrapping requests**

Add this helper after the interceptor, before the search operations section:

```js
function hsRequest(fn) {
  return withTimeout(fn, config.hubspot.timeout);
}
```

- [ ] **Step 4: Wrap all exported functions with `hsRequest`**

Replace every axios call in the exported functions. Each `client.post(...)`, `client.patch(...)`, and `client.get(...)` call needs to be wrapped. Here is the complete list:

**searchContacts:**
```js
export async function searchContacts(email) {
  await rateLimitDelay(true);
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/contacts/search', {
    filterGroups: [{
      filters: [{ propertyName: 'email', operator: 'EQ', value: email }],
    }],
    properties: ['email', 'firstname', 'lastname'],
    limit: 1,
  }, { signal }));
  return data.results[0] || null;
}
```

**searchProductBySku:**
```js
export async function searchProductBySku(sku) {
  await rateLimitDelay(true);
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/products/search', {
    filterGroups: [{
      filters: [{ propertyName: 'hs_sku', operator: 'EQ', value: sku }],
    }],
    properties: ['name', 'hs_sku', 'price'],
    limit: 1,
  }, { signal }));
  return data.results[0] || null;
}
```

**searchDealByOrderNumber:**
```js
export async function searchDealByOrderNumber(orderNumber) {
  await rateLimitDelay(true);
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/deals/search', {
    filterGroups: [{
      filters: [{ propertyName: 'order_number', operator: 'EQ', value: orderNumber }],
    }],
    properties: ['dealname', 'order_number', 'dealstage', 'amount'],
    limit: 1,
  }, { signal }));
  return data.results[0] || null;
}
```

**createContact:**
```js
export async function createContact(properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/contacts', { properties }, { signal }));
  return data;
}
```

**updateContact:**
```js
export async function updateContact(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.patch(`/crm/v3/objects/contacts/${hubspotId}`, { properties }, { signal }));
  return data;
}
```

**createProduct:**
```js
export async function createProduct(properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/products', { properties }, { signal }));
  return data;
}
```

**updateProduct:**
```js
export async function updateProduct(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.patch(`/crm/v3/objects/products/${hubspotId}`, { properties }, { signal }));
  return data;
}
```

**createDeal:**
```js
export async function createDeal(properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/deals', { properties }, { signal }));
  return data;
}
```

**updateDeal:**
```js
export async function updateDeal(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.patch(`/crm/v3/objects/deals/${hubspotId}`, { properties }, { signal }));
  return data;
}
```

**getDealPipelines:**
```js
export async function getDealPipelines() {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.get('/crm/v3/pipelines/deals', { signal }));
  return data.results || [];
}
```

**createContactProperty:**
```js
export async function createContactProperty(name, label, type = 'string', fieldType = 'text') {
  await rateLimitDelay();
  try {
    const { data } = await hsRequest((signal) => client.post('/crm/v3/properties/contacts', {
      name, label, type, fieldType, groupName: 'contactinformation',
    }, { signal }));
    return data;
  } catch (err) {
    if (err.response?.status === 409) {
      logger.debug(`Contact property "${name}" already exists`);
      return null;
    }
    throw err;
  }
}
```

**createDealProperty:**
```js
export async function createDealProperty(name, label, type = 'string', fieldType = 'text') {
  await rateLimitDelay();
  try {
    const { data } = await hsRequest((signal) => client.post('/crm/v3/properties/deals', {
      name, label, type, fieldType, groupName: 'dealinformation',
    }, { signal }));
    return data;
  } catch (err) {
    if (err.response?.status === 409) {
      logger.debug(`Deal property "${name}" already exists`);
      return null;
    }
    throw err;
  }
}
```

**batchCreateLineItems** (inside the for loop):
```js
    const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/line_items/batch/create', {
      inputs: batch,
    }, { signal }));
```

**batchUpdateLineItems** (inside the for loop):
```js
    const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/line_items/batch/update', {
      inputs: batch,
    }, { signal }));
```

**batchCreateAssociations:**
```js
export async function batchCreateAssociations(fromType, toType, inputs) {
  if (!inputs.length) return;
  await rateLimitDelay();
  await hsRequest((signal) => client.post(`/crm/v4/associations/${fromType}/${toType}/batch/create`, {
    inputs,
  }, { signal }));
}
```

- [ ] **Step 5: Commit**

```bash
git add src/api/hubspot.js
git commit -m "feat: add AbortController timeout and retry cap to all HubSpot API requests"
```

---

### Task 5: Add sync-level timeout to scheduler

**Files:**
- Modify: `src/sync/scheduler.js`

- [ ] **Step 1: Extract `runSyncBody` and add `Promise.race` timeout**

Replace the entire file with:

```js
import cron from 'node-cron';
import { v4 as uuidv4 } from 'uuid';
import { config } from '../config/index.js';
import { syncProducts } from './products.js';
import { syncCustomers } from './customers.js';
import { syncOrders } from './orders.js';
import { processRetryQueue } from './retry-queue.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

let syncInProgress = false;

async function runSyncBody(runId, syncStart) {
  // 1. Sync products first (orders reference them)
  const productSince = await db.getLastSyncedAt('product');
  const productResult = await syncProducts(productSince, runId);

  // 2. Sync customers (orders reference them)
  const customerSince = await db.getLastSyncedAt('customer');
  const customerResult = await syncCustomers(customerSince, runId);

  // 3. Sync orders last
  const orderSince = await db.getLastSyncedAt('order');
  const orderResult = await syncOrders(orderSince, runId);

  // 4. Update sync timestamps only for entity types with zero failures
  // Use lastUpdatedAt (high-water mark from processed records) when available,
  // so that maxRecords-limited runs advance incrementally rather than jumping to now
  if (productResult.failed === 0) {
    await db.updateLastSyncedAt('product', productResult.lastUpdatedAt || syncStart);
  } else {
    logger.warn('Skipping product timestamp update due to failures', { failed: productResult.failed });
  }
  if (customerResult.failed === 0) {
    await db.updateLastSyncedAt('customer', customerResult.lastUpdatedAt || syncStart);
  } else {
    logger.warn('Skipping customer timestamp update due to failures', { failed: customerResult.failed });
  }
  if (orderResult.failed === 0) {
    await db.updateLastSyncedAt('order', orderResult.lastUpdatedAt || syncStart);
  } else {
    logger.warn('Skipping order timestamp update due to failures', { failed: orderResult.failed });
  }

  // 5. Process retry queue
  await processRetryQueue(runId);

  return { productResult, customerResult, orderResult };
}

export async function runFullSync() {
  if (syncInProgress) {
    logger.warn('Sync already in progress, skipping');
    return;
  }

  syncInProgress = true;
  const runId = uuidv4();
  const syncStart = new Date();
  const timeoutMs = config.sync.timeoutMinutes * 60 * 1000;

  logger.info('=== Starting full sync ===', { runId });

  try {
    const results = await Promise.race([
      runSyncBody(runId, syncStart),
      new Promise((_, reject) =>
        setTimeout(() => reject(new Error(`Sync timed out after ${config.sync.timeoutMinutes} minutes`)), timeoutMs)
      ),
    ]);

    logger.info('=== Full sync complete ===', {
      runId,
      products: results.productResult,
      customers: results.customerResult,
      orders: results.orderResult,
      duration: `${((Date.now() - syncStart.getTime()) / 1000).toFixed(1)}s`,
    });
  } catch (err) {
    logger.error('Full sync failed', { runId, error: err.message, stack: err.stack });
    await db.logSync(runId, null, 'error', `Sync failed: ${err.message}`);
  } finally {
    syncInProgress = false;
  }
}

export function startScheduler() {
  const minutes = config.sync.intervalMinutes;
  const cronExpr = `*/${minutes} * * * *`;

  logger.info(`Scheduling sync every ${minutes} minutes (${cronExpr})`);

  cron.schedule(cronExpr, () => {
    runFullSync().catch(err => {
      logger.error('Scheduled sync error', { error: err.message });
    });
  });
}
```

- [ ] **Step 2: Commit**

```bash
git add src/sync/scheduler.js
git commit -m "feat: add 10-minute sync-level timeout watchdog"
```

---

### Task 6: Deploy and verify

- [ ] **Step 1: Push all commits**

```bash
git push
```

- [ ] **Step 2: On server, pull and rebuild**

```bash
cd /opt/hubspot-magento-connector
git pull && docker compose build app && docker compose up -d app
```

- [ ] **Step 3: Verify startup logs**

```bash
docker compose logs app --tail 20
```

Expected: Normal startup with sync running. No errors about missing imports or config.

- [ ] **Step 4: Monitor a few sync cycles**

```bash
docker compose logs app -f --tail 10
```

Expected: Sync cycles completing normally with durations under 3 minutes. If a request times out, it should log an error and continue to the next record rather than hanging.
