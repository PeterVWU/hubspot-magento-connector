import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapOrderToDeal, mapOrderItemToLineItem, getOrderItemsForSync } from '../mappers/order.mapper.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import { SALESREP_OWNER_MAP } from '../config/salesrep-mapping.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

let pipelineId = null;
let stageMap = null;
let pipelineFetchedAt = 0;
const PIPELINE_CACHE_TTL_MS = 60 * 60 * 1000; // 1 hour

async function ensurePipeline() {
  const now = Date.now();
  if (pipelineId && stageMap && (now - pipelineFetchedAt) < PIPELINE_CACHE_TTL_MS) return;

  const pipelines = await hubspot.getDealPipelines();
  const ecommPipeline = pipelines.find(p => p.label === 'Ecommerce Pipeline')
    || pipelines.find(p => p.id === 'default')
    || pipelines[0];

  pipelineId = ecommPipeline.id;
  stageMap = {};
  for (const stage of ecommPipeline.stages || []) {
    stageMap[stage.label] = stage.id;
  }
  pipelineFetchedAt = now;

  logger.info('Using deal pipeline', { pipelineId, stages: Object.keys(stageMap) });
}

export async function syncOrders(since, runId) {
  logger.info('Starting order sync', { since: since.toISOString(), runId });

  await ensurePipeline();

  const orders = await magento.getOrdersUpdatedSince(since);
  logger.info(`Found ${orders.length} orders to sync`, { runId });

  let created = 0;
  let updated = 0;
  let failed = 0;

  for (const order of orders) {
    try {
      const existingHubspotId = await db.getHubspotId('order', order.entity_id);
      const ownerId = await resolveOrderOwner(order);
      await syncSingleOrder(order, runId, ownerId);

      if (existingHubspotId) {
        updated++;
      } else {
        created++;
      }
    } catch (err) {
      failed++;
      logger.error('Failed to sync order', {
        orderId: order.increment_id,
        error: err.message,
        runId,
      });
      await db.addToRetryQueue('order', order.entity_id, 'upsert', { orderId: order.entity_id }, err.message);
    }
  }

  // Use the last record's updated_at as the high-water mark (records are sorted ASC)
  const lastUpdatedAt = orders.length > 0
    ? new Date(orders[orders.length - 1].updated_at)
    : null;

  logger.info('Order sync complete', { created, updated, failed, total: orders.length, runId });
  await db.logSync(runId, 'order', 'info', `Synced ${orders.length} orders: ${created} created, ${updated} updated, ${failed} failed`);

  return { created, updated, failed, lastUpdatedAt };
}

async function resolveOrderOwner(order) {
  if (!order.customer_id) return null;
  try {
    const customer = await magento.getCustomerById(order.customer_id);
    const salesrepAttr = (customer.custom_attributes || []).find(a => a.attribute_code === 'salesrep_rep_id');
    return salesrepAttr ? (SALESREP_OWNER_MAP[String(salesrepAttr.value)] || null) : null;
  } catch {
    return null;
  }
}

async function resolveOrCreateContact(order, runId) {
  try {
    const customer = await magento.getCustomerById(order.customer_id);
    if (!customer.email) return null;

    const properties = mapCustomerToContact(customer);

    // Search HubSpot by email first
    const existing = await hubspot.searchContacts(customer.email);
    if (existing) {
      await db.upsertMapping('customer', order.customer_id, existing.id);
      logger.debug('Found existing contact in HubSpot for order', {
        customerId: order.customer_id, hubspotId: existing.id, runId,
      });
      return existing.id;
    }

    // Create the contact
    const result = await hubspot.createContact(properties);
    await db.upsertMapping('customer', order.customer_id, result.id);
    logger.debug('Created contact from order sync', {
      customerId: order.customer_id, hubspotId: result.id, runId,
    });
    return result.id;
  } catch (err) {
    logger.warn('Could not resolve/create contact for order', {
      customerId: order.customer_id, orderId: order.increment_id, error: err.message, runId,
    });
    return null;
  }
}

