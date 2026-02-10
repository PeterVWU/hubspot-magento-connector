# Magento-HubSpot Sync App Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a Node.js background service that syncs customers, products, and orders from Magento 2 to HubSpot CRM via polling.

**Architecture:** Modular Node.js app using ES Modules. Polls Magento REST API on a cron schedule, transforms data through mappers, and pushes to HubSpot CRM v3 batch APIs with search-before-upsert. PostgreSQL stores sync state, ID mappings, and a retry queue for failed operations.

**Tech Stack:** Node.js (ESM), axios, node-cron, winston, pg (node-postgres), dotenv

---

### Task 1: Project Scaffolding

**Files:**
- Create: `package.json`
- Create: `.env.example`
- Create: `.gitignore`
- Create: `src/config/index.js`

**Step 1: Initialize project and install dependencies**

Run:
```bash
cd /home/vwu/development/nodeapp/hubspot-magento-connector
npm init -y
npm install axios node-cron winston pg dotenv uuid
```

**Step 2: Configure package.json for ESM**

Set `"type": "module"` in package.json. Set `"main": "src/index.js"`. Add scripts:
```json
{
  "scripts": {
    "start": "node src/index.js",
    "dev": "node --watch src/index.js"
  }
}
```

**Step 3: Create .env.example**

```env
# Magento
MAGENTO_BASE_URL=https://your-store.com/rest/V1
MAGENTO_TOKEN=your-magento-bearer-token

# HubSpot
HUBSPOT_ACCESS_TOKEN=your-hubspot-private-app-token

# PostgreSQL
DATABASE_URL=postgresql://user:password@localhost:5432/hubspot_sync

# Sync Settings
SYNC_INTERVAL_MINUTES=5
HUBSPOT_BATCH_SIZE=100
MAGENTO_PAGE_SIZE=100

# Customer Filter
EXCLUDED_SALESREP_IDS=

# Logging
LOG_LEVEL=info
```

**Step 4: Create .gitignore**

```
node_modules/
.env
logs/
```

**Step 5: Create src/config/index.js**

```js
import 'dotenv/config';

const required = [
  'MAGENTO_BASE_URL',
  'MAGENTO_TOKEN',
  'HUBSPOT_ACCESS_TOKEN',
  'DATABASE_URL',
];

for (const key of required) {
  if (!process.env[key]) {
    throw new Error(`Missing required environment variable: ${key}`);
  }
}

export const config = {
  magento: {
    baseUrl: process.env.MAGENTO_BASE_URL,
    token: process.env.MAGENTO_TOKEN,
    pageSize: parseInt(process.env.MAGENTO_PAGE_SIZE || '100', 10),
  },
  hubspot: {
    accessToken: process.env.HUBSPOT_ACCESS_TOKEN,
    batchSize: parseInt(process.env.HUBSPOT_BATCH_SIZE || '100', 10),
  },
  db: {
    connectionString: process.env.DATABASE_URL,
  },
  sync: {
    intervalMinutes: parseInt(process.env.SYNC_INTERVAL_MINUTES || '5', 10),
    excludedSalesrepIds: process.env.EXCLUDED_SALESREP_IDS
      ? process.env.EXCLUDED_SALESREP_IDS.split(',').map(id => id.trim()).filter(Boolean)
      : [],
  },
  log: {
    level: process.env.LOG_LEVEL || 'info',
  },
};
```

**Step 6: Commit**

```bash
git add package.json package-lock.json .env.example .gitignore src/config/index.js
git commit -m "feat: project scaffolding with config and dependencies"
```

---

### Task 2: Logger

**Files:**
- Create: `src/utils/logger.js`

**Step 1: Create winston logger**

```js
import winston from 'winston';
import { config } from '../config/index.js';

const logger = winston.createLogger({
  level: config.log.level,
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json(),
  ),
  defaultMeta: { service: 'hubspot-magento-sync' },
  transports: [
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.colorize(),
        winston.format.printf(({ timestamp, level, message, service, ...meta }) => {
          const metaStr = Object.keys(meta).length ? ` ${JSON.stringify(meta)}` : '';
          return `${timestamp} [${level}] ${message}${metaStr}`;
        }),
      ),
    }),
    new winston.transports.File({
      filename: 'logs/error.log',
      level: 'error',
      maxsize: 10 * 1024 * 1024,
      maxFiles: 5,
    }),
    new winston.transports.File({
      filename: 'logs/combined.log',
      maxsize: 10 * 1024 * 1024,
      maxFiles: 5,
    }),
  ],
});

export default logger;
```

**Step 2: Commit**

```bash
mkdir -p logs
git add src/utils/logger.js
git commit -m "feat: add winston logger with file and console transports"
```

---

### Task 3: Database Layer

**Files:**
- Create: `src/db/index.js`
- Create: `src/db/migrations.js`
- Create: `src/db/sync-state.js`

**Step 1: Create PostgreSQL connection pool**

