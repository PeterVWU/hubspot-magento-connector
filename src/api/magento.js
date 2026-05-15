import axios from 'axios';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';
import { withTimeout } from '../utils/timeout.js';

const client = axios.create({
  baseURL: config.magento.baseUrl,
  headers: {
    Authorization: `Bearer ${config.magento.token}`,
    'Content-Type': 'application/json',
  },
});

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const url = error.config?.url;
    const data = error.response?.data;
    logger.error('Magento API error', { status, url, data: JSON.stringify(data) });
    throw error;
  },
);

function buildSearchCriteria(filters, pageSize, currentPage, sortField = 'updated_at') {
  const params = new URLSearchParams();
  let groupIdx = 0;

  for (const filter of filters) {
    const filterIdx = filter.filterIdx || 0;
    const prefix = `searchCriteria[filterGroups][${filter.group ?? groupIdx}][filters][${filterIdx}]`;
    params.append(`${prefix}[field]`, filter.field);
    params.append(`${prefix}[value]`, filter.value);
    params.append(`${prefix}[conditionType]`, filter.condition);
    if (filter.group === undefined) groupIdx++;
  }

  params.append('searchCriteria[sortOrders][0][field]', sortField);
  params.append('searchCriteria[sortOrders][0][direction]', 'ASC');
  params.append('searchCriteria[pageSize]', String(pageSize));
  params.append('searchCriteria[currentPage]', String(currentPage));

  return params.toString();
}

async function fetchAllPages(endpoint, filters, entityName, maxRecords = 0) {
  const pageSize = config.magento.pageSize;
  let currentPage = 1;
  let allItems = [];
  let totalCount = 0;

  do {
    const query = buildSearchCriteria(filters, pageSize, currentPage);
    const url = `${endpoint}?${query}`;

    logger.debug(`Fetching ${entityName} page ${currentPage}`, { url });
    const { data } = await withTimeout(
      (signal) => client.get(url, { signal }),
      config.magento.timeout,
    );

    const items = data.items || [];
    totalCount = data.total_count;
    allItems = allItems.concat(items);

    logger.info(`Fetched ${entityName} page ${currentPage}`, {
      pageItems: items.length,
      totalSoFar: allItems.length,
      totalCount,
    });

    if (maxRecords > 0 && allItems.length >= maxRecords) {
      allItems = allItems.slice(0, maxRecords);
      logger.info(`Reached max records limit for ${entityName}`, { maxRecords, totalAvailable: totalCount });
      break;
    }

    // Defensive: if Magento reports total_count > collected but returns an
    // empty page, the do/while condition can never become false. Stop and
    // let the next sync cycle pick up any remaining records via its cursor.
    if (items.length === 0) {
      if (allItems.length < totalCount) {
        logger.warn(`${entityName} pagination stopped early: empty page despite totalCount > collected`, {
          currentPage, totalSoFar: allItems.length, totalCount,
        });
      }
      break;
    }

    currentPage++;
  } while (allItems.length < totalCount);

  return allItems;
}

