import cron from 'node-cron';
import { v4 as uuidv4 } from 'uuid';
import { config } from '../config/index.js';
import { syncProducts } from './products.js';
import { syncCustomers } from './customers.js';
import { syncOrders } from './orders.js';
import { syncOwnersReverse } from './owner-reverse.js';
import { processRetryQueue } from './retry-queue.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

let syncInProgress = false;
let heartbeat = () => {};

export function setHeartbeat(fn) {
  heartbeat = fn;
}

async function runSyncBody(runId, syncStart) {
  // 1. Sync products first (orders reference them)
  const productSince = await db.getLastSyncedAt('product');
  const productResult = await syncProducts(productSince, runId);

  // 2. Reverse-sync HubSpot contact owner → Magento customer salesrep.
  // Runs BEFORE forward customer sync so that a HubSpot owner change is
  // written to Magento first; otherwise forward sync would push the stale
  // Magento salesrep back to HubSpot and clobber the user's change.
  const ownerReverseSince = await db.getLastSyncedAt('owner_reverse');
  const ownerReverseResult = await syncOwnersReverse(ownerReverseSince, runId);

  // 3. Sync customers (orders reference them)
  const customerSince = await db.getLastSyncedAt('customer');
  const customerResult = await syncCustomers(customerSince, runId);

  // 4. Sync orders last
  const orderSince = await db.getLastSyncedAt('order');
  const orderResult = await syncOrders(orderSince, runId);

  // 5. Update sync timestamps only for entity types with zero failures
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
  // owner_reverse updates its own timestamp internally so progress is saved
  // even when the outer sync times out.

  // 6. Process retry queue
  await processRetryQueue(runId);

  return { productResult, customerResult, orderResult, ownerReverseResult };
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

  let watchdogTimer;
  try {
    const watchdog = new Promise((_, reject) => {
      watchdogTimer = setTimeout(
        () => reject(new Error(`Sync timed out after ${config.sync.timeoutMinutes} minutes`)),
        timeoutMs,
      );
    });

    const results = await Promise.race([runSyncBody(runId, syncStart), watchdog]);

    logger.info('=== Full sync complete ===', {
      runId,
      products: results.productResult,
      customers: results.customerResult,
      orders: results.orderResult,
      ownerReverse: results.ownerReverseResult,
      duration: `${((Date.now() - syncStart.getTime()) / 1000).toFixed(1)}s`,
    });
  } catch (err) {
    logger.error('Full sync failed', { runId, error: err.message, stack: err.stack });
    await db.logSync(runId, null, 'error', `Sync failed: ${err.message}`);
  } finally {
    clearTimeout(watchdogTimer);
    syncInProgress = false;
    heartbeat();
    // Direct stdout write — survives even if Winston transport dies
    process.stdout.write(`[heartbeat] ${new Date().toISOString()} sync cycle done\n`);
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