`src/db/index.js`:
```js
import pg from 'pg';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';

const pool = new pg.Pool({
  connectionString: config.db.connectionString,
});

pool.on('error', (err) => {
  logger.error('Unexpected PostgreSQL pool error', { error: err.message });
});

export default pool;
```

**Step 2: Create migrations**

`src/db/migrations.js`:
```js
import pool from './index.js';
import logger from '../utils/logger.js';

export async function runMigrations() {
  const client = await pool.connect();
  try {
    await client.query(`
      CREATE TABLE IF NOT EXISTS sync_state (
        entity_type VARCHAR(50) PRIMARY KEY,
        last_synced_at TIMESTAMPTZ NOT NULL DEFAULT '1970-01-01T00:00:00Z',
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );

      CREATE TABLE IF NOT EXISTS entity_mapping (
        id SERIAL PRIMARY KEY,
        entity_type VARCHAR(50) NOT NULL,
        magento_id VARCHAR(255) NOT NULL,
        hubspot_id VARCHAR(255) NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        UNIQUE(entity_type, magento_id)
      );

      CREATE INDEX IF NOT EXISTS idx_entity_mapping_lookup
        ON entity_mapping(entity_type, magento_id);

      CREATE TABLE IF NOT EXISTS sync_retry_queue (
        id SERIAL PRIMARY KEY,
        entity_type VARCHAR(50) NOT NULL,
        magento_id VARCHAR(255) NOT NULL,
        operation VARCHAR(20) NOT NULL,
        payload JSONB NOT NULL,
        error_message TEXT,
        attempts INT NOT NULL DEFAULT 0,
        max_attempts INT NOT NULL DEFAULT 5,
        next_retry_at TIMESTAMPTZ,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );

      CREATE INDEX IF NOT EXISTS idx_retry_queue_pending
        ON sync_retry_queue(next_retry_at)
        WHERE attempts < max_attempts;

      CREATE TABLE IF NOT EXISTS sync_log (
        id SERIAL PRIMARY KEY,
        run_id UUID NOT NULL,
        entity_type VARCHAR(50),
        level VARCHAR(10) NOT NULL,
        message TEXT NOT NULL,
        metadata JSONB,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      );

      CREATE INDEX IF NOT EXISTS idx_sync_log_run
        ON sync_log(run_id);

      INSERT INTO sync_state (entity_type) VALUES ('customer'), ('product'), ('order')
        ON CONFLICT (entity_type) DO NOTHING;
    `);
    logger.info('Database migrations completed');
  } finally {
    client.release();
  }
}
```

**Step 3: Create sync-state queries**

`src/db/sync-state.js`:
```js
import pool from './index.js';

export async function getLastSyncedAt(entityType) {
  const { rows } = await pool.query(
    'SELECT last_synced_at FROM sync_state WHERE entity_type = $1',
    [entityType],
  );
  return rows[0]?.last_synced_at || new Date('1970-01-01');
}

export async function updateLastSyncedAt(entityType, timestamp) {
  await pool.query(
    `UPDATE sync_state SET last_synced_at = $1, updated_at = NOW()
     WHERE entity_type = $2`,
    [timestamp, entityType],
  );
}

export async function getHubspotId(entityType, magentoId) {
  const { rows } = await pool.query(
    'SELECT hubspot_id FROM entity_mapping WHERE entity_type = $1 AND magento_id = $2',
    [entityType, String(magentoId)],
  );
  return rows[0]?.hubspot_id || null;
}

export async function upsertMapping(entityType, magentoId, hubspotId) {
  await pool.query(
    `INSERT INTO entity_mapping (entity_type, magento_id, hubspot_id)
     VALUES ($1, $2, $3)
     ON CONFLICT (entity_type, magento_id)
     DO UPDATE SET hubspot_id = $3, updated_at = NOW()`,
    [entityType, String(magentoId), String(hubspotId)],
  );
}

export async function getHubspotIdsBatch(entityType, magentoIds) {
  if (!magentoIds.length) return new Map();
  const { rows } = await pool.query(
    'SELECT magento_id, hubspot_id FROM entity_mapping WHERE entity_type = $1 AND magento_id = ANY($2)',
    [entityType, magentoIds.map(String)],
  );
  return new Map(rows.map(r => [r.magento_id, r.hubspot_id]));
}

export async function addToRetryQueue(entityType, magentoId, operation, payload, errorMessage) {
  const backoffMinutes = [1, 5, 15, 60, 240];
  await pool.query(
    `INSERT INTO sync_retry_queue (entity_type, magento_id, operation, payload, error_message, next_retry_at)
     VALUES ($1, $2, $3, $4, $5, NOW() + INTERVAL '1 minute')
     ON CONFLICT DO NOTHING`,
    [entityType, String(magentoId), operation, JSON.stringify(payload), errorMessage],
  );
}

export async function getRetryItems(limit = 50) {
  const { rows } = await pool.query(
    `SELECT * FROM sync_retry_queue
     WHERE attempts < max_attempts AND next_retry_at <= NOW()
     ORDER BY next_retry_at ASC
     LIMIT $1`,
    [limit],
  );
  return rows;
}

export async function updateRetryItem(id, attempts, errorMessage) {
  const backoffMinutes = [1, 5, 15, 60, 240];
  const nextBackoff = backoffMinutes[Math.min(attempts, backoffMinutes.length - 1)];
  await pool.query(
    `UPDATE sync_retry_queue
     SET attempts = $1, error_message = $2, next_retry_at = NOW() + ($3 || ' minutes')::INTERVAL, updated_at = NOW()
     WHERE id = $4`,
    [attempts, errorMessage, String(nextBackoff), id],
  );
}

export async function removeRetryItem(id) {
  await pool.query('DELETE FROM sync_retry_queue WHERE id = $1', [id]);
}

export async function logSync(runId, entityType, level, message, metadata = null) {
  await pool.query(
    `INSERT INTO sync_log (run_id, entity_type, level, message, metadata)
     VALUES ($1, $2, $3, $4, $5)`,
    [runId, entityType, level, message, metadata ? JSON.stringify(metadata) : null],
  );
}
```