export async function getCustomersUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const excludedIds = config.sync.excludedSalesrepIds;
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
    // Exclude fraud customer group (group_id = 5) server-side
    { field: 'group_id', value: '5', condition: 'neq', group: 1, filterIdx: 0 },
  ];

  // Exclude assigned-to-excluded-salesrep customers server-side so the
  // pagination budget isn't burned on records we'd drop anyway. Within a
  // filter group, multiple filters are OR'd, so `nin` OR `null` keeps
  // customers with no salesrep_rep_id at all (matching the prior client
  // behavior that only excluded *assigned* excluded reps).
  if (excludedIds.length > 0) {
    filters.push(
      { field: 'salesrep_rep_id', value: excludedIds.join(','), condition: 'nin', group: 2, filterIdx: 0 },
      { field: 'salesrep_rep_id', value: '1', condition: 'null', group: 2, filterIdx: 1 },
    );
  }

  // Fetch customers updated since the given time
  const customers = await fetchAllPages('/customers/search', filters, 'customers', config.magento.maxRecordsPerSync);

  // High-water mark for cursor advancement: latest updated_at in the raw
  // batch (pre-filter). Excluded customers are deterministically excluded,
  // so we don't need the cursor to revisit them.
  const lastRawUpdatedAt = customers.length > 0
    ? new Date(customers[customers.length - 1].updated_at)
    : null;

  // Client-side filters: defense-in-depth against EAV/Magento edge cases
  // (e.g. a salesrep we forgot to add to EXCLUDED_SALESREP_IDS slipping
  // through the server filter, or the Fraud flag which we don't push to
  // the server filter today).
  const before = customers.length;
  const filtered = customers.filter((customer) => {
    const attrs = customer.custom_attributes || [];

    const fraudAttr = attrs.find(a => a.attribute_code === 'Fraud');
    if (fraudAttr?.value === '1' || fraudAttr?.value === 1) return false;

    if (excludedIds.length > 0) {
      const salesrepAttr = attrs.find(a => a.attribute_code === 'salesrep_rep_id');
      const salesrepId = salesrepAttr?.value;
      if (salesrepId && excludedIds.includes(String(salesrepId))) return false;
    }

    return true;
  });

  if (filtered.length !== before) {
    logger.info(`Customer filters applied: ${before} -> ${filtered.length} (excluded ${before - filtered.length})`);
  }

  return { customers: filtered, lastRawUpdatedAt };
}

export async function getProductsUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/products', filters, 'products', config.magento.maxRecordsPerSync);
}

export async function getCustomersByEmail(email) {
  const filters = [
    { field: 'email', value: email, condition: 'eq', group: 0, filterIdx: 0 },
  ];
  const query = buildSearchCriteria(filters, 50, 1);
  const { data } = await withTimeout(
    (signal) => client.get(`/customers/search?${query}`, { signal }),
    config.magento.timeout,
  );
  return data.items || [];
}

export async function getCustomerById(customerId) {
  logger.debug('Fetching single customer', { customerId });
  const { data } = await withTimeout(
    (signal) => client.get(`/customers/${customerId}`, { signal }),
    config.magento.timeout,
  );
  return data;
}

export async function getOrdersByCustomerId(customerId) {
  const filters = [
    { field: 'customer_id', value: String(customerId), condition: 'eq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/orders', filters, 'orders', 0);
}

export async function getQualifyingOrdersByCustomerId(customerId, minTotal, limit = 1) {
  const filters = [
    { field: 'customer_id', value: String(customerId), condition: 'eq', group: 0, filterIdx: 0 },
    { field: 'grand_total', value: String(minTotal), condition: 'gt', group: 1, filterIdx: 0 },
  ];
  const query = buildSearchCriteria(filters, limit, 1);
  const { data } = await withTimeout(
    (signal) => client.get(`/orders?${query}`, { signal }),
    config.magento.timeout,
  );
  return data.items || [];
}

export async function getOrderById(orderId) {
  logger.debug('Fetching single order', { orderId });
  const { data } = await withTimeout(
    (signal) => client.get(`/orders/${orderId}`, { signal }),
    config.magento.timeout,
  );
  return data;
}

export async function updateCustomerSalesrep(customerId, salesrepId, existing = null) {
  if (!existing) existing = await getCustomerById(customerId);
  logger.debug('Updating customer salesrep', { customerId, salesrepId });
  const { data } = await withTimeout(
    (signal) => client.put(`/customers/${customerId}`, {
      customer: {
        id: existing.id,
        email: existing.email,
        website_id: existing.website_id,
        firstname: existing.firstname,
        lastname: existing.lastname,
        custom_attributes: [
          { attribute_code: 'salesrep_rep_id', value: String(salesrepId) },
        ],
      },
    }, { signal }),
    config.magento.timeout,
  );
  return data;
}

export async function getOrdersUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/orders', filters, 'orders', config.magento.maxRecordsPerSync);
}
