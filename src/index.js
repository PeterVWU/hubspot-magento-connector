import { config } from './config/index.js';
import { runMigrations } from './db/migrations.js';
import pool from './db/index.js';
import { startScheduler, runFullSync, setHeartbeat } from './sync/scheduler.js';
import * as hubspot from './api/hubspot.js';
import { startWatchdog } from './utils/watchdog.js';
import logger from './utils/logger.js';

async function main() {
  logger.info('Starting Magento-HubSpot sync service', {
    syncInterval: `${config.sync.intervalMinutes} minutes`,
    excludedSalesreps: config.sync.excludedSalesrepIds,
  });

  // Run database migrations
  await runMigrations();

  // Ensure required HubSpot custom properties exist
  await hubspot.createContactProperty('account_created_date', 'Account Created Date', 'date', 'date');
  await hubspot.createDealProperty('order_number', 'Order Number');

  // Start worker thread watchdog (kills process if event loop goes inert)
  const watchdog = startWatchdog(config.sync.timeoutMinutes * 2);
  setHeartbeat(watchdog.heartbeat);

  // Run initial sync immediately
  logger.info('Running initial sync...');
  await runFullSync();

  // Start scheduled sync
  startScheduler();

  logger.info('Sync service running. Press Ctrl+C to stop.');
}

// Graceful shutdown
async function shutdown(signal) {
  logger.info(`Received ${signal}, shutting down gracefully...`);
  await pool.end();
  logger.info('Database pool closed. Exiting.');
  process.exit(0);
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));

main().catch((err) => {
  logger.error('Fatal error', { error: err.message, stack: err.stack });
  process.exit(1);
});
