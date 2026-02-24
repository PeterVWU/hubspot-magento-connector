import { createReadStream } from 'fs';
import { readdir } from 'fs/promises';
import { join, dirname } from 'path';
import { parse } from 'csv-parse';

/**
 * Reads a CSV file and returns an array of objects keyed by header row.
 * Skips empty rows. Trims whitespace from all values.
 */
export async function readCsv(filePath) {
  return new Promise((resolve, reject) => {
    const records = [];
    createReadStream(filePath)
      .pipe(parse({
        columns: true,       // use first row as keys
        skip_empty_lines: true,
        trim: true,
      }))
      .on('data', (row) => records.push(row))
      .on('end', () => resolve(records))
      .on('error', reject);
  });
}

/**
 * Reads all CSV files matching a prefix pattern (e.g. "data/customers" matches
 * data/customers.csv, data/customers_2020.csv, data/customers_2021.csv, ...).
 * Files are sorted alphabetically before reading so year-based exports merge in order.
 * Returns all rows from all matching files combined into a single array.
 */
export async function readCsvGlob(prefix) {
  const dir = dirname(prefix);
  const base = prefix.slice(dir.length + 1);   // filename prefix without dir
  const entries = await readdir(dir);
  const files = entries
    .filter(f => f.startsWith(base) && f.endsWith('.csv'))
    .sort()
    .map(f => join(dir, f));

  if (files.length === 0) {
    throw new Error(`No CSV files found matching prefix "${prefix}" in "${dir}"`);
  }

  const rows = [];
  for (const file of files) {
    const chunk = await readCsv(file);
    rows.push(...chunk);
  }
  return { rows, files };
}
