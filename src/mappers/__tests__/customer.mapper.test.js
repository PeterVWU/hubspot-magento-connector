import { describe, it, expect, vi } from 'vitest';

vi.mock('../../config/salesrep-mapping.js', () => ({
  SALESREP_OWNER_MAP: {
    '24': '162307854',
  },
}));

const { mapCustomerToContact } = await import('../customer.mapper.js');

function withSalesrep(value) {
  return {
    email: 'x@y.com',
    custom_attributes: value === undefined ? [] : [{ attribute_code: 'salesrep_rep_id', value }],
  };
}

describe('mapCustomerToContact owner resolution', () => {
  it('sets hubspot_owner_id when salesrep is mapped', () => {
    const props = mapCustomerToContact(withSalesrep('24'));
    expect(props.hubspot_owner_id).toBe('162307854');
  });

  it('clears hubspot_owner_id when salesrep is missing entirely', () => {
    const props = mapCustomerToContact(withSalesrep(undefined));
    expect(props.hubspot_owner_id).toBe('');
  });

  it('clears hubspot_owner_id when salesrep is the empty/zero unassigned sentinel', () => {
    expect(mapCustomerToContact(withSalesrep('')).hubspot_owner_id).toBe('');
    expect(mapCustomerToContact(withSalesrep('0')).hubspot_owner_id).toBe('');
    expect(mapCustomerToContact(withSalesrep(0)).hubspot_owner_id).toBe('');
    expect(mapCustomerToContact(withSalesrep(null)).hubspot_owner_id).toBe('');
  });

  it('leaves hubspot_owner_id untouched for an unmapped non-zero salesrep', () => {
    // Preserves any manual HubSpot assignment for reps not yet in SALESREP_OWNER_MAP.
    const props = mapCustomerToContact(withSalesrep('999'));
    expect(props).not.toHaveProperty('hubspot_owner_id');
  });
});
