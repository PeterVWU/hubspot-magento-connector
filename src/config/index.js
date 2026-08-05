import 'dotenv/config';

const required = [
  'MAGENTO_BASE_URL',
  'MAGENTO_TOKEN',
  'HUBSPOT_ACCESS_TOKEN',
  'DATABASE_URL',
];

for (const key of required) {
  if (!process.env[key]) {
    throw new Error(`Missing required environment variable: ${key}`);
  }
}

export const config = {
  magento: {
    baseUrl: process.env.MAGENTO_BASE_URL,
    token: process.env.MAGENTO_TOKEN,
    pageSize: parseInt(process.env.MAGENTO_PAGE_SIZE || '100', 10),
    maxRecordsPerSync: parseInt(process.env.MAX_RECORDS_PER_SYNC || '0', 10),
    timeout: parseInt(process.env.MAGENTO_TIMEOUT_MS || '120000', 10),
  },
  hubspot: {
    accessToken: process.env.HUBSPOT_ACCESS_TOKEN,
    batchSize: parseInt(process.env.HUBSPOT_BATCH_SIZE || '100', 10),
    timeout: parseInt(process.env.HUBSPOT_TIMEOUT_MS || '30000', 10),
    maxRetries: parseInt(process.env.HUBSPOT_MAX_RETRIES || '3', 10),
  },
  db: {
    connectionString: process.env.DATABASE_URL,
  },
  sync: {
    intervalMinutes: parseInt(process.env.SYNC_INTERVAL_MINUTES || '5', 10),
    timeoutMinutes: parseInt(process.env.SYNC_TIMEOUT_MINUTES || '10', 10),
    customerMinOrderTotal: parseFloat(process.env.CUSTOMER_MIN_ORDER_TOTAL || '500'),
    ownerReverseBatchSize: parseInt(process.env.OWNER_REVERSE_BATCH_SIZE || '500', 10),
    startDate: process.env.SYNC_START_DATE
      ? new Date(process.env.SYNC_START_DATE)
      : null,
    excludedSalesrepIds: process.env.EXCLUDED_SALESREP_IDS
      ? process.env.EXCLUDED_SALESREP_IDS.split(',').map(id => id.trim()).filter(Boolean)
      : [],
    ownerReverseProtectedSalesrepIds: process.env.OWNER_REVERSE_PROTECTED_SALESREP_IDS
      ? process.env.OWNER_REVERSE_PROTECTED_SALESREP_IDS.split(',').map(id => id.trim()).filter(Boolean)
      : ['175'],
  },
  log: {
    level: process.env.LOG_LEVEL || 'info',
  },
};
