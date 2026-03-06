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

Export via `gcloud sql export csv` — runs server-side, writes directly to GCS, no row limit.

**Each query includes a header row** via `UNION ALL` — the exported CSV is self-describing and can be opened directly in Excel/Sheets. The import script reads headers from the file (`columns: true`).

**Text field sanitization** — `gcloud sql export csv` uses MySQL's non-standard backslash escaping, which breaks RFC 4180 CSV parsers when fields contain `"`, `,`, or newlines. All free-text fields are cleaned with:
```
REPLACE(REPLACE(REPLACE(COALESCE(field, ''), CHAR(34), ''), ',', ' '), '\n', ' ')
```
`CHAR(34)` is the ASCII double-quote character — using it instead of `'"'` avoids shell quoting conflicts when the query is passed inline to `gcloud sql export csv`. This removes embedded double-quotes and replaces commas/newlines with spaces. Minor data loss in edge cases (e.g. "Smith, Jones" → "Smith  Jones") is acceptable for CRM import purposes.

### customers.sql
```sql
SELECT 'entity_id', 'email', 'firstname', 'lastname', 'created_at', 'company', 'telephone', 'street', 'city', 'region', 'postcode', 'country_id', 'salesrep_rep_id'
UNION ALL
SELECT
    c.entity_id,
    c.email,
    REPLACE(REPLACE(REPLACE(COALESCE(c.firstname, ''),    CHAR(34), ''), ',', ' '), '\n', ' ') AS firstname,
    REPLACE(REPLACE(REPLACE(COALESCE(c.lastname, ''),     CHAR(34), ''), ',', ' '), '\n', ' ') AS lastname,
    c.created_at,
    REPLACE(REPLACE(REPLACE(COALESCE(addr.company, ''),   CHAR(34), ''), ',', ' '), '\n', ' ') AS company,
    REPLACE(REPLACE(REPLACE(COALESCE(addr.telephone, ''), CHAR(34), ''), ',', ' '), '\n', ' ') AS telephone,
    REPLACE(REPLACE(REPLACE(COALESCE(addr.street, ''),    CHAR(34), ''), ',', ' '), '\n', ' ') AS street,
    REPLACE(REPLACE(REPLACE(COALESCE(addr.city, ''),      CHAR(34), ''), ',', ' '), '\n', ' ') AS city,
    REPLACE(REPLACE(REPLACE(COALESCE(addr.region, ''),    CHAR(34), ''), ',', ' '), '\n', ' ') AS region,
    REPLACE(REPLACE(COALESCE(addr.postcode, ''), CHAR(34), ''), ',', ' ') AS postcode,
    COALESCE(addr.country_id, '') AS country_id,
    COALESCE(salesrep.value, '') AS salesrep_rep_id
FROM customer_entity c
LEFT JOIN customer_address_entity addr ON addr.entity_id = c.default_billing
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
LEFT JOIN customer_entity_int salesrep ON salesrep.entity_id = c.entity_id AND salesrep.attribute_id = 351
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1);
```

### orders.sql
```sql
SELECT 'entity_id', 'increment_id', 'customer_id', 'grand_total', 'status', 'order_currency_code', 'created_at'
UNION ALL
SELECT
    o.entity_id,
    o.increment_id,
    o.customer_id,
    COALESCE(o.grand_total, 0) AS grand_total,
    COALESCE(o.status, '') AS status,
    COALESCE(o.order_currency_code, 'USD') AS order_currency_code,
    o.created_at
FROM sales_order o
INNER JOIN customer_entity c ON c.entity_id = o.customer_id
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1);
```

### order_items.sql
```sql
SELECT 'item_id', 'order_id', 'product_id', 'name', 'sku', 'qty_ordered', 'row_total_incl_tax', 'price', 'product_type'
UNION ALL
SELECT
    oi.item_id,
    oi.order_id,
    oi.product_id,
    REPLACE(REPLACE(REPLACE(COALESCE(child.name, oi.name, ''), CHAR(34), ''), ',', ' '), '\n', ' ') AS name,
    REPLACE(REPLACE(COALESCE(oi.sku, ''), CHAR(34), ''), ',', ' ') AS sku,
    COALESCE(oi.qty_ordered, 0) AS qty_ordered,
    COALESCE(oi.row_total_incl_tax, 0) AS row_total_incl_tax,
    COALESCE(oi.price, 0) AS price,
    COALESCE(oi.product_type, '') AS product_type
FROM sales_order_item oi
LEFT JOIN sales_order_item child ON child.parent_item_id = oi.item_id
INNER JOIN sales_order o ON o.entity_id = oi.order_id
WHERE oi.parent_item_id IS NULL;
```