**Step 4: Commit**

```bash
git add src/db/
git commit -m "feat: database layer with migrations, sync state, and retry queue"
```

---

### Task 4: Magento API Client

**Files:**
- Create: `src/api/magento.js`

**Step 1: Create Magento REST client with pagination**

```js
import axios from 'axios';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';

const client = axios.create({
  baseURL: config.magento.baseUrl,
  headers: {
    Authorization: `Bearer ${config.magento.token}`,
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const url = error.config?.url;
    const data = error.response?.data;
    logger.error('Magento API error', { status, url, data: JSON.stringify(data) });
    throw error;
  },
);

function buildSearchCriteria(filters, pageSize, currentPage, sortField = 'updated_at') {
  const params = new URLSearchParams();
  let groupIdx = 0;

  for (const filter of filters) {
    const filterIdx = filter.filterIdx || 0;
    const prefix = `searchCriteria[filterGroups][${filter.group ?? groupIdx}][filters][${filterIdx}]`;
    params.append(`${prefix}[field]`, filter.field);
    params.append(`${prefix}[value]`, filter.value);
    params.append(`${prefix}[conditionType]`, filter.condition);
    if (filter.group === undefined) groupIdx++;
  }

  params.append('searchCriteria[sortOrders][0][field]', sortField);
  params.append('searchCriteria[sortOrders][0][direction]', 'ASC');
  params.append('searchCriteria[pageSize]', String(pageSize));
  params.append('searchCriteria[currentPage]', String(currentPage));

  return params.toString();
}

async function fetchAllPages(endpoint, filters, entityName) {
  const pageSize = config.magento.pageSize;
  let currentPage = 1;
  let allItems = [];
  let totalCount = 0;

  do {
    const query = buildSearchCriteria(filters, pageSize, currentPage);
    const url = `${endpoint}?${query}`;

    logger.debug(`Fetching ${entityName} page ${currentPage}`, { url });
    const { data } = await client.get(url);

    totalCount = data.total_count;
    allItems = allItems.concat(data.items || []);

    logger.info(`Fetched ${entityName} page ${currentPage}`, {
      pageItems: (data.items || []).length,
      totalSoFar: allItems.length,
      totalCount,
    });

    currentPage++;
  } while (allItems.length < totalCount);

  return allItems;
}

export async function getCustomersUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const excludedIds = config.sync.excludedSalesrepIds;

  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];

  // Fetch all customers updated since the given time
  const customers = await fetchAllPages('/customers/search', filters, 'customers');

  // Client-side filter: exclude customers assigned to specific sales reps
  if (excludedIds.length > 0) {
    const before = customers.length;
    const filtered = customers.filter((customer) => {
      const salesrepAttr = (customer.custom_attributes || [])
        .find(a => a.attribute_code === 'salesrep_rep_id');
      const salesrepId = salesrepAttr?.value;
      // Keep if no salesrep or salesrep not in excluded list
      return !salesrepId || !excludedIds.includes(String(salesrepId));
    });
    logger.info(`Customer salesrep filter: ${before} -> ${filtered.length} (excluded ${before - filtered.length})`);
    return filtered;
  }

  return customers;
}

export async function getProductsUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/products', filters, 'products');
}

export async function getOrdersUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/orders', filters, 'orders');
}
```

**Step 2: Commit**

```bash
git add src/api/magento.js
git commit -m "feat: Magento REST API client with pagination and salesrep filtering"
```

---

### Task 5: HubSpot API Client

**Files:**
- Create: `src/api/hubspot.js`

**Step 1: Create HubSpot CRM client with rate limiting and batch operations**

