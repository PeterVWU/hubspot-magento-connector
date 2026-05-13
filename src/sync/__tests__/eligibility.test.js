import { describe, it, expect, vi } from 'vitest';

vi.mock('../../config/index.js', () => ({
  config: {
    sync: {
      customerMinOrderTotal: 500,
      excludedSalesrepIds: ['99'],
    },
  },
}));

const { isEligibleCustomer, isQualifyingOrder } = await import('../eligibility.js');

describe('sync eligibility', () => {
  it('requires order grand_total to be strictly greater than the configured threshold', () => {
    expect(isQualifyingOrder({ grand_total: '499.99' })).toBe(false);
    expect(isQualifyingOrder({ grand_total: '500.00' })).toBe(false);
    expect(isQualifyingOrder({ grand_total: '500.01' })).toBe(true);
    expect(isQualifyingOrder({ grand_total: undefined })).toBe(false);
    expect(isQualifyingOrder({ grand_total: 'not-a-number' })).toBe(false);
  });

  it('excludes fraud groups, fraud flags, and excluded sales reps', () => {
    expect(isEligibleCustomer({ group_id: 5, custom_attributes: [] })).toBe(false);
    expect(isEligibleCustomer({
      group_id: 1,
      custom_attributes: [{ attribute_code: 'Fraud', value: '1' }],
    })).toBe(false);
    expect(isEligibleCustomer({
      group_id: 1,
      custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '99' }],
    })).toBe(false);
    expect(isEligibleCustomer({ group_id: 1, custom_attributes: [] })).toBe(true);
  });
});