## Export Commands

The queries use `CHAR(34)` for the double-quote character so they can be safely passed inline inside `--query="..."` without shell quoting conflicts.

```bash
gcloud sql export csv vwu-vusa-prod-db-replica gs://vwudatabasedump/hubspot-import/customers.csv \
  --database=vusa_db0 --query="SELECT 'entity_id','email','firstname','lastname','created_at','company','telephone','street','city','region','postcode','country_id','salesrep_rep_id' UNION ALL SELECT c.entity_id, c.email, REPLACE(REPLACE(REPLACE(COALESCE(c.firstname,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(REPLACE(COALESCE(c.lastname,''),CHAR(34),''),',',' '),'\n',' '), c.created_at, REPLACE(REPLACE(REPLACE(COALESCE(addr.company,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(REPLACE(COALESCE(addr.telephone,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(REPLACE(COALESCE(addr.street,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(REPLACE(COALESCE(addr.city,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(REPLACE(COALESCE(addr.region,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(COALESCE(addr.postcode,''),CHAR(34),''),',',' '), COALESCE(addr.country_id,''), COALESCE(salesrep.value,'') FROM customer_entity c LEFT JOIN customer_address_entity addr ON addr.entity_id = c.default_billing LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320 LEFT JOIN customer_entity_int salesrep ON salesrep.entity_id = c.entity_id AND salesrep.attribute_id = 351 WHERE c.group_id != 5 AND (fraud.value IS NULL OR fraud.value != 1);"

gcloud sql export csv vwu-vusa-prod-db-replica gs://vwudatabasedump/hubspot-import/orders.csv \
  --database=vusa_db0 --query="SELECT 'entity_id','increment_id','customer_id','grand_total','status','order_currency_code','created_at' UNION ALL SELECT o.entity_id, o.increment_id, o.customer_id, COALESCE(o.grand_total,0), COALESCE(o.status,''), COALESCE(o.order_currency_code,'USD'), o.created_at FROM sales_order o INNER JOIN customer_entity c ON c.entity_id = o.customer_id LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320 WHERE c.group_id != 5 AND (fraud.value IS NULL OR fraud.value != 1);"

gcloud sql export csv vwu-vusa-prod-db-replica gs://vwudatabasedump/hubspot-import/order_items.csv \
  --database=vusa_db0 --query="SELECT 'item_id','order_id','product_id','name','sku','qty_ordered','row_total_incl_tax','price','product_type' UNION ALL SELECT oi.item_id, oi.order_id, oi.product_id, REPLACE(REPLACE(REPLACE(COALESCE(child.name,oi.name,''),CHAR(34),''),',',' '),'\n',' '), REPLACE(REPLACE(COALESCE(oi.sku,''),CHAR(34),''),',',' '), COALESCE(oi.qty_ordered,0), COALESCE(oi.row_total_incl_tax,0), COALESCE(oi.price,0), COALESCE(oi.product_type,'') FROM sales_order_item oi LEFT JOIN sales_order_item child ON child.parent_item_id = oi.item_id INNER JOIN sales_order o ON o.entity_id = oi.order_id WHERE oi.parent_item_id IS NULL;"

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

**Column mapping:** CSV files now include a header row, so `readCsvGlob()` is called without a columns argument (defaults to `columns: true` — read headers from file). The hardcoded `CUSTOMERS_COLUMNS`, `ORDERS_COLUMNS`, `ITEMS_COLUMNS` arrays are removed.

**Reuses without modification:**
- `src/mappers/customer.mapper.js`
- `src/mappers/order.mapper.js`
- `src/sync/orders.js` → `syncSingleOrder()`
- `src/api/hubspot.js`
- `src/db/sync-state.js`

## CSV Parser (`src/utils/csv.js`)

With clean SQL output the parser simplifies significantly:

- **Remove** `fixGcloudSplitRecords()` Transform — no longer needed since text fields are sanitized
- **Remove** `relax_quotes: true` and `relax_column_count: true` — these masked bad data; strict RFC 4180 parsing is correct now and will fail fast on any remaining issues
