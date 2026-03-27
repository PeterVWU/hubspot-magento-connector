import axios from 'axios';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';

const client = axios.create({
  baseURL: config.magento.baseUrl,
  headers: {
    Authorization: `Bearer ${config.magento.token}`,
    'Content-Type': 'application/json',
  },
  timeout: config.magento.timeout,
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
    const { data } = await client.get(url);

    totalCount = data.total_count;
    allItems = allItems.concat(data.items || []);

    logger.info(`Fetched ${entityName} page ${currentPage}`, {
      pageItems: (data.items || []).length,
      totalSoFar: allItems.length,
      totalCount,
    });

    if (maxRecords > 0 && allItems.length >= maxRecords) {
      allItems = allItems.slice(0, maxRecords);
      logger.info(`Reached max records limit for ${entityName}`, { maxRecords, totalAvailable: totalCount });
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

  // Fetch customers updated since the given time
  const customers = await fetchAllPages('/customers/search', filters, 'customers', config.magento.maxRecordsPerSync);

  // Client-side filters for custom EAV attributes
  const before = customers.length;
  const filtered = customers.filter((customer) => {
    const attrs = customer.custom_attributes || [];

    // Exclude customers with Fraud flag set to 1
    const fraudAttr = attrs.find(a => a.attribute_code === 'Fraud');
    if (fraudAttr?.value === '1' || fraudAttr?.value === 1) return false;

    // Exclude customers assigned to specific sales reps
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

  return filtered;
}

export async function getProductsUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/products', filters, 'products', config.magento.maxRecordsPerSync);
}

export async function getCustomerById(customerId) {
  logger.debug('Fetching single customer', { customerId });
  const { data } = await client.get(`/customers/${customerId}`);
  return data;
}

export async function getOrdersByCustomerId(customerId) {
  const filters = [
    { field: 'customer_id', value: String(customerId), condition: 'eq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/orders', filters, 'orders', 0);
}

export async function getOrderById(orderId) {
  logger.debug('Fetching single order', { orderId });
  const { data } = await client.get(`/orders/${orderId}`);
  return data;
}

export async function getOrdersUpdatedSince(since) {
  const sinceStr = since.toISOString().replace('T', ' ').replace('Z', '');
  const filters = [
    { field: 'updated_at', value: sinceStr, condition: 'gteq', group: 0, filterIdx: 0 },
  ];
  return fetchAllPages('/orders', filters, 'orders', config.magento.maxRecordsPerSync);
}
