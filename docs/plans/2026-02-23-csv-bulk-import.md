# CSV Bulk Import Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Import historical Magento customers and orders into HubSpot from 3 CSV files exported from the Magento database.

**Architecture:** A single script (`src/scripts/import-csv.js`) reads `data/customers.csv`, `data/orders.csv`, and `data/order_items.csv`, reshapes each row into the object shape the existing mappers and sync logic already expect, then calls the same HubSpot upsert functions used by the live sync. No new mapper or API logic needed — only a CSV parser and a reshaping layer.

**Tech Stack:** Node.js ESM, `csv-parse` (new dependency), existing `src/mappers/`, `src/sync/orders.js`, `src/api/hubspot.js`, `src/db/sync-state.js`

---

### Task 1: Install csv-parse and set up data directory

**Files:**
- Modify: `package.json`
- Modify: `.gitignore`
- Create: `data/.gitkeep`

**Step 1: Install csv-parse**

```bash
npm install csv-parse
```

Expected: `csv-parse` appears in `package.json` dependencies.

**Step 2: Add data directory to .gitignore**

Add these lines to `.gitignore`:
```
data/*.csv
```
This keeps CSVs (which may contain PII) out of git while tracking the directory itself.

**Step 3: Create data directory placeholder**

```bash
mkdir -p data && touch data/.gitkeep
```

**Step 4: Commit**

```bash
git add package.json package-lock.json .gitignore data/.gitkeep
git commit -m "feat: add csv-parse dependency and data directory for bulk import"
```

---

### Task 2: Write the CSV parser utility

**Files:**
- Create: `src/utils/csv.js`

**Step 1: Write the utility**

Create `src/utils/csv.js`:

```js
import { createReadStream } from 'fs';
import { parse } from 'csv-parse';

/**
 * Reads a CSV file and returns an array of objects keyed by header row.
 * Skips empty rows. Trims whitespace from all values.
 */
export async function readCsv(filePath) {
  return new Promise((resolve, reject) => {
    const records = [];
    createReadStream(filePath)
      .pipe(parse({
        columns: true,       // use first row as keys
        skip_empty_lines: true,
        trim: true,
      }))
      .on('data', (row) => records.push(row))
      .on('end', () => resolve(records))
      .on('error', reject);
  });
}
```

**Step 2: Commit**

```bash
git add src/utils/csv.js
git commit -m "feat: add CSV reader utility"
```

---

### Task 3: Write the import script

**Files:**
- Create: `src/scripts/import-csv.js`

This script:
1. Reads all 3 CSVs
2. Syncs customers first (same search-before-upsert logic as live sync)
3. Builds order objects (with embedded items array) and calls `syncSingleOrder` for each

**Step 1: Write the script**

Create `src/scripts/import-csv.js`:

