import pool from './index.js';
import { config } from '../config/index.js';
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
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        UNIQUE(entity_type, magento_id)
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

    // If SYNC_START_DATE is set, update any sync_state rows still at epoch
    if (config.sync.startDate) {
      await client.query(
        `UPDATE sync_state SET last_synced_at = $1
         WHERE last_synced_at = '1970-01-01T00:00:00Z'`,
        [config.sync.startDate],
      );
      logger.info('Set initial sync start date', { startDate: config.sync.startDate.toISOString() });
    }

    logger.info('Database migrations completed');
  } finally {
    client.release();
  }
}
