/**
 * Bulk import script — reads data/customers.csv, data/orders.csv, data/order_items.csv
 * and syncs them to HubSpot using the same logic as the live sync.
 *
 * Usage: node src/scripts/import-csv.js
 */

import 'dotenv/config';
import { readCsvGlob } from '../utils/csv.js';
import { runMigrations } from '../db/migrations.js';
import pool from '../db/index.js';
import * as hubspot from '../api/hubspot.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import { SALESREP_OWNER_MAP } from '../config/salesrep-mapping.js';
import * as db from '../db/sync-state.js';
import { syncSingleOrder } from '../sync/orders.js';
import logger from '../utils/logger.js';

const CUSTOMERS_PREFIX = 'data/customers';
const ORDERS_PREFIX    = 'data/orders';
const ITEMS_PREFIX     = 'data/order_items';

// Column names for headerless CSVs exported via: gcloud sql export csv
// If your files have a header row, set these to true instead.
const CUSTOMERS_COLUMNS = ['entity_id','email','firstname','lastname','created_at','company','telephone','street','city','region','postcode','country_id','salesrep_rep_id'];
const ORDERS_COLUMNS    = ['entity_id','increment_id','customer_id','grand_total','status','order_currency_code','created_at'];
const ITEMS_COLUMNS     = ['item_id','order_id','product_id','name','sku','qty_ordered','row_total_incl_tax','price','product_type'];

function progress(label, current, total, stats) {
  const pct = total > 0 ? ((current / total) * 100).toFixed(1) : '0.0';
  const bar = Math.floor((current / total) * 20);
  const filled = '█'.repeat(bar) + '░'.repeat(20 - bar);
  const line = `${label}: [${filled}] ${current}/${total} (${pct}%) | created: ${stats.created} updated: ${stats.updated} failed: ${stats.failed}`;
  process.stdout.write(`\r${line}  `);
}

// --- Customer sync ---

async function importCustomers(rows) {
  let created = 0, updated = 0, skipped = 0, failed = 0;
  const total = rows.length;
  let i = 0;

  for (const row of rows) {
    i++;
    if (!row.email) {
      skipped++;
      progress('Customers', i, total, { created, updated, failed });
      continue;
    }

    try {
      // Reshape CSV row into the object shape mapCustomerToContact expects
      const customer = {
        id: row.entity_id,
        email: row.email,
        firstname: row.firstname,
        lastname: row.lastname,
        created_at: row.created_at || null,
        salesrep_rep_id: row.salesrep_rep_id || null,
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

      progress('Customers', i, total, { created, updated, failed });
    } catch (err) {
      failed++;
      logger.error('\nFailed to import customer', { entity_id: row.entity_id, email: row.email, error: err.message });
      progress('Customers', i, total, { created, updated, failed });
    }
  }

  process.stdout.write('\n');
  return { created, updated, skipped, failed };
}

// --- Order sync ---

async function importOrders(orderRows, itemRows, customerOwnerMap) {
  // Group items by order_id for fast lookup
  const itemsByOrderId = new Map();
  for (const item of itemRows) {
    const key = item.order_id;
    if (!itemsByOrderId.has(key)) itemsByOrderId.set(key, []);
    itemsByOrderId.get(key).push(item);
  }

  let created = 0, updated = 0, failed = 0;
  const total = orderRows.length;
  let i = 0;

  for (const row of orderRows) {
    i++;
    try {
      // Reshape CSV row into the object shape syncSingleOrder expects
      const order = {
        entity_id: row.entity_id,
        increment_id: row.increment_id,
        customer_id: row.customer_id || null,
        grand_total: row.grand_total,
        status: row.status,
        order_currency_code: row.order_currency_code || 'USD',
        created_at: row.created_at || null,
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
      const ownerId = row.customer_id ? (customerOwnerMap.get(String(row.customer_id)) || null) : null;
      await syncSingleOrder(order, 'csv-import', ownerId);
      existed ? updated++ : created++;
      progress('Orders', i, total, { created, updated, failed });
    } catch (err) {
      failed++;
      logger.error('\nFailed to import order', { entity_id: row.entity_id, increment_id: row.increment_id, error: err.message });
      progress('Orders', i, total, { created, updated, failed });
    }
  }

  process.stdout.write('\n');
  return { created, updated, failed };
}

// --- Main ---

async function run() {
  await runMigrations();

  logger.info('Reading CSV files...');
  const [
    { rows: customerRows, files: custFiles },
    { rows: orderRows,    files: orderFiles },
    { rows: itemRows,     files: itemFiles },
  ] = await Promise.all([
    readCsvGlob(CUSTOMERS_PREFIX, CUSTOMERS_COLUMNS),
    readCsvGlob(ORDERS_PREFIX,    ORDERS_COLUMNS),
    readCsvGlob(ITEMS_PREFIX,     ITEMS_COLUMNS),
  ]);

  logger.info('Customer files:', custFiles);
  logger.info('Order files:   ', orderFiles);
  logger.info('Item files:    ', itemFiles);
  logger.info(`Loaded: ${customerRows.length} customers, ${orderRows.length} orders, ${itemRows.length} order items`);

  logger.info('--- Importing customers ---');
  const custStats = await importCustomers(customerRows);
  logger.info('Customer import complete', custStats);

  // Build customer_id → hubspot_owner_id map for order assignment
  const customerOwnerMap = new Map();
  for (const row of customerRows) {
    if (row.salesrep_rep_id && row.entity_id) {
      const ownerId = SALESREP_OWNER_MAP[String(row.salesrep_rep_id)];
      if (ownerId) customerOwnerMap.set(String(row.entity_id), ownerId);
    }
  }
  logger.info(`Built owner map: ${customerOwnerMap.size} customers have an assigned owner`);

  logger.info('--- Importing orders ---');
  const orderStats = await importOrders(orderRows, itemRows, customerOwnerMap);
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
