import { config } from '../config/index.js';

export const BLOCKED_CUSTOMER_GROUP_IDS = ['0', '5', '61', '62', '64'];

export function getCustomerSalesrepId(customer) {
  return (customer.custom_attributes || [])
    .find(a => a.attribute_code === 'salesrep_rep_id')?.value
    ?? customer.salesrep_rep_id
    ?? null;
}

export function isEligibleCustomer(customer) {
  if (!customer) return false;
  if (BLOCKED_CUSTOMER_GROUP_IDS.includes(String(customer.group_id))) return false;

  const fraudAttr = (customer.custom_attributes || [])
    .find(a => a.attribute_code === 'Fraud');
  if (fraudAttr?.value === '1' || fraudAttr?.value === 1) return false;

  const salesrepId = getCustomerSalesrepId(customer);
  if (salesrepId && config.sync.excludedSalesrepIds.includes(String(salesrepId))) {
    return false;
  }

  return true;
}

export function isQualifyingOrder(order) {
  return Number(order?.grand_total) > config.sync.customerMinOrderTotal;
}
