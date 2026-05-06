import * as magento from '../api/magento.js';
import * as hubspot from '../api/hubspot.js';
import * as db from '../db/sync-state.js';
import { OWNER_SALESREP_MAP } from '../config/salesrep-mapping.js';
import logger from '../utils/logger.js';

let ownerReverseInProgress = false;

async function updateScopeIfNeeded(magentoCustomerId, targetSalesrepId, context) {
  let existing;
  try {
    existing = await magento.getCustomerById(magentoCustomerId);
  } catch (err) {
    if (err.response?.status === 404) {
      logger.debug('Magento customer not found (deleted), skipping', { magentoId: magentoCustomerId, ...context });
      return 'skipped';
    }
    throw err;
  }
  const currentSalesrep = (existing.custom_attributes || [])
    .find(a => a.attribute_code === 'salesrep_rep_id')?.value;

  const normalize = (v) => { const s = String(v ?? ''); return s === '' ? '0' : s; };
  if (normalize(currentSalesrep) === normalize(targetSalesrepId)) {
    return 'skipped';
  }

  try {
    await magento.updateCustomerSalesrep(magentoCustomerId, targetSalesrepId, existing);
  } catch (err) {
    if (err.response?.status === 400) {
      logger.warn('Skipping customer with invalid Magento data (400)', {
        magentoId: magentoCustomerId, error: err.response?.data?.message, ...context,
      });
      return 'skipped';
    }
    throw err;
  }
  logger.info('Reverse-synced owner to salesrep', {
    ...context,
    magentoId: magentoCustomerId,
    from: currentSalesrep,
    to: targetSalesrepId,
  });
  return 'updated';
}

/**
 * Reverse sync: HubSpot contact owner → Magento customer salesrep_rep_id.
 *
 * Idempotency: skips the Magento write when the customer already has the
 * target salesrep_rep_id. This prevents ping-pong between forward and
 * reverse sync (forward sync bumps lastmodifieddate on the contact, which
 * would otherwise retrigger a reverse write of the same value).
 */
export async function syncOwnersReverse(since, runId) {
  if (ownerReverseInProgress) {
    logger.warn('Owner reverse sync already in progress, skipping', { runId });
    return { updated: 0, skipped: 0, failed: 0, lastModifiedAt: null };
  }
  ownerReverseInProgress = true;
  try {
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
        ? new Date(Number(contact.properties.lastmodifieddate))
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
        let magentoCustomerIds = await db.getMagentoIdsByHubspotId('customer', contact.id);

        if (magentoCustomerIds.length === 0) {
          // Fallback: look up Magento customer by email, then backfill mappings for
          // all matching store accounts so future syncs use the fast DB path.
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
          for (const match of matches) {
            await db.upsertMapping('customer', match.id, contact.id);
          }
          magentoCustomerIds = matches.map(m => String(m.id));
        }

        for (const magentoCustomerId of magentoCustomerIds) {
          const outcome = await updateScopeIfNeeded(
            magentoCustomerId,
            targetSalesrepId,
            { hubspotId: contact.id, ownerId, runId },
          );
          if (outcome === 'updated') updated++;
          else skipped++;
        }
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

    if (failed === 0 && lastModifiedAt) {
      await db.updateLastSyncedAt('owner_reverse', lastModifiedAt);
    }

    return { updated, skipped, failed, lastModifiedAt };
  } finally {
    ownerReverseInProgress = false;
  }
}
