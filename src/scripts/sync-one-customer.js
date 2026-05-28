/**
 * One-off script to sync a single customer and their orders.
 * Usage: node src/scripts/sync-one-customer.js <magento_customer_id>
 */

import 'dotenv/config';
import { runMigrations } from '../db/migrations.js';
import pool from '../db/index.js';
import * as magento from '../api/magento.js';
import * as db from '../db/sync-state.js';
import { syncSingleOrder } from '../sync/orders.js';
import { isEligibleCustomer, isQualifyingOrder, requiresQualifyingOrderForCustomer } from '../sync/eligibility.js';
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

  if (!isEligibleCustomer(customer)) {
    logger.info(`Customer ${customerId} is not eligible for HubSpot sync`);
    await pool.end();
    return;
  }

  logger.info(`Fetching orders for customer ${customerId}...`);
  const orders = await magento.getOrdersByCustomerId(customerId);
  const qualifyingOrders = orders.filter(isQualifyingOrder);
  logger.info(`Found ${orders.length} orders, ${qualifyingOrders.length} qualifying`);

  if (requiresQualifyingOrderForCustomer(customer) && !qualifyingOrders.length) {
    logger.info(`Customer ${customerId} has no qualifying orders; nothing to sync`);
    await pool.end();
    return;
  }

  let created = 0;
  let updated = 0;
  let failed = 0;
  let contactHubspotId = null;

  for (const order of qualifyingOrders) {
    try {
      const existed = await db.getHubspotId('order', order.entity_id);
      const result = await syncSingleOrder(order, 'manual', null, customer);
      if (result.skipped) continue;
      contactHubspotId ||= result.contactHubspotId;
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
