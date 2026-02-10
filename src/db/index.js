import pg from 'pg';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';

const pool = new pg.Pool({
  connectionString: config.db.connectionString,
});

pool.on('error', (err) => {
  logger.error('Unexpected PostgreSQL pool error', { error: err.message });
});

export default pool;
