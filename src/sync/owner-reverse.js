import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import * as db from '../db/sync-state.js';
import { OWNER_SALESREP_MAP } from '../config/salesrep-mapping.js';
import logger from '../utils/logger.js';

/**
 * Reverse sync: HubSpot contact owner → Magento customer salesrep_rep_id.
 *
 * Idempotency: skips the Magento write when the customer already has the
 * target salesrep_rep_id. This prevents ping-pong between forward and
 * reverse sync (forward sync bumps lastmodifieddate on the contact, which
 * would otherwise retrigger a reverse write of the same value).
 */
export async function syncOwnersReverse(since, runId) {
  logger.info('Starting owner reverse sync', { since: since.toISOString(), runId });

  const contacts = await hubspot.searchContactsModifiedSince(since);
  logger.info(`Found ${contacts.length} modified contacts`, { runId });

  let updated = 0;
  let skipped = 0;
  let failed = 0;
  let lastModifiedAt = null;

  for (const contact of contacts) {
    const ownerId = contact.properties?.hubspot_owner_id;
    const modifiedAt = contact.properties?.lastmodifieddate
      ? new Date(contact.properties.lastmodifieddate)
      : null;
    if (modifiedAt && (!lastModifiedAt || modifiedAt > lastModifiedAt)) {
      lastModifiedAt = modifiedAt;
    }

    // Empty owner on HubSpot ⇒ unassign on Magento (salesrep_rep_id = "0").
    let targetSalesrepId;
    if (!ownerId) {
      targetSalesrepId = '0';
    } else {
      targetSalesrepId = OWNER_SALESREP_MAP[String(ownerId)];
      if (!targetSalesrepId) {
        logger.debug('Owner has no salesrep mapping, skipping', { hubspotId: contact.id, ownerId, runId });
        skipped++;
        continue;
      }
    }

    try {
      let magentoCustomerId = await db.getMagentoIdByHubspotId('customer', contact.id);
      let existing = null;

      if (!magentoCustomerId) {
        // Fallback: look up Magento customer by email, then backfill the mapping.
        // If the email is shared across multiple stores, prefer the Main Website
        // (website_id=1) account so we don't write to duplicate per-store accounts.
        const email = contact.properties?.email;
        if (!email) {
          skipped++;
          continue;
        }
        const matches = await magento.getCustomersByEmail(email);
        if (matches.length === 0) {
          logger.debug('No Magento customer for HubSpot contact, skipping', {
            hubspotId: contact.id, email, runId,
          });
          skipped++;
          continue;
        }
        existing = matches.length > 1
          ? (matches.find(c => c.website_id === 1) || matches[0])
          : matches[0];
        magentoCustomerId = existing.id;
        await db.upsertMapping('customer', magentoCustomerId, contact.id);
      } else {
        existing = await magento.getCustomerById(magentoCustomerId);
      }

      const currentSalesrep = (existing.custom_attributes || [])
        .find(a => a.attribute_code === 'salesrep_rep_id')?.value;

      // Treat null/undefined/""/"0" as equivalent "unassigned" states so we
      // don't churn when HubSpot owner is blank and Magento is already empty.
      const normalize = (v) => {
        const s = String(v ?? '');
        return s === '' ? '0' : s;
      };
      if (normalize(currentSalesrep) === normalize(targetSalesrepId)) {
        skipped++;
        continue;
      }

      await magento.updateCustomerSalesrep(magentoCustomerId, targetSalesrepId);
      updated++;
      logger.info('Reverse-synced owner to salesrep', {
        hubspotId: contact.id,
        magentoId: magentoCustomerId,
        from: currentSalesrep,
        to: targetSalesrepId,
        runId,
      });
    } catch (err) {
      failed++;
      logger.error('Failed to reverse-sync owner', {
        hubspotId: contact.id,
        ownerId,
        error: err.message,
        runId,
      });
    }
  }

  logger.info('Owner reverse sync complete', { updated, skipped, failed, total: contacts.length, runId });
  await db.logSync(runId, 'owner_reverse', 'info',
    `Reverse-synced owners: ${updated} updated, ${skipped} skipped, ${failed} failed`);

  return { updated, skipped, failed, lastModifiedAt };
}
