# Magento-HubSpot Sync App Design

## Overview

Node.js background service that syncs data from Magento 2 to HubSpot. Customers and products are initially bulk-imported via CSV. The app handles all order sync and ongoing incremental sync for new/updated customers, products, and orders.

## Tech Stack

- **Runtime:** Node.js (ES Modules)
- **HTTP Client:** axios
- **Scheduler:** node-cron
- **Logging:** winston (structured JSON, file + console)
- **Config:** dotenv
- **Database:** PostgreSQL via pg (node-postgres)

No web framework - this is a background sync service.

## Authentication

- **Magento:** Bearer token (Admin integration token)
- **HubSpot:** Private App access token

## Module Structure

```
src/
├── index.js                  # Entry point - starts scheduler
├── config/
│   └── index.js              # Loads env vars, validates config
├── api/
│   ├── magento.js            # Magento REST API client
│   └── hubspot.js            # HubSpot CRM API client
├── sync/
│   ├── customers.js          # Customer → Contact sync
│   ├── products.js           # Product → Product sync
│   ├── orders.js             # Order → Deal + Line Items sync
│   ├── scheduler.js          # Cron job orchestration
│   └── retry-queue.js        # Failed sync retry with exponential backoff
├── mappers/
│   ├── customer.mapper.js    # Magento customer → HubSpot contact fields
│   ├── product.mapper.js     # Magento product → HubSpot product fields
│   └── order.mapper.js       # Magento order → HubSpot deal + line item fields
├── db/
│   ├── index.js              # PostgreSQL connection pool
│   ├── migrations.js         # Schema setup
│   └── sync-state.js         # Sync tracking queries
└── utils/
    └── logger.js             # Winston logger config
```

## Sync Strategy

**Trigger:** Polling via cron (configurable interval, default every 5 minutes).

**Sync Order:** Products → Customers → Orders (dependencies flow left to right).

**Pattern:** Search-before-upsert. For each entity:
1. Query Magento for records updated since last sync
2. For each record, search HubSpot for existing match (by SKU, email, or order number)
3. Create or update in HubSpot
4. Store magento_id ↔ hubspot_id mapping in PostgreSQL

**Batching:** HubSpot batch APIs, chunks of 100.

**Rate Limiting:** Token bucket for HubSpot (100 requests / 10 seconds).

## Customer Filter

Customers are filtered by `salesrep_rep_id` (custom attribute in Magento):

- Config: `EXCLUDED_SALESREP_IDS=5,12,34` (comma-separated)
- Sync IF: `salesrep_rep_id` is NULL/empty OR not in excluded list
- Skip IF: `salesrep_rep_id` is in the excluded list
- Applied server-side via Magento searchCriteria filters

## Field Mappings

### Customer → HubSpot Contact

| Magento Field | HubSpot Property |
|---|---|
| email | email |
| firstname | firstname |
| lastname | lastname |
| addresses[billing].company | company |
| addresses[billing].telephone | phone |
| addresses[billing].street | address |
| addresses[billing].city | city |
| addresses[billing].region.region | state |
| addresses[billing].postcode | zip |
| addresses[billing].country_id | country |

### Product → HubSpot Product

| Magento Field | HubSpot Property |
|---|---|
| name | name |
| sku | hs_sku |
| price | price |
| custom_attributes.description | description |

### Order → HubSpot Deal

| Magento Field | HubSpot Property |
|---|---|
| increment_id | dealname ("Order #" + increment_id) |
| grand_total | amount |
| status | dealstage (mapped to pipeline) |
| created_at | createdate |
| customer_email | contact association |

### Order Item → HubSpot Line Item

| Magento Field | HubSpot Property |
|---|---|
| sku | hs_sku |
| name | name |
| qty_ordered | quantity |
| row_total_incl_tax | price |
| product_id | hs_product_id (via mapping table) |

### Deal Pipeline Stage Mapping

| Magento Status | HubSpot Stage |
|---|---|
| pending | Checkout Completed |
| processing | Processing |
| complete | Completed |
| canceled / closed | Cancelled |
| holded | On Hold |

## Database Schema (PostgreSQL)

### sync_state
Tracks last successful sync per entity type.

| Column | Type | Notes |
|---|---|---|
| entity_type | VARCHAR PK | 'customer', 'product', 'order' |
| last_synced_at | TIMESTAMP | Last successful sync time |
| updated_at | TIMESTAMP | Row update time |

### entity_mapping
Maps Magento IDs to HubSpot IDs.

| Column | Type | Notes |
|---|---|---|
| id | SERIAL PK | |
| entity_type | VARCHAR | 'customer', 'product', 'order', 'line_item' |
| magento_id | VARCHAR | Magento entity ID |
| hubspot_id | VARCHAR | HubSpot object ID |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| | UNIQUE | (entity_type, magento_id) |

### sync_retry_queue
Failed operations for retry with exponential backoff.

| Column | Type | Notes |
|---|---|---|
| id | SERIAL PK | |
| entity_type | VARCHAR | |
| magento_id | VARCHAR | |
| operation | VARCHAR | 'create' or 'update' |
| payload | JSONB | Full sync payload |
| error_message | TEXT | Last error |
| attempts | INT | Default 0 |
| max_attempts | INT | Default 5 |
| next_retry_at | TIMESTAMP | Next retry time |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### sync_log
Persistent log for debugging sync runs.

| Column | Type | Notes |
|---|---|---|
| id | SERIAL PK | |
| run_id | UUID | Groups logs per sync run |
| entity_type | VARCHAR | |
| level | VARCHAR | 'info', 'error', 'warn' |
| message | TEXT | |
| metadata | JSONB | Additional context |
| created_at | TIMESTAMP | |

## Retry Queue

- Exponential backoff: 1min → 5min → 15min → 1hr → 4hr
- Max 5 attempts (configurable)
- Processed on its own cron interval
- Each attempt logged for debugging

## Data Flow Per Sync Cycle

1. Scheduler triggers sync job
2. Read `last_synced_at` per entity from `sync_state`
3. **Products:** Fetch from Magento (updated_at filter) → map → search HubSpot by SKU → create/update → store mapping
4. **Customers:** Fetch from Magento (updated_at filter + salesrep exclusion) → map → search HubSpot by email → create/update → store mapping
5. **Orders:** Fetch from Magento (updated_at filter) → map deal + line items → look up contact/product IDs from mapping → search HubSpot by order number → create/update deal + line items → create associations (contact↔deal, line_item↔deal) → store mapping
6. Update `last_synced_at` per entity
7. Process retry queue
