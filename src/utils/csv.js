import { createReadStream } from 'fs';
import { readdir } from 'fs/promises';
import { join, dirname } from 'path';
import { parse } from 'csv-parse';

/**
 * Reads a CSV file and returns an array of objects.
 * Pass columns as an array of strings for headerless files (e.g. gcloud sql export csv output).
 * Omit columns (or pass true) to use the first row as headers.
 */
export async function readCsv(filePath, columns = true) {
  return new Promise((resolve, reject) => {
    const records = [];
    createReadStream(filePath)
      .pipe(parse({
        columns,
        skip_empty_lines: true,
        trim: true,
        relax_quotes: true,
        relax_column_count: true,
      }))
      .on('data', (row) => records.push(row))
      .on('end', () => resolve(records))
      .on('error', reject);
  });
}

/**
 * Reads all CSV files matching a prefix (e.g. "data/customers" matches
 * data/customers.csv, data/customers_2020.csv, ...).
 * Files are sorted alphabetically before reading.
 * Pass columns as an array for headerless files (gcloud sql export csv output).
 */
export async function readCsvGlob(prefix, columns = true) {
  const dir = dirname(prefix);
  const base = prefix.slice(dir.length + 1);
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
    const chunk = await readCsv(file, columns);
    rows.push(...chunk);
  }
  return { rows, files };
}
