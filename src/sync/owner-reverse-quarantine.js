import * as magento from '../api/magento.js';
import * as db from '../db/sync-state.js';
import logger from '../utils/logger.js';

/**
 * Re-attempts owner-reverse updates that previously failed Magento validation
 * (typically pre-existing data quality issues like an invisible Unicode char
 * in an address phone). Items become eligible based on `next_retry_at`, and
 * the per-item backoff in db.quarantineOwnerReverse() pushes the retry farther
 * out each time it fails again. Once the underlying Magento data is corrected
 * by an admin, the next due retry succeeds and the row is removed.
 */
export async function processOwnerReverseQuarantine(runId) {
  const items = await db.getDueQuarantinedOwnerReverse(50);
  if (!items.length) return { retried: 0, recovered: 0, stillFailing: 0 };

  logger.info(`Processing owner-reverse quarantine: ${items.length} due`, { runId });

  let recovered = 0;
  let stillFailing = 0;

  for (const item of items) {
    try {
      const existing = await magento.getCustomerById(item.magento_id);
      await magento.updateCustomerSalesrep(item.magento_id, item.target_salesrep_id, existing);
      await db.removeQuarantinedOwnerReverse(item.hubspot_id, item.magento_id);
      recovered++;
      logger.info('Quarantined owner-reverse recovered', {
        hubspotId: item.hubspot_id,
        magentoId: item.magento_id,
        targetSalesrep: item.target_salesrep_id,
        priorAttempts: item.attempts,
        runId,
      });
    } catch (err) {
      const status = err.response?.status;
      if (status === 404) {
        // Customer was deleted — clean up the row.
        await db.removeQuarantinedOwnerReverse(item.hubspot_id, item.magento_id);
        logger.info('Quarantined customer deleted in Magento, removing', {
          hubspotId: item.hubspot_id, magentoId: item.magento_id, runId,
        });
        continue;
      }
      const errorMessage = err.response?.data?.message || err.message || 'unknown';
      // Re-quarantine bumps attempts and pushes next_retry_at.
      await db.quarantineOwnerReverse(
        item.hubspot_id, item.magento_id, item.target_salesrep_id, errorMessage,
      );
      stillFailing++;
      logger.debug('Quarantined owner-reverse still failing', {
        hubspotId: item.hubspot_id,
        magentoId: item.magento_id,
        attempts: item.attempts + 1,
        error: errorMessage,
        runId,
      });
    }
  }

  logger.info('Owner-reverse quarantine pass complete', {
    retried: items.length, recovered, stillFailing, runId,
  });
  return { retried: items.length, recovered, stillFailing };
}