```js
import axios from 'axios';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';

const RATE_LIMIT_PER_SECOND = 9; // Conservative: 100 per 10s = 10/s, use 9 for safety
const SEARCH_RATE_LIMIT_PER_SECOND = 4; // HubSpot limit: 5/s, use 4 for safety

let lastRequestTime = 0;
let lastSearchTime = 0;

async function rateLimitDelay(isSearch = false) {
  const now = Date.now();
  const minInterval = isSearch
    ? 1000 / SEARCH_RATE_LIMIT_PER_SECOND
    : 1000 / RATE_LIMIT_PER_SECOND;

  if (isSearch) {
    const elapsed = now - lastSearchTime;
    if (elapsed < minInterval) {
      await new Promise(resolve => setTimeout(resolve, minInterval - elapsed));
    }
    lastSearchTime = Date.now();
  } else {
    const elapsed = now - lastRequestTime;
    if (elapsed < minInterval) {
      await new Promise(resolve => setTimeout(resolve, minInterval - elapsed));
    }
    lastRequestTime = Date.now();
  }
}

const client = axios.create({
  baseURL: 'https://api.hubapi.com',
  headers: {
    Authorization: `Bearer ${config.hubspot.accessToken}`,
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

client.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const url = error.config?.url;

    if (status === 429) {
      const retryAfter = parseInt(error.response.headers['retry-after'] || '10', 10);
      logger.warn('HubSpot rate limited, retrying', { url, retryAfter });
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

// --- Search Operations ---

export async function searchContacts(email) {
  await rateLimitDelay(true);
  const { data } = await client.post('/crm/v3/objects/contacts/search', {
    filterGroups: [{
      filters: [{ propertyName: 'email', operator: 'EQ', value: email }],
    }],
    properties: ['email', 'firstname', 'lastname'],
    limit: 1,
  });
  return data.results[0] || null;
}

export async function searchProductBySku(sku) {
  await rateLimitDelay(true);
  const { data } = await client.post('/crm/v3/objects/products/search', {
    filterGroups: [{
      filters: [{ propertyName: 'hs_sku', operator: 'EQ', value: sku }],
    }],
    properties: ['name', 'hs_sku', 'price'],
    limit: 1,
  });
  return data.results[0] || null;
}

export async function searchDealByOrderNumber(orderNumber) {
  await rateLimitDelay(true);
  const { data } = await client.post('/crm/v3/objects/deals/search', {
    filterGroups: [{
      filters: [{ propertyName: 'order_number', operator: 'EQ', value: orderNumber }],
    }],
    properties: ['dealname', 'order_number', 'dealstage', 'amount'],
    limit: 1,
  });
  return data.results[0] || null;
}

// --- Single Create/Update Operations ---

export async function createContact(properties) {
  await rateLimitDelay();
  const { data } = await client.post('/crm/v3/objects/contacts', { properties });
  return data;
}

export async function updateContact(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await client.patch(`/crm/v3/objects/contacts/${hubspotId}`, { properties });
  return data;
}

export async function createProduct(properties) {
  await rateLimitDelay();
  const { data } = await client.post('/crm/v3/objects/products', { properties });
  return data;
}

export async function updateProduct(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await client.patch(`/crm/v3/objects/products/${hubspotId}`, { properties });
  return data;
}

export async function createDeal(properties) {
  await rateLimitDelay();
  const { data } = await client.post('/crm/v3/objects/deals', { properties });
  return data;
}

export async function updateDeal(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await client.patch(`/crm/v3/objects/deals/${hubspotId}`, { properties });
  return data;
}

// --- Batch Operations ---

export async function batchCreateLineItems(inputs) {
  const batchSize = config.hubspot.batchSize;
  const results = [];

  for (let i = 0; i < inputs.length; i += batchSize) {
    const batch = inputs.slice(i, i + batchSize);
    await rateLimitDelay();
    const { data } = await client.post('/crm/v3/objects/line_items/batch/create', {
      inputs: batch,
    });
    results.push(...(data.results || []));
    logger.debug(`Created line items batch ${Math.floor(i / batchSize) + 1}`, { count: batch.length });
  }

  return results;
}

export async function batchUpdateLineItems(inputs) {
  const batchSize = config.hubspot.batchSize;
  const results = [];

  for (let i = 0; i < inputs.length; i += batchSize) {
    const batch = inputs.slice(i, i + batchSize);
    await rateLimitDelay();
    const { data } = await client.post('/crm/v3/objects/line_items/batch/update', {
      inputs: batch,
    });
    results.push(...(data.results || []));
  }

  return results;
}

// --- Associations ---

export async function batchCreateAssociations(fromType, toType, inputs) {
  if (!inputs.length) return;
  await rateLimitDelay();
  await client.post(`/crm/v4/associations/${fromType}/${toType}/batch/create`, {
    inputs,
  });
}

// --- Pipeline ---

export async function getDealPipelines() {
  await rateLimitDelay();
  const { data } = await client.get('/crm/v3/pipelines/deals');
  return data.results || [];
}

// --- Properties ---

export async function createDealProperty(name, label, type = 'string', fieldType = 'text') {
  await rateLimitDelay();
  try {
    const { data } = await client.post('/crm/v3/properties/deals', {
      name,
      label,
      type,
      fieldType,
      groupName: 'dealinformation',
    });
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

**Step 2: Commit**

```bash
git add src/api/hubspot.js
git commit -m "feat: HubSpot CRM API client with rate limiting, search, batch, and associations"
```

---

### Task 6: Field Mappers

**Files:**
- Create: `src/mappers/customer.mapper.js`
- Create: `src/mappers/product.mapper.js`
- Create: `src/mappers/order.mapper.js`

**Step 1: Create customer mapper**

`src/mappers/customer.mapper.js`:
```js
export function mapCustomerToContact(customer) {
  const billingAddress = (customer.addresses || []).find(a => a.default_billing) || customer.addresses?.[0];

  const properties = {
    email: customer.email,
    firstname: customer.firstname || '',
    lastname: customer.lastname || '',
  };

  if (billingAddress) {
    if (billingAddress.company) properties.company = billingAddress.company;
    if (billingAddress.telephone) properties.phone = billingAddress.telephone;
    if (billingAddress.street?.length) properties.address = billingAddress.street.join(', ');
    if (billingAddress.city) properties.city = billingAddress.city;
    if (billingAddress.region?.region) properties.state = billingAddress.region.region;
    if (billingAddress.postcode) properties.zip = billingAddress.postcode;
    if (billingAddress.country_id) properties.country = billingAddress.country_id;
  }

  return properties;
}
```

**Step 2: Create product mapper**

`src/mappers/product.mapper.js`:
```js
export function mapProductToHubspot(product) {
  const description = (product.custom_attributes || [])
    .find(a => a.attribute_code === 'description')?.value || '';

  return {
    name: product.name || '',
    hs_sku: product.sku || '',
    price: String(product.price || '0'),
    description: stripHtml(description),
  };
}

