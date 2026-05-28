import * as db from '../db/sync-state.js';
import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import { mapProductToHubspot } from '../mappers/product.mapper.js';
import { syncSingleOrder } from './orders.js';
import { config } from '../config/index.js';
import { isEligibleCustomer, requiresQualifyingOrderForCustomer } from './eligibility.js';
import logger from '../utils/logger.js';

export async function processRetryQueue(runId) {
  const items = await db.getRetryItems(50);
  if (!items.length) return;

  logger.info(`Processing retry queue: ${items.length} items`, { runId });

  let succeeded = 0;
  let failed = 0;

  for (const item of items) {
    try {
      await retryItem(item);
      await db.removeRetryItem(item.id);
      succeeded++;
      logger.info('Retry succeeded', { entityType: item.entity_type, magentoId: item.magento_id, runId });
    } catch (err) {
      const newAttempts = item.attempts + 1;
      await db.updateRetryItem(item.id, newAttempts, err.message);
      failed++;

      if (newAttempts >= item.max_attempts) {
        logger.error('Retry exhausted max attempts', {
          entityType: item.entity_type,
          magentoId: item.magento_id,
          attempts: newAttempts,
          error: err.message,
          runId,
        });
      } else {
        logger.warn('Retry failed, will try again', {
          entityType: item.entity_type,
          magentoId: item.magento_id,
          attempts: newAttempts,
          error: err.message,
          runId,
        });
      }
    }
  }

  logger.info('Retry queue processing complete', { succeeded, failed, runId });
}

async function retryItem(item) {
  const payload = item.payload;

  switch (item.entity_type) {
    case 'customer': {
      const customer = payload.customer;
      if (!isEligibleCustomer(customer)) return;

      if (requiresQualifyingOrderForCustomer(customer)) {
        const qualifyingOrders = await magento.getQualifyingOrdersByCustomerId(
          item.magento_id,
          config.sync.customerMinOrderTotal,
          1,
        );
        if (!qualifyingOrders.length) return;
      }

      const properties = mapCustomerToContact(customer);
      if (!properties.email) return;

      const existing = await hubspot.searchContacts(properties.email);
      if (existing) {
        await hubspot.updateContact(existing.id, properties);
        await db.upsertMapping('customer', item.magento_id, existing.id);
      } else {
        const result = await hubspot.createContact(properties);
        await db.upsertMapping('customer', item.magento_id, result.id);
      }
      break;
    }
    case 'product': {
      const product = payload.product;
      const properties = mapProductToHubspot(product);
      const existing = await hubspot.searchProductBySku(properties.hs_sku);
      if (existing) {
        await hubspot.updateProduct(existing.id, properties);
        await db.upsertMapping('product', item.magento_id, existing.id);
      } else {
        const result = await hubspot.createProduct(properties);
        await db.upsertMapping('product', item.magento_id, result.id);
      }
      break;
    }
    case 'order': {
      const order = await magento.getOrderById(item.magento_id);
      await syncSingleOrder(order, 'retry');
      break;
    }
    default:
      logger.warn('Unknown entity type in retry queue', { entityType: item.entity_type });
  }
}
