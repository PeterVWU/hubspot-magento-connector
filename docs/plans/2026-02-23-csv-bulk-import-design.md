# CSV Bulk Import Design

**Date:** 2026-02-23
**Feature:** One-time bulk import of existing Magento customers and orders via CSV

## Overview

The live sync (API polling) handles new/updated records going forward. This feature handles the historical backfill: export data from Magento's database, drop 3 CSV files into the project, run one script.

## CSV Files

All files go in `data/` at the project root.

### `data/customers.csv`
| Column | Source |
|---|---|
| entity_id | customer_entity.entity_id |
| email | customer_entity.email |
| firstname | customer_entity.firstname |
| lastname | customer_entity.lastname |
| created_at | customer_entity.created_at |
| company | customer_address_entity.company |
| telephone | customer_address_entity.telephone |
| street | customer_address_entity.street |
| city | customer_address_entity.city |
| region | customer_address_entity.region |
| postcode | customer_address_entity.postcode |
| country_id | customer_address_entity.country_id |
| salesrep_rep_id | customer_entity_int (attribute_id 351) |

### `data/orders.csv`
| Column | Source |
|---|---|
| entity_id | sales_order.entity_id |
| increment_id | sales_order.increment_id |
| customer_id | sales_order.customer_id |
| grand_total | sales_order.grand_total |
| status | sales_order.status |
| order_currency_code | sales_order.order_currency_code |
| created_at | sales_order.created_at |

### `data/order_items.csv`
| Column | Source |
|---|---|
| item_id | sales_order_item.item_id |
| order_id | sales_order_item.order_id (= orders.entity_id) |
| product_id | sales_order_item.product_id |
| name | sales_order_item.name |
| sku | sales_order_item.sku |
| qty_ordered | sales_order_item.qty_ordered |
| row_total_incl_tax | sales_order_item.row_total_incl_tax |
| price | sales_order_item.price |
| product_type | sales_order_item.product_type |

## SQL Queries

Export via `gcloud sql export csv` — runs server-side, writes directly to GCS, no row limit. Files have no header row; column names are defined in the import script.

### customers.sql
```sql
SELECT
    c.entity_id,
    c.email,
    c.firstname,
    c.lastname,
    c.created_at,
    COALESCE(addr.company, '') AS company,
    COALESCE(addr.telephone, '') AS telephone,
    COALESCE(REPLACE(addr.street, '\n', ' '), '') AS street,
    COALESCE(addr.city, '') AS city,
    COALESCE(addr.region, '') AS region,
    COALESCE(addr.postcode, '') AS postcode,
    COALESCE(addr.country_id, '') AS country_id,
    COALESCE(salesrep.value, '') AS salesrep_rep_id
FROM customer_entity c
LEFT JOIN customer_address_entity addr ON addr.entity_id = c.default_billing
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
LEFT JOIN customer_entity_int salesrep ON salesrep.entity_id = c.entity_id AND salesrep.attribute_id = 351
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1)
ORDER BY c.entity_id;
```

### orders.sql
```sql
SELECT
    o.entity_id,
    o.increment_id,
    o.customer_id,
    COALESCE(o.grand_total, 0) AS grand_total,
    o.status,
    COALESCE(o.order_currency_code, 'USD') AS order_currency_code,
    o.created_at
FROM sales_order o
INNER JOIN customer_entity c ON c.entity_id = o.customer_id
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1)
ORDER BY o.entity_id;
```

### order_items.sql
```sql
SELECT
    oi.item_id,
    oi.order_id,
    oi.product_id,
    oi.name,
    COALESCE(oi.sku, '') AS sku,
    COALESCE(oi.qty_ordered, 0) AS qty_ordered,
    COALESCE(oi.row_total_incl_tax, 0) AS row_total_incl_tax,
    COALESCE(oi.price, 0) AS price,
    oi.product_type
FROM sales_order_item oi
INNER JOIN sales_order o ON o.entity_id = oi.order_id
ORDER BY oi.order_id, oi.item_id;
```

## Export Commands

```bash
gcloud sql export csv  vwu-vusa-prod-db-replica gs://vwudatabasedump/hubspot-import/customers.csv \
  --database=vusa_db0 --query="<customers.sql above>"

gcloud sql export csv vwu-vusa-prod-db-replica gs://vwudatabasedump/hubspot-import/orders.csv \
  --database=vusa_db0 --query="<orders.sql above>"

gcloud sql export csv vwu-vusa-prod-db-replica gs://vwudatabasedump/hubspot-import/order_items.csv \
  --database=vusa_db0 --query="<order_items.sql above>"

# Download all three to data/
gsutil cp "gs://vwudatabasedump/hubspot-import/*.csv" data/
```

## Import Script

**File:** `src/scripts/import-csv.js`

**Flow:**
1. Run DB migrations
2. Find all `data/customers*.csv` files, merge rows, run customer upsert logic for each row
3. Find all `data/orders*.csv` + `data/order_items*.csv` files, merge rows, group items by order_id, call `syncSingleOrder()` for each order
4. Log running totals (processed / created / updated / failed) to console with live progress bar
5. Print final summary

**No retry queue** — this is a one-time operation. Failures are logged and can be re-run; the script is fully idempotent via the existing DB mapping checks.

**Reuses without modification:**
- `src/mappers/customer.mapper.js`
- `src/mappers/order.mapper.js`
- `src/sync/orders.js` → `syncSingleOrder()`
- `src/api/hubspot.js`
- `src/db/sync-state.js`
