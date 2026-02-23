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
        created_at: row.created_at || null,
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
