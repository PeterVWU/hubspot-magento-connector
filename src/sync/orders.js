import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapOrderToDeal, mapOrderItemToLineItem, getOrderItemsForSync } from '../mappers/order.mapper.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

let pipelineId = null;
let stageMap = null;

async function ensurePipeline() {
  if (pipelineId && stageMap) return;

  const pipelines = await hubspot.getDealPipelines();
  const ecommPipeline = pipelines.find(p => p.label === 'Ecommerce Pipeline')
    || pipelines.find(p => p.id === 'default')
    || pipelines[0];

  pipelineId = ecommPipeline.id;
  stageMap = {};
  for (const stage of ecommPipeline.stages || []) {
    stageMap[stage.label] = stage.id;
  }

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
      await syncSingleOrder(order, runId);

      // Check after sync to determine if it was a create or update
      const hubspotIdAfter = await db.getHubspotId('order', order.entity_id);
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

  logger.info('Order sync complete', { created, updated, failed, total: orders.length, runId });
  await db.logSync(runId, 'order', 'info', `Synced ${orders.length} orders: ${created} created, ${updated} updated, ${failed} failed`);

  return { created, updated, failed };
}

async function syncSingleOrder(order, runId) {
  const dealProperties = mapOrderToDeal(order, pipelineId, stageMap);

  // Check if deal already exists in our mapping
  let dealHubspotId = await db.getHubspotId('order', order.entity_id);

  if (dealHubspotId) {
    await hubspot.updateDeal(dealHubspotId, dealProperties);
    logger.debug('Updated deal', { orderId: order.increment_id, hubspotId: dealHubspotId, runId });
  } else {
    // Search HubSpot by order number
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

  // Associate contact to deal
  const contactHubspotId = order.customer_id
    ? await db.getHubspotId('customer', order.customer_id)
    : null;

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

  const lineItemInputs = items.map((item) => {
    const hubspotProductId = productMappings.get(String(item.product_id));
    const properties = mapOrderItemToLineItem(item, hubspotProductId);
    return {
      properties,
      associations: [{
        to: { id: dealHubspotId },
        types: [{ associationCategory: 'HUBSPOT_DEFINED', associationTypeId: 20 }],
      }],
    };
  });

  const results = await hubspot.batchCreateLineItems(lineItemInputs);
  logger.debug('Created line items', { orderId: order.increment_id, count: results.length, runId });

  // Store line item mappings
  for (let i = 0; i < results.length && i < items.length; i++) {
    await db.upsertMapping('line_item', items[i].item_id, results[i].id);
  }
}
