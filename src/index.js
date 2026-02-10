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
