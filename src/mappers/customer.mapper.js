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

  return properties;
}
