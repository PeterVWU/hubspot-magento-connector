import { describe, it, expect, vi } from 'vitest';

vi.mock('../../config/index.js', () => ({
  config: {
    sync: {
      excludedSalesrepIds: ['99'],
    },
  },
}));

const { isEligibleCustomer } = await import('../eligibility.js');

describe('sync eligibility', () => {
  it('excludes blocked groups, fraud flags, and excluded sales reps', () => {
    for (const groupId of [0, 1, '1', 5, 61, 62, 64]) {
      expect(isEligibleCustomer({ group_id: groupId, custom_attributes: [] })).toBe(false);
    }
    expect(isEligibleCustomer({
      group_id: 65,
      custom_attributes: [{ attribute_code: 'Fraud', value: '1' }],
    })).toBe(false);
    expect(isEligibleCustomer({
      group_id: 65,
      custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '99' }],
    })).toBe(false);
    expect(isEligibleCustomer({ group_id: 65, custom_attributes: [] })).toBe(true);
    expect(isEligibleCustomer({ group_id: 2, custom_attributes: [] })).toBe(true);
  });

});
