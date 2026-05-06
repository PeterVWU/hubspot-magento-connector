import axios from 'axios';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';
import { withTimeout } from '../utils/timeout.js';

const RATE_LIMIT_PER_SECOND = 9;
const SEARCH_RATE_LIMIT_PER_SECOND = 4;

let lastRequestTime = 0;
let lastSearchTime = 0;

async function rateLimitDelay(isSearch = false) {
  const now = Date.now();
  const minInterval = isSearch
    ? 1000 / SEARCH_RATE_LIMIT_PER_SECOND
    : 1000 / RATE_LIMIT_PER_SECOND;

  if (isSearch) {
    const elapsed = now - lastSearchTime;
    if (elapsed < minInterval) {
      await new Promise(resolve => setTimeout(resolve, minInterval - elapsed));
    }
    lastSearchTime = Date.now();
  } else {
    const elapsed = now - lastRequestTime;
    if (elapsed < minInterval) {
      await new Promise(resolve => setTimeout(resolve, minInterval - elapsed));
    }
    lastRequestTime = Date.now();
  }
}

const client = axios.create({
  baseURL: 'https://api.hubapi.com',
  headers: {
    Authorization: `Bearer ${config.hubspot.accessToken}`,
    'Content-Type': 'application/json',
  },
});

client.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const url = error.config?.url;

    if (status === 429) {
      const retries = (error.config._retryCount || 0) + 1;
      if (retries > config.hubspot.maxRetries) {
        logger.error('HubSpot rate limit retries exhausted', { url, retries });
        throw error;
      }
      error.config._retryCount = retries;
      const retryAfter = parseInt(error.response.headers['retry-after'] || '10', 10);
      logger.warn('HubSpot rate limited, retrying', { url, retryAfter, attempt: retries });
      await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      return client.request(error.config);
    }

    // 409 = already exists; callers handle it, no need to log as error
    if (status !== 409) {
      logger.error('HubSpot API error', {
        status,
        url,
        data: JSON.stringify(error.response?.data),
      });
    }
    throw error;
  },
);

function hsRequest(fn) {
  return withTimeout(fn, config.hubspot.timeout);
}

// --- Search Operations ---

export async function searchContacts(email) {
  await rateLimitDelay(true);
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/contacts/search', {
    filterGroups: [{
      filters: [{ propertyName: 'email', operator: 'EQ', value: email }],
    }],
    properties: ['email', 'firstname', 'lastname'],
    limit: 1,
  }, { signal }));
  return data.results[0] || null;
}

export async function searchProductBySku(sku) {
  await rateLimitDelay(true);
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/products/search', {
    filterGroups: [{
      filters: [{ propertyName: 'hs_sku', operator: 'EQ', value: sku }],
    }],
    properties: ['name', 'hs_sku', 'price'],
    limit: 1,
  }, { signal }));
  return data.results[0] || null;
}

export async function searchDealByOrderNumber(orderNumber) {
  await rateLimitDelay(true);
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/deals/search', {
    filterGroups: [{
      filters: [{ propertyName: 'order_number', operator: 'EQ', value: orderNumber }],
    }],
    properties: ['dealname', 'order_number', 'dealstage', 'amount'],
    limit: 1,
  }, { signal }));
  return data.results[0] || null;
}

/**
 * Returns contacts modified since the given Date, including their current
 * hubspot_owner_id. Paginated; `lastmodifieddate` is a millisecond epoch in
 * the HubSpot search API.
 */
