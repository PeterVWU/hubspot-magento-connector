import { describe, it, expect, vi, beforeEach } from 'vitest';

const mockGetOrdersUpdatedSince = vi.fn();
const mockGetCustomerById = vi.fn();
vi.mock('../../api/magento.js', () => ({
  getOrdersUpdatedSince: mockGetOrdersUpdatedSince,
  getCustomerById: mockGetCustomerById,
}));

const mockGetDealPipelines = vi.fn();
const mockSearchDealByOrderNumber = vi.fn();
const mockCreateDeal = vi.fn();
const mockUpdateDeal = vi.fn();
const mockSearchContacts = vi.fn();
const mockCreateContact = vi.fn();
const mockUpdateContact = vi.fn();
const mockBatchCreateAssociations = vi.fn();
const mockBatchCreateLineItems = vi.fn();
const mockBatchUpdateLineItems = vi.fn();
vi.mock('../../api/hubspot.js', () => ({
  getDealPipelines: mockGetDealPipelines,
  searchDealByOrderNumber: mockSearchDealByOrderNumber,
  createDeal: mockCreateDeal,
  updateDeal: mockUpdateDeal,
  searchContacts: mockSearchContacts,
  createContact: mockCreateContact,
  updateContact: mockUpdateContact,
  batchCreateAssociations: mockBatchCreateAssociations,
  batchCreateLineItems: mockBatchCreateLineItems,
  batchUpdateLineItems: mockBatchUpdateLineItems,
}));

const mockGetHubspotId = vi.fn();
const mockGetHubspotIdsBatch = vi.fn();
const mockUpsertMapping = vi.fn();
const mockAddToRetryQueue = vi.fn();
const mockLogSync = vi.fn();
vi.mock('../../db/sync-state.js', () => ({
  getHubspotId: mockGetHubspotId,
  getHubspotIdsBatch: mockGetHubspotIdsBatch,
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

const { syncSingleOrder } = await import('../orders.js');

function order(overrides = {}) {
  return {
    entity_id: 100,
    increment_id: '000100',
    customer_id: 200,
    grand_total: '501.00',
    status: 'processing',
    created_at: '2024-01-01T00:00:00Z',
    items: [],
    ...overrides,
  };
}

function customer(overrides = {}) {
  return {
    id: 200,
    email: 'customer@test.com',
    firstname: 'Test',
    lastname: 'Customer',
    group_id: 1,
    custom_attributes: [],
    ...overrides,
  };
}

describe('syncSingleOrder qualification gate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGetDealPipelines.mockResolvedValue([{
      id: 'pipeline-1',
      label: 'Ecommerce Pipeline',
      stages: [{ label: 'Processing', id: 'stage-processing' }],
    }]);
    mockLogSync.mockResolvedValue();
    mockBatchCreateAssociations.mockResolvedValue([]);
  });

  it('skips orders at or below the threshold before any HubSpot or DB writes', async () => {
    const result = await syncSingleOrder(order({ grand_total: '500.00' }), 'run-1');

    expect(result).toEqual({ skipped: true, reason: 'order_total_below_threshold' });
    expect(mockGetCustomerById).not.toHaveBeenCalled();
    expect(mockGetDealPipelines).not.toHaveBeenCalled();
    expect(mockGetHubspotId).not.toHaveBeenCalled();
    expect(mockCreateDeal).not.toHaveBeenCalled();
  });

  it('skips qualifying orders for fraud customers before HubSpot writes', async () => {
    mockGetCustomerById.mockResolvedValueOnce(customer({ group_id: 5 }));

    const result = await syncSingleOrder(order(), 'run-2');

    expect(result).toEqual({ skipped: true, reason: 'ineligible_customer' });
    expect(mockGetDealPipelines).not.toHaveBeenCalled();
    expect(mockCreateDeal).not.toHaveBeenCalled();
  });

  it('creates deal and contact for a qualifying order from an eligible customer', async () => {
    mockGetCustomerById.mockResolvedValueOnce(customer());
    mockGetHubspotId
      .mockResolvedValueOnce(null)
      .mockResolvedValueOnce(null);
    mockSearchDealByOrderNumber.mockResolvedValueOnce(null);
    mockCreateDeal.mockResolvedValueOnce({ id: 'hs-deal-1' });
    mockSearchContacts.mockResolvedValueOnce(null);
    mockCreateContact.mockResolvedValueOnce({ id: 'hs-contact-1' });

    const result = await syncSingleOrder(order(), 'run-3');

    expect(result.skipped).toBe(false);
    expect(result.action).toBe('created');
    expect(mockCreateDeal).toHaveBeenCalledWith(expect.objectContaining({ amount: '501.00' }));
    expect(mockCreateContact).toHaveBeenCalledWith(expect.objectContaining({ email: 'customer@test.com' }));
    expect(mockBatchCreateAssociations).toHaveBeenCalledOnce();
    expect(mockUpsertMapping).toHaveBeenCalledWith('order', 100, 'hs-deal-1');
    expect(mockUpsertMapping).toHaveBeenCalledWith('customer', 200, 'hs-contact-1');
  });
});