export async function syncSingleOrder(order, runId, ownerId = null) {
  await ensurePipeline();

  const dealProperties = mapOrderToDeal(order, pipelineId, stageMap, ownerId);

  // Check if deal already exists in our mapping
  let dealHubspotId = await db.getHubspotId('order', order.entity_id);

  if (dealHubspotId) {
    await hubspot.updateDeal(dealHubspotId, dealProperties);
    logger.debug('Updated deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
  } else {
    // Search HubSpot by order number in case it exists but we don't have a mapping
    const existing = await hubspot.searchDealByOrderNumber(String(order.increment_id));
    if (existing) {
      dealHubspotId = existing.id;
      await hubspot.updateDeal(dealHubspotId, dealProperties);
      await db.upsertMapping('order', order.entity_id, dealHubspotId);
      logger.debug('Found and updated existing deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
    } else {
      const result = await hubspot.createDeal(dealProperties);
      dealHubspotId = result.id;
      await db.upsertMapping('order', order.entity_id, dealHubspotId);
      logger.debug('Created new deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
    }
  }

  // Associate contact to deal (type 3 = deal → contact)
  let contactHubspotId = order.customer_id
    ? await db.getHubspotId('customer', order.customer_id)
    : null;

  if (!contactHubspotId && order.customer_id) {
    // No DB mapping — look up or create the contact in HubSpot
    contactHubspotId = await resolveOrCreateContact(order, runId);
  }

  if (contactHubspotId) {
    await hubspot.batchCreateAssociations('deal', 'contact', [{
      from: { id: dealHubspotId },
      to: { id: contactHubspotId },
      types: [{ associationCategory: 'HUBSPOT_DEFINED', associationTypeId: 3 }],
    }]);
    logger.debug('Associated contact to deal', { contactId: contactHubspotId, dealId: dealHubspotId, runId });
  }

  // Sync line items
  await syncLineItems(order, dealHubspotId, runId);
}

async function syncLineItems(order, dealHubspotId, runId) {
  const items = getOrderItemsForSync(order);
  if (!items.length) return;

  // Look up HubSpot product IDs for all items
  const productIds = items.map(i => String(i.product_id));
  const productMappings = await db.getHubspotIdsBatch('product', productIds);

  // Check which line items already exist in HubSpot (avoid duplicates on re-sync)
  const itemIds = items.map(i => String(i.item_id));
  const existingLineItems = await db.getHubspotIdsBatch('line_item', itemIds);

  const toCreate = [];
  const toUpdate = [];

  for (const item of items) {
    const hubspotProductId = productMappings.get(String(item.product_id));
    const properties = mapOrderItemToLineItem(item, hubspotProductId);
    const existingHubspotId = existingLineItems.get(String(item.item_id));

    if (existingHubspotId) {
      toUpdate.push({ id: existingHubspotId, properties });
    } else {
      toCreate.push({
        item,
        input: {
          properties,
          associations: [{
            to: { id: dealHubspotId },
            types: [{ associationCategory: 'HUBSPOT_DEFINED', associationTypeId: 20 }],
          }],
        },
      });
    }
  }

  // Update existing line items
  if (toUpdate.length) {
    await hubspot.batchUpdateLineItems(toUpdate);
    logger.debug('Updated line items', { orderId: order.increment_id, count: toUpdate.length, runId });
  }

  // Create new line items (inline association to deal via type 20)
  if (toCreate.length) {
    const results = await hubspot.batchCreateLineItems(toCreate.map(t => t.input));
    logger.debug('Created line items', { orderId: order.increment_id, count: results.length, runId });

    for (let i = 0; i < results.length && i < toCreate.length; i++) {
      await db.upsertMapping('line_item', toCreate[i].item.item_id, results[i].id);
    }
  }
}
