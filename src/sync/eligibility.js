import { config } from '../config/index.js';

export function getCustomerSalesrepId(customer) {
  return (customer.custom_attributes || [])
    .find(a => a.attribute_code === 'salesrep_rep_id')?.value
    ?? customer.salesrep_rep_id
    ?? null;
}

export function isEligibleCustomer(customer) {
  if (!customer) return false;
  if (String(customer.group_id) === '5') return false;

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
