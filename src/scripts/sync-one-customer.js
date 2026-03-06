/**
 * One-off script to sync a single customer and their orders.
 * Usage: node src/scripts/sync-one-customer.js <magento_customer_id>
 */

import 'dotenv/config';
import { runMigrations } from '../db/migrations.js';
import pool from '../db/index.js';
import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import * as db from '../db/sync-state.js';
import { syncSingleOrder } from '../sync/orders.js';
import logger from '../utils/logger.js';

const customerId = process.argv[2];

if (!customerId) {
  console.error('Usage: node src/scripts/sync-one-customer.js <magento_customer_id>');
  process.exit(1);
}

async function run() {
  await runMigrations();

  // --- Sync customer ---
  logger.info(`Fetching customer ${customerId} from Magento...`);
  const customer = await magento.getCustomerById(customerId);
  logger.info(`Found customer: ${customer.email} (${customer.firstname} ${customer.lastname})`);

  const properties = mapCustomerToContact(customer);

  let contactHubspotId = await db.getHubspotId('customer', customer.id);

  if (contactHubspotId) {
    await hubspot.updateContact(contactHubspotId, properties);
    logger.info(`Updated existing HubSpot contact: ${contactHubspotId}`);
  } else {
    const existing = await hubspot.searchContacts(properties.email);
    if (existing) {
      contactHubspotId = existing.id;
      await hubspot.updateContact(contactHubspotId, properties);
      await db.upsertMapping('customer', customer.id, contactHubspotId);
      logger.info(`Found and updated existing HubSpot contact: ${contactHubspotId}`);
    } else {
      const result = await hubspot.createContact(properties);
      contactHubspotId = result.id;
      await db.upsertMapping('customer', customer.id, contactHubspotId);
      logger.info(`Created new HubSpot contact: ${contactHubspotId}`);
    }
  }

  // --- Sync orders ---
  logger.info(`Fetching orders for customer ${customerId}...`);
  const orders = await magento.getOrdersByCustomerId(customerId);
  logger.info(`Found ${orders.length} orders`);

  let created = 0;
  let updated = 0;
  let failed = 0;

  for (const order of orders) {
    try {
      const existed = await db.getHubspotId('order', order.entity_id);
      await syncSingleOrder(order, 'manual');
      existed ? updated++ : created++;
      logger.info(`Synced order #${order.increment_id}`);
    } catch (err) {
      failed++;
      logger.error(`Failed to sync order #${order.increment_id}: ${err.message}`);
    }
  }

  logger.info(`Done. Contact: ${contactHubspotId} | Orders: ${created} created, ${updated} updated, ${failed} failed`);
  await pool.end();
}

run().catch((err) => {
  logger.error('Script failed', { error: err.message, stack: err.stack });
  pool.end().finally(() => process.exit(1));
});