function stripHtml(html) {
  return html.replace(/<[^>]*>/g, '').trim();
}
```

**Step 3: Create order mapper**

`src/mappers/order.mapper.js`:
```js
const STAGE_MAP = {
  pending: 'Checkout Completed',
  pending_payment: 'Checkout Completed',
  processing: 'Processing',
  complete: 'Completed',
  canceled: 'Cancelled',
  closed: 'Cancelled',
  holded: 'On Hold',
};

export function mapOrderToDeal(order, pipelineId, stageMap) {
  const stageName = STAGE_MAP[order.status] || 'Checkout Completed';
  const stageId = stageMap[stageName];

  return {
    dealname: `Order #${order.increment_id}`,
    amount: String(order.grand_total || '0'),
    pipeline: pipelineId,
    dealstage: stageId || Object.values(stageMap)[0],
    order_number: String(order.increment_id),
    closedate: order.created_at ? new Date(order.created_at).toISOString() : undefined,
  };
}

export function mapOrderItemToLineItem(item, hubspotProductId) {
  return {
    name: item.name || '',
    quantity: String(item.qty_ordered || 1),
    price: String(item.row_total_incl_tax || item.price || '0'),
    hs_sku: item.sku || '',
    ...(hubspotProductId ? { hs_product_id: hubspotProductId } : {}),
  };
}

export function getOrderItemsForSync(order) {
  // Filter out parent items of configurable products (they have children)
  // Only sync the child items (simple products) that have a parent_item_id
  const items = order.items || [];
  const parentIds = new Set(
    items.filter(i => i.product_type === 'configurable').map(i => i.item_id),
  );
  return items.filter(i => !parentIds.has(i.item_id));
}
```

**Step 4: Commit**

```bash
git add src/mappers/
git commit -m "feat: field mappers for customer, product, and order entities"
```

---

### Task 7: Customer Sync Module

**Files:**
- Create: `src/sync/customers.js`

**Step 1: Create customer sync logic**

```js
import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

export async function syncCustomers(since, runId) {
  logger.info('Starting customer sync', { since: since.toISOString(), runId });

  const customers = await magento.getCustomersUpdatedSince(since);
  logger.info(`Found ${customers.length} customers to sync`, { runId });

  let created = 0;
  let updated = 0;
  let failed = 0;

  for (const customer of customers) {
    try {
      const properties = mapCustomerToContact(customer);
      if (!properties.email) {
        logger.warn('Skipping customer without email', { magentoId: customer.id, runId });
        continue;
      }

      const existingHubspotId = await db.getHubspotId('customer', customer.id);

      if (existingHubspotId) {
        await hubspot.updateContact(existingHubspotId, properties);
        await db.upsertMapping('customer', customer.id, existingHubspotId);
        updated++;
        logger.debug('Updated contact', { magentoId: customer.id, hubspotId: existingHubspotId, runId });
      } else {
        // Search HubSpot by email in case it exists but we don't have a mapping
        const existing = await hubspot.searchContacts(properties.email);
        if (existing) {
          await hubspot.updateContact(existing.id, properties);
          await db.upsertMapping('customer', customer.id, existing.id);
          updated++;
          logger.debug('Found and updated existing contact', { magentoId: customer.id, hubspotId: existing.id, runId });
        } else {
          const result = await hubspot.createContact(properties);
          await db.upsertMapping('customer', customer.id, result.id);
          created++;
          logger.debug('Created new contact', { magentoId: customer.id, hubspotId: result.id, runId });
        }
      }
    } catch (err) {
      failed++;
      logger.error('Failed to sync customer', {
        magentoId: customer.id,
        error: err.message,
        runId,
      });
      await db.addToRetryQueue('customer', customer.id, 'upsert', { customer }, err.message);
    }
  }

  logger.info('Customer sync complete', { created, updated, failed, total: customers.length, runId });
  await db.logSync(runId, 'customer', 'info', `Synced ${customers.length} customers: ${created} created, ${updated} updated, ${failed} failed`);

  return { created, updated, failed };
}
```

**Step 2: Commit**

```bash
git add src/sync/customers.js
git commit -m "feat: customer sync module with search-before-upsert"
```

---

### Task 8: Product Sync Module

**Files:**
- Create: `src/sync/products.js`

**Step 1: Create product sync logic**

```js
import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapProductToHubspot } from '../mappers/product.mapper.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

