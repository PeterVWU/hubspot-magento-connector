import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockGetRetryItems = vi.fn();
const mockRemoveRetryItem = vi.fn();
const mockUpdateRetryItem = vi.fn();
const mockUpsertMapping = vi.fn();
vi.mock('../../db/sync-state.js', () => ({
  getRetryItems: mockGetRetryItems,
  removeRetryItem: mockRemoveRetryItem,
  updateRetryItem: mockUpdateRetryItem,
  upsertMapping: mockUpsertMapping,
}));

const mockSearchContacts = vi.fn();
const mockCreateContact = vi.fn();
const mockUpdateContact = vi.fn();
vi.mock('../../api/hubspot.js', () => ({
  searchContacts: mockSearchContacts,
  createContact: mockCreateContact,
  updateContact: mockUpdateContact,
}));
vi.mock('../../api/magento.js', () => ({}));
vi.mock('../orders.js', () => ({ syncSingleOrder: vi.fn() }));
vi.mock('../../config/index.js', () => ({
  config: { sync: { excludedSalesrepIds: [] } },
}));
vi.mock('../../config/salesrep-mapping.js', () => ({ SALESREP_OWNER_MAP: {} }));
vi.mock('../../utils/logger.js', () => ({
  default: { info: vi.fn(), debug: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

const { processRetryQueue } = await import('../retry-queue.js');

describe('customer retries', () => {
  beforeEach(() => vi.clearAllMocks());

  function queueCustomer(groupId) {
    mockGetRetryItems.mockResolvedValueOnce([{
      id: 10,
      entity_type: 'customer',
      magento_id: 200,
      payload: { customer: { id: 200, email: 'customer@test.com', group_id: groupId } },
      attempts: 0,
      max_attempts: 3,
    }]);
  }

  it.each([1, '1'])('discards queued group %s customers before HubSpot writes', async (groupId) => {
    queueCustomer(groupId);

    await processRetryQueue('run-1');

    expect(mockSearchContacts).not.toHaveBeenCalled();
    expect(mockCreateContact).not.toHaveBeenCalled();
    expect(mockUpdateContact).not.toHaveBeenCalled();
    expect(mockUpsertMapping).not.toHaveBeenCalled();
    expect(mockRemoveRetryItem).toHaveBeenCalledWith(10);
    expect(mockUpdateRetryItem).not.toHaveBeenCalled();
  });

  it('retries eligible customers without looking up orders', async () => {
    queueCustomer(65);
    mockSearchContacts.mockResolvedValueOnce(null);
    mockCreateContact.mockResolvedValueOnce({ id: 'hs-200' });

    await processRetryQueue('run-2');

    expect(mockCreateContact).toHaveBeenCalledWith(expect.objectContaining({ email: 'customer@test.com' }));
    expect(mockUpsertMapping).toHaveBeenCalledWith('customer', 200, 'hs-200');
    expect(mockRemoveRetryItem).toHaveBeenCalledWith(10);
    expect(mockUpdateRetryItem).not.toHaveBeenCalled();
  });
});
