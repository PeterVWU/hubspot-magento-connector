import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapProductToHubspot } from '../mappers/product.mapper.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

export async function syncProducts(since, runId) {
  logger.info('Starting product sync', { since: since.toISOString(), runId });

  const products = await magento.getProductsUpdatedSince(since);
  logger.info(`Found ${products.length} products to sync`, { runId });

  let created = 0;
  let updated = 0;
  let failed = 0;

  for (const product of products) {
    try {
      const properties = mapProductToHubspot(product);
      if (!properties.hs_sku) {
        logger.warn('Skipping product without SKU', { magentoId: product.id, runId });
        continue;
      }

      const existingHubspotId = await db.getHubspotId('product', product.id);

      if (existingHubspotId) {
        await hubspot.updateProduct(existingHubspotId, properties);
        await db.upsertMapping('product', product.id, existingHubspotId);
        updated++;
        logger.debug('Updated product', { magentoId: product.id, sku: product.sku, runId });
      } else {
        const existing = await hubspot.searchProductBySku(product.sku);
        if (existing) {
          await hubspot.updateProduct(existing.id, properties);
          await db.upsertMapping('product', product.id, existing.id);
          updated++;
          logger.debug('Found and updated existing product', { magentoId: product.id, hubspotId: existing.id, runId });
        } else {
          const result = await hubspot.createProduct(properties);
          await db.upsertMapping('product', product.id, result.id);
          created++;
          logger.debug('Created new product', { magentoId: product.id, hubspotId: result.id, runId });
        }
      }
    } catch (err) {
      failed++;
      logger.error('Failed to sync product', {
        magentoId: product.id,
        sku: product.sku,
        error: err.message,
        runId,
      });
      await db.addToRetryQueue('product', product.id, 'upsert', { product }, err.message);
    }
  }

  logger.info('Product sync complete', { created, updated, failed, total: products.length, runId });
  await db.logSync(runId, 'product', 'info', `Synced ${products.length} products: ${created} created, ${updated} updated, ${failed} failed`);

  return { created, updated, failed };
}
