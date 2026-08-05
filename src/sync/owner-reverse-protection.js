import { config } from '../config/index.js';
import { getCustomerSalesrepId } from './eligibility.js';

/**
 * Protected Magento sales reps remain the source of truth for assignment.
 * HubSpot owner changes (including an empty owner) must not overwrite them.
 */
export function isOwnerReverseProtected(customer) {
  const salesrepId = getCustomerSalesrepId(customer);
  return salesrepId != null
    && config.sync.ownerReverseProtectedSalesrepIds.includes(String(salesrepId));
}