```js
/**
 * Bulk import script — reads data/customers.csv, data/orders.csv, data/order_items.csv
 * and syncs them to HubSpot using the same logic as the live sync.
 *
 * Usage: node src/scripts/import-csv.js
 */

import 'dotenv/config';
import { readCsv } from '../utils/csv.js';
import { runMigrations } from '../db/migrations.js';
import pool from '../db/index.js';
import * as hubspot from '../api/hubspot.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import * as db from '../db/sync-state.js';
import { syncSingleOrder } from '../sync/orders.js';
import logger from '../utils/logger.js';

const CUSTOMERS_FILE = 'data/customers.csv';
const ORDERS_FILE    = 'data/orders.csv';
const ITEMS_FILE     = 'data/order_items.csv';

// --- Customer sync ---

async function importCustomers(rows) {
  let created = 0, updated = 0, skipped = 0, failed = 0;

  for (const row of rows) {
    if (!row.email) {
      skipped++;
      logger.warn('Skipping customer row without email', { entity_id: row.entity_id });
      continue;
    }

    try {
      // Reshape CSV row into the object shape mapCustomerToContact expects
      const customer = {
        id: row.entity_id,
        email: row.email,
        firstname: row.firstname,
        lastname: row.lastname,
        addresses: [{
          default_billing: true,
          company: row.company || '',
          telephone: row.telephone || '',
          street: row.street ? [row.street] : [],
          city: row.city || '',
          region: { region: row.region || '' },
          postcode: row.postcode || '',
          country_id: row.country_id || '',
        }],
      };

      const properties = mapCustomerToContact(customer);
      let contactHubspotId = await db.getHubspotId('customer', row.entity_id);

      if (contactHubspotId) {
        await hubspot.updateContact(contactHubspotId, properties);
        updated++;
      } else {
        const existing = await hubspot.searchContacts(properties.email);
        if (existing) {
          contactHubspotId = existing.id;
          await hubspot.updateContact(contactHubspotId, properties);
          await db.upsertMapping('customer', row.entity_id, contactHubspotId);
          updated++;
        } else {
          const result = await hubspot.createContact(properties);
          contactHubspotId = result.id;
          await db.upsertMapping('customer', row.entity_id, contactHubspotId);
          created++;
        }
      }

      if ((created + updated) % 100 === 0) {
        logger.info(`Customer progress: ${created} created, ${updated} updated, ${failed} failed`);
      }
    } catch (err) {
      failed++;
      logger.error('Failed to import customer', { entity_id: row.entity_id, email: row.email, error: err.message });
    }
  }

  return { created, updated, skipped, failed };
}

// --- Order sync ---

async function importOrders(orderRows, itemRows) {
  // Group items by order_id for fast lookup
  const itemsByOrderId = new Map();
  for (const item of itemRows) {
    const key = item.order_id;
    if (!itemsByOrderId.has(key)) itemsByOrderId.set(key, []);
    itemsByOrderId.get(key).push(item);
  }

  let created = 0, updated = 0, failed = 0;

  for (const row of orderRows) {
    try {
      // Reshape CSV row into the object shape syncSingleOrder expects
      const order = {
        entity_id: row.entity_id,
        increment_id: row.increment_id,
        customer_id: row.customer_id || null,
        grand_total: row.grand_total,
        status: row.status,
        order_currency_code: row.order_currency_code || 'USD',
        items: (itemsByOrderId.get(row.entity_id) || []).map(i => ({
          item_id: i.item_id,
          product_id: i.product_id,
          name: i.name,
          sku: i.sku,
          qty_ordered: i.qty_ordered,
          row_total_incl_tax: i.row_total_incl_tax,
          price: i.price,
          product_type: i.product_type,
        })),
      };

      const existed = !!(await db.getHubspotId('order', row.entity_id));
      await syncSingleOrder(order, 'csv-import');
      existed ? updated++ : created++;

      if ((created + updated) % 100 === 0) {
        logger.info(`Order progress: ${created} created, ${updated} updated, ${failed} failed`);
      }
    } catch (err) {
      failed++;
      logger.error('Failed to import order', { entity_id: row.entity_id, increment_id: row.increment_id, error: err.message });
    }
  }

  return { created, updated, failed };
}

// --- Main ---

async function run() {
  await runMigrations();

  logger.info('Reading CSV files...');
  const [customerRows, orderRows, itemRows] = await Promise.all([
    readCsv(CUSTOMERS_FILE),
    readCsv(ORDERS_FILE),
    readCsv(ITEMS_FILE),
  ]);

  logger.info(`Loaded: ${customerRows.length} customers, ${orderRows.length} orders, ${itemRows.length} order items`);

  logger.info('--- Importing customers ---');
  const custStats = await importCustomers(customerRows);
  logger.info('Customer import complete', custStats);

  logger.info('--- Importing orders ---');
  const orderStats = await importOrders(orderRows, itemRows);
  logger.info('Order import complete', orderStats);

  logger.info('=== IMPORT SUMMARY ===');
  logger.info(`Customers: ${custStats.created} created, ${custStats.updated} updated, ${custStats.skipped} skipped, ${custStats.failed} failed`);
  logger.info(`Orders:    ${orderStats.created} created, ${orderStats.updated} updated, ${orderStats.failed} failed`);

  await pool.end();
}

run().catch((err) => {
  logger.error('Import failed', { error: err.message, stack: err.stack });
  pool.end().finally(() => process.exit(1));
});
```

**Step 2: Commit**

```bash
git add src/scripts/import-csv.js
git commit -m "feat: add CSV bulk import script for historical backfill"
```

---

### Task 4: Add npm script shortcut

**Files:**
- Modify: `package.json`

**Step 1: Add the script**

Add to the `scripts` section in `package.json`:
```json
"import-csv": "node src/scripts/import-csv.js"
```

**Step 2: Commit**

```bash
git add package.json
git commit -m "feat: add import-csv npm script"
```

---

### Task 5: Test with real data

**Step 1: Place the CSV files**

Copy your 3 exported CSV files into the `data/` directory:
- `data/customers.csv`
- `data/orders.csv`
- `data/order_items.csv`

**Step 2: Run a small test first**

To test with a small subset, open `data/customers.csv` and keep only 2-3 rows (plus the header). Run:

```bash
npm run import-csv
```

Watch the logs. Verify in HubSpot that the contacts and orders appear and are linked correctly.

**Step 3: Run full import**

Restore the full CSV and run:
```bash
npm run import-csv
```

The script is fully idempotent — if it's interrupted or partially fails, just re-run it. Already-synced records will be updated (not duplicated) because of the DB mapping checks.

**Step 4: Verify in HubSpot**

- Search for a known customer email → should see contact with orders associated
- Open an order → should see line items linked

---

## Notes

- **Idempotent**: Safe to re-run. The DB mapping table (`entity_mapping`) prevents duplicates.
- **Customers must be imported before orders** — the order sync looks up `customer_id` in the mapping table to create the contact→order association. The script already does customers first.
- **Products**: Line items will have `hs_product_id` set only if the product was previously synced via the live sync. That's fine — line item still imports, just without the product catalog link.
- **Guest orders**: `customer_id` will be null for guest orders in the CSV — the order still imports, just without a contact association.
