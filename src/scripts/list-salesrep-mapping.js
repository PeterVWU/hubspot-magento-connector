/**
 * Fetches salesrep options from Magento and owners from HubSpot,
 * attempts to match by name, and prints a review table.
 *
 * Usage: node src/scripts/list-salesrep-mapping.js
 */

import 'dotenv/config';
import axios from 'axios';
import { config } from '../config/index.js';

const magento = axios.create({
  baseURL: config.magento.baseUrl,
  headers: { Authorization: `Bearer ${config.magento.token}` },
  timeout: 15000,
});

const hubspot = axios.create({
  baseURL: 'https://api.hubapi.com',
  headers: { Authorization: `Bearer ${config.hubspot.accessToken}` },
  timeout: 15000,
});

async function getMagentoSalesreps() {
  const { data } = await magento.get('/customers/attributes/salesrep_rep_id');
  const options = data.options || [];
  // Filter out blank label (the "-- Please Select --" placeholder)
  return options
    .filter(o => o.value && o.label && o.label.trim())
    .map(o => ({ id: o.value, name: o.label.trim() }));
}

async function getHubSpotOwners() {
  const { data } = await hubspot.get('/crm/v3/owners', { params: { limit: 100 } });
  return (data.results || []).map(o => ({
    id: o.id,
    name: `${o.firstName || ''} ${o.lastName || ''}`.trim(),
    email: o.email,
  }));
}

function normalize(str) {
  return str.toLowerCase().replace(/[^a-z0-9]/g, '');
}

function findBestMatch(repName, owners) {
  const repNorm = normalize(repName);

  // Exact normalized match
  const exact = owners.find(o => normalize(o.name) === repNorm);
  if (exact) return { owner: exact, confidence: 'exact' };

  // One name contains the other
  const partial = owners.find(o => {
    const ownerNorm = normalize(o.name);
    return ownerNorm.includes(repNorm) || repNorm.includes(ownerNorm);
  });
  if (partial) return { owner: partial, confidence: 'partial' };

  // First name only match
  const repFirst = normalize(repName.split(' ')[0]);
  const firstOnly = owners.filter(o => normalize(o.name.split(' ')[0]) === repFirst);
  if (firstOnly.length === 1) return { owner: firstOnly[0], confidence: 'first-name-only' };

  return { owner: null, confidence: 'no-match' };
}

async function run() {
  console.log('Fetching Magento salesreps...');
  const reps = await getMagentoSalesreps();
  console.log(`Found ${reps.length} salesreps\n`);

  console.log('Fetching HubSpot owners...');
  const owners = await getHubSpotOwners();
  console.log(`Found ${owners.length} owners\n`);

  console.log('='.repeat(90));
  console.log('SALESREP MAPPING REVIEW');
  console.log('='.repeat(90));
  console.log(
    'Magento ID'.padEnd(12),
    'Magento Name'.padEnd(28),
    'Confidence'.padEnd(18),
    'HubSpot Owner'.padEnd(28),
    'HubSpot ID',
  );
  console.log('-'.repeat(90));

  const mapping = [];
  for (const rep of reps) {
    const { owner, confidence } = findBestMatch(rep.name, owners);
    mapping.push({ rep, owner, confidence });
    console.log(
      String(rep.id).padEnd(12),
      rep.name.padEnd(28),
      confidence.padEnd(18),
      (owner?.name ?? '???').padEnd(28),
      owner?.id ?? '',
    );
  }

  console.log('-'.repeat(90));

  const unmatched = mapping.filter(m => !m.owner);
  if (unmatched.length) {
    console.log(`\nUnmatched reps (${unmatched.length}):`);
    for (const { rep } of unmatched) {
      console.log(`  - Magento ${rep.id}: "${rep.name}"`);
    }
    console.log('\nAvailable HubSpot owners:');
    for (const o of owners) {
      console.log(`  - ${o.id}: ${o.name} <${o.email}>`);
    }
  }
}

run().catch(err => {
  console.error('Error:', err.response?.data ?? err.message);
  process.exit(1);
});
