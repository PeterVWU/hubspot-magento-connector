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

## SQL Queries — Year-by-Year Export

Run each query once per year, export to a file named `customers_YYYY.csv`, `orders_YYYY.csv`, `order_items_YYYY.csv`. The import script automatically reads all matching files and merges them.

Replace `YYYY` with the target year (e.g. 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025).

### customers_YYYY.sql
```sql
SELECT
    c.entity_id,
    c.email,
    c.firstname,
    c.lastname,
    c.created_at,
    addr.company,
    addr.telephone,
    addr.street,
    addr.city,
    addr.region,
    addr.postcode,
    addr.country_id
FROM customer_entity c
LEFT JOIN customer_address_entity addr ON addr.entity_id = c.default_billing
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
LEFT JOIN customer_entity_int rep ON rep.entity_id = c.entity_id AND rep.attribute_id = 351
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1)
    AND (rep.value IS NULL OR rep.value NOT IN (81, 97, 143, 121, 73, 130, 129, 128, 146))
    AND c.created_at >= 'YYYY-01-01'
    AND c.created_at <  'YYYY+1-01-01'
ORDER BY c.entity_id;
```

### orders_YYYY.sql
```sql
SELECT
    o.entity_id,
    o.increment_id,
    o.customer_id,
    o.grand_total,
    o.status,
    o.order_currency_code,
    o.created_at
FROM sales_order o
INNER JOIN customer_entity c ON c.entity_id = o.customer_id
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
LEFT JOIN customer_entity_int rep ON rep.entity_id = c.entity_id AND rep.attribute_id = 351
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1)
    AND (rep.value IS NULL OR rep.value NOT IN (81, 97, 143, 121, 73, 130, 129, 128, 146))
    AND o.created_at >= 'YYYY-01-01'
    AND o.created_at <  'YYYY+1-01-01'
ORDER BY o.entity_id;
```

### order_items_YYYY.sql
```sql
SELECT
    oi.item_id,
    oi.order_id,
    oi.product_id,
    oi.name,
    oi.sku,
    oi.qty_ordered,
    oi.row_total_incl_tax,
    oi.price,
    oi.product_type
FROM sales_order_item oi
INNER JOIN sales_order o ON o.entity_id = oi.order_id
INNER JOIN customer_entity c ON c.entity_id = o.customer_id
LEFT JOIN customer_entity_int fraud ON fraud.entity_id = c.entity_id AND fraud.attribute_id = 320
LEFT JOIN customer_entity_int rep ON rep.entity_id = c.entity_id AND rep.attribute_id = 351
WHERE
    c.group_id != 5
    AND (fraud.value IS NULL OR fraud.value != 1)
    AND (rep.value IS NULL OR rep.value NOT IN (81, 97, 143, 121, 73, 130, 129, 128, 146))
    AND o.created_at >= 'YYYY-01-01'
    AND o.created_at <  'YYYY+1-01-01'
ORDER BY oi.order_id, oi.item_id;
```

## File Naming

Place all exported files in `data/`:

```
data/customers_2018.csv
data/customers_2019.csv
...
data/customers_2025.csv

data/orders_2018.csv
...
data/orders_2025.csv

data/order_items_2018.csv
...
data/order_items_2025.csv
```

The import script matches by prefix (`customers`, `orders`, `order_items`) so any files starting with those names and ending in `.csv` will be picked up automatically, sorted alphabetically before merging.

A single `data/customers.csv` (no year suffix) also works if you have a complete export.

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
