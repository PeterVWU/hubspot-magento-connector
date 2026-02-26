# CSV Export Fix Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the fragile gcloud-split-record workaround in `csv.js` with a clean, standard CSV parser that reads header rows from the file.

**Architecture:** The root cause (non-standard backslash escaping from `gcloud sql export csv`) is eliminated in the SQL queries themselves via `REPLACE()` sanitization and a `UNION ALL` header row. The Node.js side simplifies: remove the `fixGcloudSplitRecords` Transform, remove `relax_quotes`/`relax_column_count` parser flags, and remove the hardcoded column-name arrays from the import script.

**Tech Stack:** Node.js ESM, `csv-parse` v6

---

### Task 1: Simplify `src/utils/csv.js`

**Files:**
- Modify: `src/utils/csv.js`

**Step 1: Delete the `fixGcloudSplitRecords` function**

Remove lines 1–75 (the entire `fixGcloudSplitRecords()` function including its block comment). The file should start at the `readCsv` JSDoc comment.

**Step 2: Remove the pipe and relax options from `readCsv`**

In `readCsv()`, change:
```js
createReadStream(filePath)
  .pipe(fixGcloudSplitRecords())
  .pipe(parse({
    columns,
    skip_empty_lines: true,
    trim: true,
    relax_quotes: true,
    relax_column_count: true,
  }))
```
to:
```js
createReadStream(filePath)
  .pipe(parse({
    columns,
    skip_empty_lines: true,
    trim: true,
  }))
```

**Step 3: Remove unused imports**

Remove the `Transform` import from `'stream'` at the top — it's only used by the deleted function. The import line is:
```js
import { Transform } from 'stream';
```

**Step 4: Smoke-test the simplified parser**

Create a minimal sample file `data/test-csv-parse.csv`:
```
entity_id,email,firstname,lastname
1,alice@example.com,Alice,Smith
2,bob@example.com,Bob,Jones with "quotes" removed
```

Run:
```bash
node --input-type=module <<'EOF'
import { readCsv } from './src/utils/csv.js';
const rows = await readCsv('data/test-csv-parse.csv');
console.log(rows);
EOF
```

Expected output — two objects with the correct field names:
```
[
  { entity_id: '1', email: 'alice@example.com', firstname: 'Alice', lastname: 'Smith' },
  { entity_id: '2', email: 'bob@example.com', firstname: 'Bob', lastname: 'Jones with  quotes  removed' }
]
```

**Step 5: Delete the test file and commit**

```bash
rm data/test-csv-parse.csv
git add src/utils/csv.js
git commit -m "refactor: remove fixGcloudSplitRecords — SQL now sanitizes text fields"
```

---

### Task 2: Update `src/scripts/import-csv.js`

**Files:**
- Modify: `src/scripts/import-csv.js`

**Step 1: Remove the hardcoded column-name constants**

Delete these three lines (around line 25–27):
```js
const CUSTOMERS_COLUMNS = ['entity_id','email','firstname','lastname','created_at','company','telephone','street','city','region','postcode','country_id','salesrep_rep_id'];
const ORDERS_COLUMNS    = ['entity_id','increment_id','customer_id','grand_total','status','order_currency_code','created_at'];
const ITEMS_COLUMNS     = ['item_id','order_id','product_id','name','sku','qty_ordered','row_total_incl_tax','price','product_type'];
```

**Step 2: Update the `readCsvGlob` calls in `run()`**

Change (around line 172–174):
```js
readCsvGlob(CUSTOMERS_PREFIX, CUSTOMERS_COLUMNS),
readCsvGlob(ORDERS_PREFIX,    ORDERS_COLUMNS),
readCsvGlob(ITEMS_PREFIX,     ITEMS_COLUMNS),
```
to:
```js
readCsvGlob(CUSTOMERS_PREFIX),
readCsvGlob(ORDERS_PREFIX),
readCsvGlob(ITEMS_PREFIX),
```

(`readCsvGlob` already defaults `columns` to `true`, which tells `csv-parse` to read the first row as headers.)

**Step 3: Smoke-test with a real sample CSV**

Create `data/customers-sample.csv` with a few rows that match the new format (header + data from the updated SQL queries), e.g.:

```
entity_id,email,firstname,lastname,created_at,company,telephone,street,city,region,postcode,country_id,salesrep_rep_id
99999,test@example.com,Test,User,2024-01-01 00:00:00,Acme Corp,555-1234,123 Main St,Anytown,CA,90210,US,
```

Run the import in dry-run mode by just loading the CSV (don't push to HubSpot):
```bash
node --input-type=module <<'EOF'
import { readCsvGlob } from './src/utils/csv.js';
const { rows, files } = await readCsvGlob('data/customers-sample');
console.log('files:', files);
console.log('rows:', rows);
EOF
```

Expected: the single row object with all 13 named fields correctly populated.

**Step 4: Delete test file and commit**

```bash
rm data/customers-sample.csv
git add src/scripts/import-csv.js
git commit -m "refactor: read column names from CSV header row instead of hardcoded arrays"
```

---

### Task 3: Update the `readCsvGlob` JSDoc

**Files:**
- Modify: `src/utils/csv.js`

The existing JSDoc on `readCsvGlob` says *"Pass columns as an array for headerless files (gcloud sql export csv output)"*. Update it to reflect that files now include headers:

Change:
```js
/**
 * Reads all CSV files matching a prefix (e.g. "data/customers" matches
 * data/customers.csv, data/customers_2020.csv, ...).
 * Files are sorted alphabetically before reading.
 * Pass columns as an array for headerless files (gcloud sql export csv output).
 */
```
to:
```js
/**
 * Reads all CSV files matching a prefix (e.g. "data/customers" matches
 * data/customers.csv, data/customers_2020.csv, ...).
 * Files are sorted alphabetically before reading.
 * columns defaults to true (read header row from file). Pass a string array
 * only for legacy headerless files.
 */
```

**Commit:**
```bash
git add src/utils/csv.js
git commit -m "docs: update readCsvGlob JSDoc — CSV files now include header row"
```

---

## Verification Checklist

Before calling this done:
- [ ] `src/utils/csv.js` contains no reference to `fixGcloudSplitRecords`, `relax_quotes`, or `relax_column_count`
- [ ] `import { Transform } from 'stream'` is removed from `csv.js`
- [ ] `src/scripts/import-csv.js` contains no `CUSTOMERS_COLUMNS`, `ORDERS_COLUMNS`, or `ITEMS_COLUMNS`
- [ ] All three `readCsvGlob()` calls pass no second argument

## New gcloud Export Queries

The SQL to use in `gcloud sql export csv` is in `docs/plans/2026-02-23-csv-bulk-import-design.md` under "SQL Queries". Run those queries (not the old ones) to generate the CSV files. The key changes from the old queries:
- Header row prepended via `UNION ALL`
- All text fields wrapped with `REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(field, ''), '"', ''), ',', ' '), '\r', ''), '\n', ' ')`
- `ORDER BY` removed (not needed for import; avoids UNION ALL/ORDER BY conflict)
