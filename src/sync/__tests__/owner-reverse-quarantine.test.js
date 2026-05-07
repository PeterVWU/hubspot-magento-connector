import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockGetDue = vi.fn();
const mockRemoveQ = vi.fn();
const mockQuarantine = vi.fn();

vi.mock('../../db/sync-state.js', () => ({
  getDueQuarantinedOwnerReverse: mockGetDue,
  removeQuarantinedOwnerReverse: mockRemoveQ,
  quarantineOwnerReverse: mockQuarantine,
}));

const mockGetCustomerById = vi.fn();
const mockUpdateCustomerSalesrep = vi.fn();
vi.mock('../../api/magento.js', () => ({
  getCustomerById: mockGetCustomerById,
  updateCustomerSalesrep: mockUpdateCustomerSalesrep,
}));

vi.mock('../../utils/logger.js', () => ({
  default: { info: vi.fn(), debug: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

const { processOwnerReverseQuarantine } = await import('../owner-reverse-quarantine.js');

const dueRow = {
  hubspot_id: 'hs-1',
  magento_id: '500',
  target_salesrep_id: '42',
  error_message: 'Invalid Phone Number...',
  attempts: 2,
};

describe('processOwnerReverseQuarantine', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockRemoveQ.mockResolvedValue();
    mockQuarantine.mockResolvedValue();
  });

  it('returns zeros and skips work when nothing is due', async () => {
    mockGetDue.mockResolvedValueOnce([]);
    const result = await processOwnerReverseQuarantine('run-x');
    expect(result).toEqual({ retried: 0, recovered: 0, stillFailing: 0 });
    expect(mockGetCustomerById).not.toHaveBeenCalled();
  });

  it('removes the row when the retry succeeds (admin fixed the data)', async () => {
    mockGetDue.mockResolvedValueOnce([dueRow]);
    mockGetCustomerById.mockResolvedValueOnce({ id: 500, custom_attributes: [] });
    mockUpdateCustomerSalesrep.mockResolvedValueOnce({});

    const result = await processOwnerReverseQuarantine('run-x');

    expect(mockRemoveQ).toHaveBeenCalledWith('hs-1', '500');
    expect(mockQuarantine).not.toHaveBeenCalled();
    expect(result.recovered).toBe(1);
    expect(result.stillFailing).toBe(0);
  });

  it('re-quarantines (bumps attempts/backoff) when the retry still 400s', async () => {
    mockGetDue.mockResolvedValueOnce([dueRow]);
    mockGetCustomerById.mockResolvedValueOnce({ id: 500, custom_attributes: [] });
    mockUpdateCustomerSalesrep.mockRejectedValueOnce(
      Object.assign(new Error('400'), {
        response: { status: 400, data: { message: 'Invalid Phone Number' } },
      }),
    );

    const result = await processOwnerReverseQuarantine('run-x');

    expect(mockQuarantine).toHaveBeenCalledWith('hs-1', '500', '42', 'Invalid Phone Number');
    expect(mockRemoveQ).not.toHaveBeenCalled();
    expect(result.recovered).toBe(0);
    expect(result.stillFailing).toBe(1);
  });

  it('removes the row when the customer no longer exists in Magento (404)', async () => {
    mockGetDue.mockResolvedValueOnce([dueRow]);
    mockGetCustomerById.mockRejectedValueOnce(
      Object.assign(new Error('Not Found'), { response: { status: 404 } }),
    );

    await processOwnerReverseQuarantine('run-x');

    expect(mockRemoveQ).toHaveBeenCalledWith('hs-1', '500');
    expect(mockQuarantine).not.toHaveBeenCalled();
  });
});
