# HubSpot-Magento Connector

A Node.js background service that syncs data from a Magento 2 store to HubSpot CRM. It polls Magento for new/updated records and pushes them to HubSpot as contacts, deals, and line items on a configurable interval.

## What it syncs

- **Products** (Magento catalog items -> HubSpot products)
- **Customers** (Magento customers -> HubSpot contacts)
- **Orders** (Magento orders -> HubSpot deals with line items and contact associations)

Sync runs in dependency order: products first, then customers, then orders.

## Requirements

- Node.js 18+
- PostgreSQL 16 (used for sync state and ID mappings)
- Docker + Docker Compose (recommended for running)

## Configuration

Copy `.env.example` to `.env` and fill in the required values:

```env
# Required
MAGENTO_BASE_URL=https://your-magento-store.com
MAGENTO_TOKEN=your_magento_bearer_token
HUBSPOT_ACCESS_TOKEN=your_hubspot_private_app_token
DATABASE_URL=postgresql://hubspot_sync:changeme@localhost:5433/hubspot_sync

# Optional
SYNC_INTERVAL_MINUTES=5       # How often to poll (default: 5)
SYNC_START_DATE=2024-01-01    # Only sync records updated after this date
EXCLUDED_SALESREP_IDS=1,2,3   # Comma-separated Magento sales rep IDs to exclude
MAGENTO_PAGE_SIZE=100         # Magento API page size (default: 100)
HUBSPOT_BATCH_SIZE=100        # HubSpot batch upsert size (default: 100)
MAX_RECORDS_PER_SYNC=0        # Max records per entity per cycle, 0=unlimited (default: 0)
LOG_LEVEL=info                # Logging level (default: info)

# Timeouts
MAGENTO_TIMEOUT_MS=120000     # Per-request Magento timeout in ms (default: 120000)
HUBSPOT_TIMEOUT_MS=30000      # Per-request HubSpot timeout in ms (default: 30000)
HUBSPOT_MAX_RETRIES=3         # Max 429 rate-limit retries per request (default: 3)
SYNC_TIMEOUT_MINUTES=10       # Max duration for a full sync cycle (default: 10)
```

## Running with Docker Compose (recommended)

```bash
# Start the app and database
docker-compose up -d

# View logs
docker-compose logs -f app
```

The database is exposed on port `5433` locally.

## Running locally

```bash
npm install
npm start          # Run once and poll on interval
npm run dev        # Run with --watch for auto-restart on file changes
```

## One-off scripts

```bash
# Import customers from CSV file
npm run import-csv -- path/to/customers.csv
```

## Project structure

```
src/
  index.js          # Entry point, sets up cron schedule
  config/           # Environment config with validation
  api/              # Magento and HubSpot API clients
  mappers/          # Field transformation functions
  sync/             # Entity sync modules (products, customers, orders)
  db/               # PostgreSQL query functions (sync state, ID mappings)
  scripts/          # One-off utility scripts
```
