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
  },
  hubspot: {
    accessToken: process.env.HUBSPOT_ACCESS_TOKEN,
    batchSize: parseInt(process.env.HUBSPOT_BATCH_SIZE || '100', 10),
  },
  db: {
    connectionString: process.env.DATABASE_URL,
  },
  sync: {
    intervalMinutes: parseInt(process.env.SYNC_INTERVAL_MINUTES || '5', 10),
    startDate: process.env.SYNC_START_DATE
      ? new Date(process.env.SYNC_START_DATE)
      : null,
    excludedSalesrepIds: process.env.EXCLUDED_SALESREP_IDS
      ? process.env.EXCLUDED_SALESREP_IDS.split(',').map(id => id.trim()).filter(Boolean)
      : [],
  },
  log: {
    level: process.env.LOG_LEVEL || 'info',
  },
};
