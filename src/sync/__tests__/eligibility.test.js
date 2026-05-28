import { describe, it, expect, vi } from 'vitest';

vi.mock('../../config/index.js', () => ({
  config: {
    sync: {
      customerMinOrderTotal: 500,
      excludedSalesrepIds: ['99'],
    },
  },
}));

const { isEligibleCustomer, isQualifyingOrder, requiresQualifyingOrderForCustomer } = await import('../eligibility.js');

describe('sync eligibility', () => {
  it('requires order grand_total to be strictly greater than the configured threshold', () => {
    expect(isQualifyingOrder({ grand_total: '499.99' })).toBe(false);
    expect(isQualifyingOrder({ grand_total: '500.00' })).toBe(false);
    expect(isQualifyingOrder({ grand_total: '500.01' })).toBe(true);
    expect(isQualifyingOrder({ grand_total: undefined })).toBe(false);
    expect(isQualifyingOrder({ grand_total: 'not-a-number' })).toBe(false);
  });

  it('excludes blocked groups, fraud flags, and excluded sales reps', () => {
    for (const groupId of [0, 5, 61, 62, 64]) {
      expect(isEligibleCustomer({ group_id: groupId, custom_attributes: [] })).toBe(false);
    }
    expect(isEligibleCustomer({
      group_id: 1,
      custom_attributes: [{ attribute_code: 'Fraud', value: '1' }],
    })).toBe(false);
    expect(isEligibleCustomer({
      group_id: 1,
      custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '99' }],
    })).toBe(false);
    expect(isEligibleCustomer({ group_id: 1, custom_attributes: [] })).toBe(true);
    expect(isEligibleCustomer({ group_id: 2, custom_attributes: [] })).toBe(true);
  });

  it('only requires a qualifying order for customer group 1', () => {
    expect(requiresQualifyingOrderForCustomer({ group_id: 1 })).toBe(true);
    expect(requiresQualifyingOrderForCustomer({ group_id: '1' })).toBe(true);
    expect(requiresQualifyingOrderForCustomer({ group_id: 65 })).toBe(false);
    expect(requiresQualifyingOrderForCustomer({ group_id: 0 })).toBe(false);
  });
});
