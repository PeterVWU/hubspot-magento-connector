import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import { mapCustomerToContact } from '../mappers/customer.mapper.js';
import { config } from '../config/index.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

async function customerHasQualifyingOrder(customerId) {
  const orders = await magento.getQualifyingOrdersByCustomerId(
    customerId,
    config.sync.customerMinOrderTotal,
    1,
  );
  return orders.length > 0;
}

export async function syncCustomers(since, runId) {
  logger.info('Starting customer sync', { since: since.toISOString(), runId });

  const customers = await magento.getCustomersUpdatedSince(since);
  logger.info(`Found ${customers.length} customers to sync`, { runId });

  let created = 0;
  let updated = 0;
  let skipped = 0;
  let failed = 0;

  for (const customer of customers) {
    try {
      const properties = mapCustomerToContact(customer);
      if (!properties.email) {
        skipped++;
        logger.warn('Skipping customer without email', { magentoId: customer.id, runId });
        continue;
      }

      if (!(await customerHasQualifyingOrder(customer.id))) {
        skipped++;
        logger.debug('Skipping customer without qualifying order', {
          magentoId: customer.id,
          minOrderTotal: config.sync.customerMinOrderTotal,
          runId,
        });
        continue;
      }

      const existingHubspotId = await db.getHubspotId('customer', customer.id);

      if (existingHubspotId) {
        await hubspot.updateContact(existingHubspotId, properties);
        await db.upsertMapping('customer', customer.id, existingHubspotId);
        updated++;
        logger.debug('Updated contact', { magentoId: customer.id, hubspotId: existingHubspotId, runId });
      } else {
        // Search HubSpot by email in case it exists but we don't have a mapping
        const existing = await hubspot.searchContacts(properties.email);
        if (existing) {
          await hubspot.updateContact(existing.id, properties);
          await db.upsertMapping('customer', customer.id, existing.id);
          updated++;
          logger.debug('Found and updated existing contact', { magentoId: customer.id, hubspotId: existing.id, runId });
        } else {
          const result = await hubspot.createContact(properties);
          await db.upsertMapping('customer', customer.id, result.id);
          created++;
          logger.debug('Created new contact', { magentoId: customer.id, hubspotId: result.id, runId });
        }
      }
    } catch (err) {
      const isInvalidEmail = err.response?.data?.errors?.some(e => e.code === 'INVALID_EMAIL');
      if (isInvalidEmail) {
        skipped++;
        logger.warn('Skipping customer with invalid email (will not retry)', {
          magentoId: customer.id,
          email: customer.email,
          runId,
        });
        continue;
      }
      failed++;
      logger.error('Failed to sync customer', {
        magentoId: customer.id,
        error: err.message,
        runId,
      });
      await db.addToRetryQueue('customer', customer.id, 'upsert', { customer }, err.message);
    }
  }

  // Use the last record's updated_at as the high-water mark (records are sorted ASC)
  const lastUpdatedAt = customers.length > 0
    ? new Date(customers[customers.length - 1].updated_at)
    : null;

  logger.info('Customer sync complete', { created, updated, skipped, failed, total: customers.length, runId });
  await db.logSync(runId, 'customer', 'info', `Synced ${customers.length} customers: ${created} created, ${updated} updated, ${skipped} skipped, ${failed} failed`);

  return { created, updated, skipped, failed, lastUpdatedAt };
}