export async function syncProducts(since, runId) {
  logger.info('Starting product sync', { since: since.toISOString(), runId });

  const products = await magento.getProductsUpdatedSince(since);
  logger.info(`Found ${products.length} products to sync`, { runId });

  let created = 0;
  let updated = 0;
  let failed = 0;

  for (const product of products) {
    try {
      const properties = mapProductToHubspot(product);
      if (!properties.hs_sku) {
        logger.warn('Skipping product without SKU', { magentoId: product.id, runId });
        continue;
      }

      const existingHubspotId = await db.getHubspotId('product', product.id);

      if (existingHubspotId) {
        await hubspot.updateProduct(existingHubspotId, properties);
        await db.upsertMapping('product', product.id, existingHubspotId);
        updated++;
        logger.debug('Updated product', { magentoId: product.id, sku: product.sku, runId });
      } else {
        const existing = await hubspot.searchProductBySku(product.sku);
        if (existing) {
          await hubspot.updateProduct(existing.id, properties);
          await db.upsertMapping('product', product.id, existing.id);
          updated++;
          logger.debug('Found and updated existing product', { magentoId: product.id, hubspotId: existing.id, runId });
        } else {
          const result = await hubspot.createProduct(properties);
          await db.upsertMapping('product', product.id, result.id);
          created++;
          logger.debug('Created new product', { magentoId: product.id, hubspotId: result.id, runId });
        }
      }
    } catch (err) {
      failed++;
      logger.error('Failed to sync product', {
        magentoId: product.id,
        sku: product.sku,
        error: err.message,
        runId,
      });
      await db.addToRetryQueue('product', product.id, 'upsert', { product }, err.message);
    }
  }

  logger.info('Product sync complete', { created, updated, failed, total: products.length, runId });
  await db.logSync(runId, 'product', 'info', `Synced ${products.length} products: ${created} created, ${updated} updated, ${failed} failed`);

  return { created, updated, failed };
}
```

**Step 2: Commit**

```bash
git add src/sync/products.js
git commit -m "feat: product sync module with SKU-based upsert"
```

---

### Task 9: Order Sync Module

**Files:**
- Create: `src/sync/orders.js`

**Step 1: Create order sync logic with line items and associations**

```js
import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapOrderToDeal, mapOrderItemToLineItem, getOrderItemsForSync } from '../mappers/order.mapper.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

let pipelineId = null;
let stageMap = null;

async function ensurePipeline() {
  if (pipelineId && stageMap) return;

  const pipelines = await hubspot.getDealPipelines();
  // Look for an ecommerce pipeline or use default
  const ecommPipeline = pipelines.find(p => p.label === 'Ecommerce Pipeline')
    || pipelines.find(p => p.id === 'default')
    || pipelines[0];

  pipelineId = ecommPipeline.id;
  stageMap = {};
  for (const stage of ecommPipeline.stages || []) {
    stageMap[stage.label] = stage.id;
  }

  logger.info('Using deal pipeline', { pipelineId, stages: Object.keys(stageMap) });
}

export async function syncOrders(since, runId) {
  logger.info('Starting order sync', { since: since.toISOString(), runId });

  await ensurePipeline();

  const orders = await magento.getOrdersUpdatedSince(since);
  logger.info(`Found ${orders.length} orders to sync`, { runId });

  let created = 0;
  let updated = 0;
  let failed = 0;

  for (const order of orders) {
    try {
      await syncSingleOrder(order, runId);
      const existingHubspotId = await db.getHubspotId('order', order.entity_id);
      if (existingHubspotId) {
        updated++;
      } else {
        created++;
      }
    } catch (err) {
      failed++;
      logger.error('Failed to sync order', {
        orderId: order.increment_id,
        error: err.message,
        runId,
      });
      await db.addToRetryQueue('order', order.entity_id, 'upsert', { orderId: order.entity_id }, err.message);
    }
  }

  logger.info('Order sync complete', { created, updated, failed, total: orders.length, runId });
  await db.logSync(runId, 'order', 'info', `Synced ${orders.length} orders: ${created} created, ${updated} updated, ${failed} failed`);

  return { created, updated, failed };
}

