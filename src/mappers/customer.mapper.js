import { SALESREP_OWNER_MAP } from '../config/salesrep-mapping.js';

export function mapCustomerToContact(customer) {
  const billingAddress = (customer.addresses || []).find(a => a.default_billing) || customer.addresses?.[0];

  const properties = {
    email: customer.email,
    firstname: customer.firstname || '',
    lastname: customer.lastname || '',
  };

  if (billingAddress) {
    if (billingAddress.company) properties.company = billingAddress.company;
    if (billingAddress.telephone) properties.phone = billingAddress.telephone;
    if (billingAddress.street?.length) properties.address = billingAddress.street.join(', ');
    if (billingAddress.city) properties.city = billingAddress.city;
    if (billingAddress.region?.region) properties.state = billingAddress.region.region;
    if (billingAddress.postcode) properties.zip = billingAddress.postcode;
    if (billingAddress.country_id) properties.country = billingAddress.country_id;
  }

  if (customer.created_at) {
    const d = new Date(customer.created_at);
    properties.account_created_date = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate());
  }

  // Resolve salesrep → HubSpot owner (works for both Magento API and CSV import shapes).
  // - Mapped salesrep → set owner.
  // - Missing/empty/"0" salesrep → clear owner ("" tells HubSpot to unassign).
  // - Unmapped non-empty salesrep → leave owner untouched (preserves any manual
  //   HubSpot assignment for reps we haven't added to SALESREP_OWNER_MAP yet).
  const salesrepId = (customer.custom_attributes || []).find(a => a.attribute_code === 'salesrep_rep_id')?.value
    ?? customer.salesrep_rep_id;
  const salesrepStr = salesrepId == null ? '' : String(salesrepId);
  if (salesrepStr === '' || salesrepStr === '0') {
    properties.hubspot_owner_id = '';
  } else {
    const ownerId = SALESREP_OWNER_MAP[salesrepStr];
    if (ownerId) properties.hubspot_owner_id = ownerId;
  }

  return properties;
}
