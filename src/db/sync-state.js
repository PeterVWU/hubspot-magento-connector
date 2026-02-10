import pool from './index.js';

export async function getLastSyncedAt(entityType) {
  const { rows } = await pool.query(
    'SELECT last_synced_at FROM sync_state WHERE entity_type = $1',
    [entityType],
  );
  return rows[0]?.last_synced_at || new Date('1970-01-01');
}

export async function updateLastSyncedAt(entityType, timestamp) {
  await pool.query(
    `UPDATE sync_state SET last_synced_at = $1, updated_at = NOW()
     WHERE entity_type = $2`,
    [timestamp, entityType],
  );
}

export async function getHubspotId(entityType, magentoId) {
  const { rows } = await pool.query(
    'SELECT hubspot_id FROM entity_mapping WHERE entity_type = $1 AND magento_id = $2',
    [entityType, String(magentoId)],
  );
  return rows[0]?.hubspot_id || null;
}

export async function upsertMapping(entityType, magentoId, hubspotId) {
  await pool.query(
    `INSERT INTO entity_mapping (entity_type, magento_id, hubspot_id)
     VALUES ($1, $2, $3)
     ON CONFLICT (entity_type, magento_id)
     DO UPDATE SET hubspot_id = $3, updated_at = NOW()`,
    [entityType, String(magentoId), String(hubspotId)],
  );
}

export async function getHubspotIdsBatch(entityType, magentoIds) {
  if (!magentoIds.length) return new Map();
  const { rows } = await pool.query(
    'SELECT magento_id, hubspot_id FROM entity_mapping WHERE entity_type = $1 AND magento_id = ANY($2)',
    [entityType, magentoIds.map(String)],
  );
  return new Map(rows.map(r => [r.magento_id, r.hubspot_id]));
}

export async function addToRetryQueue(entityType, magentoId, operation, payload, errorMessage) {
  await pool.query(
    `INSERT INTO sync_retry_queue (entity_type, magento_id, operation, payload, error_message, next_retry_at)
     VALUES ($1, $2, $3, $4, $5, NOW() + INTERVAL '1 minute')`,
    [entityType, String(magentoId), operation, JSON.stringify(payload), errorMessage],
  );
}

export async function getRetryItems(limit = 50) {
  const { rows } = await pool.query(
    `SELECT * FROM sync_retry_queue
     WHERE attempts < max_attempts AND next_retry_at <= NOW()
     ORDER BY next_retry_at ASC
     LIMIT $1`,
    [limit],
  );
  return rows;
}

export async function updateRetryItem(id, attempts, errorMessage) {
  const backoffMinutes = [1, 5, 15, 60, 240];
  const nextBackoff = backoffMinutes[Math.min(attempts, backoffMinutes.length - 1)];
  await pool.query(
    `UPDATE sync_retry_queue
     SET attempts = $1, error_message = $2, next_retry_at = NOW() + ($3 || ' minutes')::INTERVAL, updated_at = NOW()
     WHERE id = $4`,
    [attempts, errorMessage, String(nextBackoff), id],
  );
}

export async function removeRetryItem(id) {
  await pool.query('DELETE FROM sync_retry_queue WHERE id = $1', [id]);
}

export async function logSync(runId, entityType, level, message, metadata = null) {
  await pool.query(
    `INSERT INTO sync_log (run_id, entity_type, level, message, metadata)
     VALUES ($1, $2, $3, $4, $5)`,
    [runId, entityType, level, message, metadata ? JSON.stringify(metadata) : null],
  );
}
