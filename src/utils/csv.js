import { createReadStream } from 'fs';
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