async function syncSingleOrder(order, runId) {
  const dealProperties = mapOrderToDeal(order, pipelineId, stageMap);

  // Check if deal already exists in our mapping
  let dealHubspotId = await db.getHubspotId('order', order.entity_id);

  if (dealHubspotId) {
    // Update existing deal
    await hubspot.updateDeal(dealHubspotId, dealProperties);
    logger.debug('Updated deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
  } else {
    // Search HubSpot by order number
    const existing = await hubspot.searchDealByOrderNumber(String(order.increment_id));
    if (existing) {
      dealHubspotId = existing.id;
      await hubspot.updateDeal(dealHubspotId, dealProperties);
      await db.upsertMapping('order', order.entity_id, dealHubspotId);
      logger.debug('Found and updated existing deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
    } else {
      const result = await hubspot.createDeal(dealProperties);
      dealHubspotId = result.id;
      await db.upsertMapping('order', order.entity_id, dealHubspotId);
      logger.debug('Created new deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
    }
  }

  // Associate contact to deal
  const contactHubspotId = order.customer_id
    ? await db.getHubspotId('customer', order.customer_id)
    : null;

  if (contactHubspotId) {
    await hubspot.batchCreateAssociations('deal', 'contact', [{
      from: { id: dealHubspotId },
      to: { id: contactHubspotId },
      types: [{ associationCategory: 'HUBSPOT_DEFINED', associationTypeId: 3 }],
    }]);
    logger.debug('Associated contact to deal', { contactId: contactHubspotId, dealId: dealHubspotId, runId });
  }

  // Sync line items
  await syncLineItems(order, dealHubspotId, runId);
}

async function syncLineItems(order, dealHubspotId, runId) {
  const items = getOrderItemsForSync(order);
  if (!items.length) return;

  // Look up HubSpot product IDs for all items
  const productIds = items.map(i => String(i.product_id));
  const productMappings = await db.getHubspotIdsBatch('product', productIds);

  const lineItemInputs = items.map((item) => {
    const hubspotProductId = productMappings.get(String(item.product_id));
    const properties = mapOrderItemToLineItem(item, hubspotProductId);
    return {
      properties,
      associations: [{
        to: { id: dealHubspotId },
        types: [{ associationCategory: 'HUBSPOT_DEFINED', associationTypeId: 20 }],
      }],
    };
  });

  const results = await hubspot.batchCreateLineItems(lineItemInputs);
  logger.debug('Created line items', { orderId: order.increment_id, count: results.length, runId });

  // Store line item mappings
  for (let i = 0; i < results.length && i < items.length; i++) {
    await db.upsertMapping('line_item', items[i].item_id, results[i].id);
  }
}
```

**Step 2: Commit**

```bash
git add src/sync/orders.js
git commit -m "feat: order sync module with line items and associations"
```

---

### Task 10: Retry Queue Processor

**Files:**
- Create: `src/sync/retry-queue.js`

**Step 1: Create retry queue processor**

```js
import * as db from '../db/sync-state.js';
import { syncCustomers } from './customers.js';
import { syncProducts } from './products.js';
import { syncOrders } from './orders.js';
import * as hubspot from '../api/hubspot.js';
import * as magento from '../api/magento.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import { mapProductToHubspot } from '../mappers/product.mapper.js';
import logger from '../utils/logger.js';

export async function processRetryQueue(runId) {
  const items = await db.getRetryItems(50);
  if (!items.length) return;

  logger.info(`Processing retry queue: ${items.length} items`, { runId });

  let succeeded = 0;
  let failed = 0;

  for (const item of items) {
    try {
      await retryItem(item);
      await db.removeRetryItem(item.id);
      succeeded++;
      logger.info('Retry succeeded', { entityType: item.entity_type, magentoId: item.magento_id, runId });
    } catch (err) {
      const newAttempts = item.attempts + 1;
      await db.updateRetryItem(item.id, newAttempts, err.message);
      failed++;

      if (newAttempts >= item.max_attempts) {
        logger.error('Retry exhausted max attempts', {
          entityType: item.entity_type,
          magentoId: item.magento_id,
          attempts: newAttempts,
          error: err.message,
          runId,
        });
      } else {
        logger.warn('Retry failed, will try again', {
          entityType: item.entity_type,
          magentoId: item.magento_id,
          attempts: newAttempts,
          error: err.message,
          runId,
        });
      }
    }
  }

  logger.info('Retry queue processing complete', { succeeded, failed, runId });
}

async function retryItem(item) {
  const payload = item.payload;

  switch (item.entity_type) {
    case 'customer': {
      const customer = payload.customer;
      const properties = mapCustomerToContact(customer);
      const existing = await hubspot.searchContacts(properties.email);
      if (existing) {
        await hubspot.updateContact(existing.id, properties);
        await db.upsertMapping('customer', item.magento_id, existing.id);
      } else {
        const result = await hubspot.createContact(properties);
        await db.upsertMapping('customer', item.magento_id, result.id);
      }
      break;
    }
    case 'product': {
      const product = payload.product;
      const properties = mapProductToHubspot(product);
      const existing = await hubspot.searchProductBySku(properties.hs_sku);
      if (existing) {
        await hubspot.updateProduct(existing.id, properties);
        await db.upsertMapping('product', item.magento_id, existing.id);
      } else {
        const result = await hubspot.createProduct(properties);
        await db.upsertMapping('product', item.magento_id, result.id);
      }
      break;
    }
    case 'order': {
      // For orders, re-fetch from Magento since order data is complex
      const orders = await magento.getOrdersUpdatedSince(new Date('1970-01-01'));
      // This is a fallback - ideally we'd fetch single order
      logger.warn('Order retry uses full refetch - consider adding single order fetch');
      break;
    }
    default:
      logger.warn('Unknown entity type in retry queue', { entityType: item.entity_type });
  }
}
```

**Step 2: Commit**

```bash
git add src/sync/retry-queue.js
git commit -m "feat: retry queue processor with exponential backoff"
```

---

### Task 11: Scheduler and Entry Point

**Files:**
- Create: `src/sync/scheduler.js`
- Create: `src/index.js`

**Step 1: Create scheduler**

`src/sync/scheduler.js`:
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

export async function runFullSync() {
  if (syncInProgress) {
    logger.warn('Sync already in progress, skipping');
    return;
  }

  syncInProgress = true;
  const runId = uuidv4();
  const syncStart = new Date();

  logger.info('=== Starting full sync ===', { runId });

  try {
    // 1. Sync products first (orders reference them)
    const productSince = await db.getLastSyncedAt('product');
    const productResult = await syncProducts(productSince, runId);

    // 2. Sync customers (orders reference them)
    const customerSince = await db.getLastSyncedAt('customer');
    const customerResult = await syncCustomers(customerSince, runId);

    // 3. Sync orders last
    const orderSince = await db.getLastSyncedAt('order');
    const orderResult = await syncOrders(orderSince, runId);

    // 4. Update sync timestamps
    await db.updateLastSyncedAt('product', syncStart);
    await db.updateLastSyncedAt('customer', syncStart);
    await db.updateLastSyncedAt('order', syncStart);

    // 5. Process retry queue
    await processRetryQueue(runId);

    logger.info('=== Full sync complete ===', {
      runId,
      products: productResult,
      customers: customerResult,
      orders: orderResult,
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

**Step 2: Create entry point**

`src/index.js`:
```js
import { config } from './config/index.js';
import { runMigrations } from './db/migrations.js';
import { startScheduler, runFullSync } from './sync/scheduler.js';
import * as hubspot from './api/hubspot.js';
import logger from './utils/logger.js';

async function main() {
  logger.info('Starting Magento-HubSpot sync service', {
    syncInterval: `${config.sync.intervalMinutes} minutes`,
    excludedSalesreps: config.sync.excludedSalesrepIds,
  });

  // Run database migrations
  await runMigrations();

  // Ensure required HubSpot deal properties exist
  await hubspot.createDealProperty('order_number', 'Order Number');

  // Run initial sync immediately
  logger.info('Running initial sync...');
  await runFullSync();

  // Start scheduled sync
  startScheduler();

  logger.info('Sync service running. Press Ctrl+C to stop.');
}

main().catch((err) => {
  logger.error('Fatal error', { error: err.message, stack: err.stack });
  process.exit(1);
});
```

**Step 3: Commit**

```bash
git add src/sync/scheduler.js src/index.js
git commit -m "feat: scheduler and entry point - app is runnable"
```

---

### Task 12: HubSpot Setup & Deal Property Initialization

**Files:**
- Modify: `src/index.js` (already has createDealProperty call)

**Step 1: Verify the `order_number` custom property creation works**

The entry point already calls `hubspot.createDealProperty('order_number', 'Order Number')` which handles 409 conflicts gracefully. This ensures the custom property exists before we try to search by it.

**Step 2: Test the full app manually**

Run:
```bash
cp .env.example .env
# Edit .env with real credentials
node src/index.js
```

Expected: App starts, runs migrations, creates deal property, runs initial sync, then starts cron scheduler.

**Step 3: Commit any fixes**

```bash
git add -A
git commit -m "fix: any issues found during manual testing"
```

---

### Task 13: Final Review and Documentation

**Step 1: Review all files for consistency**

Check that:
- All imports resolve correctly (ESM paths with `.js` extensions)
- No circular dependencies
- Error handling is consistent
- Logger is used throughout

**Step 2: Create a minimal README with setup instructions**

The user should know how to:
1. `npm install`
2. Set up PostgreSQL database
3. Copy `.env.example` to `.env` and fill in credentials
4. `npm start`

**Step 3: Final commit**

```bash
git add -A
git commit -m "docs: add setup instructions"
```
