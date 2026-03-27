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
