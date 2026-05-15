import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockGetCustomersUpdatedSince = vi.fn();
const mockGetQualifyingOrdersByCustomerId = vi.fn();
vi.mock('../../api/magento.js', () => ({
  getCustomersUpdatedSince: mockGetCustomersUpdatedSince,
  getQualifyingOrdersByCustomerId: mockGetQualifyingOrdersByCustomerId,
}));

const mockSearchContacts = vi.fn();
const mockCreateContact = vi.fn();
const mockUpdateContact = vi.fn();
vi.mock('../../api/hubspot.js', () => ({
  searchContacts: mockSearchContacts,
  createContact: mockCreateContact,
  updateContact: mockUpdateContact,
}));

const mockGetHubspotId = vi.fn();
const mockUpsertMapping = vi.fn();
const mockAddToRetryQueue = vi.fn();
const mockLogSync = vi.fn();
vi.mock('../../db/sync-state.js', () => ({
  getHubspotId: mockGetHubspotId,
  upsertMapping: mockUpsertMapping,
  addToRetryQueue: mockAddToRetryQueue,
  logSync: mockLogSync,
}));

vi.mock('../../config/index.js', () => ({
  config: {
    sync: {
      customerMinOrderTotal: 500,
      excludedSalesrepIds: [],
    },
  },
}));

vi.mock('../../config/salesrep-mapping.js', () => ({
  SALESREP_OWNER_MAP: {},
}));

vi.mock('../../utils/logger.js', () => ({
  default: { info: vi.fn(), debug: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

const { syncCustomers } = await import('../customers.js');

const since = new Date('2024-01-01T00:00:00Z');

function customer(id) {
  return {
    id,
    email: `${id}@test.com`,
    firstname: 'Test',
    lastname: 'Customer',
    updated_at: `2024-01-01T00:0${id}:00Z`,
    custom_attributes: [],
  };
}

describe('syncCustomers qualification gate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockLogSync.mockResolvedValue();
  });

  it('skips customers that do not have a qualifying historical order', async () => {
    mockGetCustomersUpdatedSince.mockResolvedValueOnce({
      customers: [customer(1)],
      lastRawUpdatedAt: new Date('2024-01-01T00:01:00Z'),
    });
    mockGetQualifyingOrdersByCustomerId.mockResolvedValueOnce([]);

    const result = await syncCustomers(since, 'run-1');

    expect(result.skipped).toBe(1);
    expect(mockSearchContacts).not.toHaveBeenCalled();
    expect(mockCreateContact).not.toHaveBeenCalled();
    expect(mockUpdateContact).not.toHaveBeenCalled();
    expect(mockGetHubspotId).not.toHaveBeenCalled();
  });

  it('creates a contact only when the customer has a qualifying order', async () => {
    mockGetCustomersUpdatedSince.mockResolvedValueOnce({
      customers: [customer(2)],
      lastRawUpdatedAt: new Date('2024-01-01T00:02:00Z'),
    });
    mockGetQualifyingOrdersByCustomerId.mockResolvedValueOnce([{ entity_id: 10, grand_total: '501.00' }]);
    mockGetHubspotId.mockResolvedValueOnce(null);
    mockSearchContacts.mockResolvedValueOnce(null);
    mockCreateContact.mockResolvedValueOnce({ id: 'hs-contact-2' });

    const result = await syncCustomers(since, 'run-2');

    expect(result.created).toBe(1);
    expect(mockCreateContact).toHaveBeenCalledWith(expect.objectContaining({ email: '2@test.com' }));
    expect(mockUpsertMapping).toHaveBeenCalledWith('customer', 2, 'hs-contact-2');
  });

  it('advances the cursor by the last raw record, not the last filtered one', async () => {
    // 1 included customer, but the raw batch reached further into the timeline.
    const rawHighWater = new Date('2024-01-02T05:00:00Z');
    mockGetCustomersUpdatedSince.mockResolvedValueOnce({
      customers: [customer(3)],
      lastRawUpdatedAt: rawHighWater,
    });
    mockGetQualifyingOrdersByCustomerId.mockResolvedValueOnce([]);

    const result = await syncCustomers(since, 'run-3');

    expect(result.lastUpdatedAt).toEqual(rawHighWater);
  });
});