export async function searchContactsModifiedSince(since) {
  const results = [];
  let after;
  const sinceMs = since.getTime();
  do {
    await rateLimitDelay(true);
    const body = {
      filterGroups: [{
        filters: [{ propertyName: 'lastmodifieddate', operator: 'GTE', value: String(sinceMs) }],
      }],
      properties: ['email', 'hubspot_owner_id', 'lastmodifieddate'],
      sorts: [{ propertyName: 'lastmodifieddate', direction: 'ASCENDING' }],
      limit: 100,
      ...(after ? { after } : {}),
    };
    try {
      const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/contacts/search', body, { signal }));
      results.push(...(data.results || []));
      after = data.paging?.next?.after;
    } catch (err) {
      // HubSpot search caps at 10,000 results; a 400 mid-pagination means we've
      // hit that limit. Return what we have so the caller can advance its cursor.
      if (err.response?.status === 400 && after) {
        logger.warn('HubSpot search 10k cap reached, returning partial results', {
          collected: results.length,
        });
        break;
      }
      throw err;
    }
  } while (after);
  return results;
}

// --- Single Create/Update Operations ---

export async function createContact(properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/contacts', { properties }, { signal }));
  return data;
}

export async function updateContact(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.patch(`/crm/v3/objects/contacts/${hubspotId}`, { properties }, { signal }));
  return data;
}

export async function createProduct(properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/products', { properties }, { signal }));
  return data;
}

export async function updateProduct(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.patch(`/crm/v3/objects/products/${hubspotId}`, { properties }, { signal }));
  return data;
}

export async function createDeal(properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/deals', { properties }, { signal }));
  return data;
}

export async function updateDeal(hubspotId, properties) {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.patch(`/crm/v3/objects/deals/${hubspotId}`, { properties }, { signal }));
  return data;
}

// --- Pipeline ---

export async function getDealPipelines() {
  await rateLimitDelay();
  const { data } = await hsRequest((signal) => client.get('/crm/v3/pipelines/deals', { signal }));
  return data.results || [];
}

// --- Properties ---

export async function createContactProperty(name, label, type = 'string', fieldType = 'text') {
  await rateLimitDelay();
  try {
    const { data } = await hsRequest((signal) => client.post('/crm/v3/properties/contacts', {
      name, label, type, fieldType, groupName: 'contactinformation',
    }, { signal }));
    return data;
  } catch (err) {
    if (err.response?.status === 409) {
      logger.debug(`Contact property "${name}" already exists`);
      return null;
    }
    throw err;
  }
}

export async function createDealProperty(name, label, type = 'string', fieldType = 'text') {
  await rateLimitDelay();
  try {
    const { data } = await hsRequest((signal) => client.post('/crm/v3/properties/deals', {
      name, label, type, fieldType, groupName: 'dealinformation',
    }, { signal }));
    return data;
  } catch (err) {
    if (err.response?.status === 409) {
      logger.debug(`Deal property "${name}" already exists`);
      return null;
    }
    throw err;
  }
}

// --- Batch Operations ---

export async function batchCreateLineItems(inputs) {
  const batchSize = config.hubspot.batchSize;
  const results = [];

  for (let i = 0; i < inputs.length; i += batchSize) {
    const batch = inputs.slice(i, i + batchSize);
    await rateLimitDelay();
    const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/line_items/batch/create', {
      inputs: batch,
    }, { signal }));
    results.push(...(data.results || []));
    logger.debug(`Created line items batch ${Math.floor(i / batchSize) + 1}`, { count: batch.length });
  }

  return results;
}

export async function batchUpdateLineItems(inputs) {
  const batchSize = config.hubspot.batchSize;
  const results = [];

  for (let i = 0; i < inputs.length; i += batchSize) {
    const batch = inputs.slice(i, i + batchSize);
    await rateLimitDelay();
    const { data } = await hsRequest((signal) => client.post('/crm/v3/objects/line_items/batch/update', {
      inputs: batch,
    }, { signal }));
    results.push(...(data.results || []));
  }

  return results;
}

// --- Associations ---

export async function batchCreateAssociations(fromType, toType, inputs) {
  if (!inputs.length) return;
  await rateLimitDelay();
  await hsRequest((signal) => client.post(`/crm/v4/associations/${fromType}/${toType}/batch/create`, {
    inputs,
  }, { signal }));
}

