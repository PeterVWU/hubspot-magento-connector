import { describe, it, expect, vi, beforeEach } from 'vitest';

// --- mock external dependencies ---
const mockGetMagentoIdsByHubspotId = vi.fn();
const mockUpsertMapping = vi.fn();
const mockLogSync = vi.fn();

vi.mock('../../db/sync-state.js', () => ({
  getMagentoIdsByHubspotId: mockGetMagentoIdsByHubspotId,
  upsertMapping: mockUpsertMapping,
  logSync: mockLogSync,
}));

const mockSearchContactsModifiedSince = vi.fn();
vi.mock('../../api/hubspot.js', () => ({
  searchContactsModifiedSince: mockSearchContactsModifiedSince,
}));

const mockGetCustomerById = vi.fn();
const mockGetCustomersByEmail = vi.fn();
const mockUpdateCustomerSalesrep = vi.fn();
vi.mock('../../api/magento.js', () => ({
  getCustomerById: mockGetCustomerById,
  getCustomersByEmail: mockGetCustomersByEmail,
  updateCustomerSalesrep: mockUpdateCustomerSalesrep,
}));

vi.mock('../../config/salesrep-mapping.js', () => ({
  OWNER_SALESREP_MAP: { 'owner-1': '42' },
}));

vi.mock('../../utils/logger.js', () => ({
  default: { info: vi.fn(), debug: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

const { syncOwnersReverse } = await import('../owner-reverse.js');

const since = new Date('2024-01-01T00:00:00Z');

function makeContact(id, ownerId, email = `${id}@test.com`) {
  return {
    id,
    properties: {
      hubspot_owner_id: ownerId,
      email,
      lastmodifieddate: '1704067200000',
    },
  };
}

describe('syncOwnersReverse – multi-scope customers', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockLogSync.mockResolvedValue();
    mockUpdateCustomerSalesrep.mockResolvedValue({});
  });

  it('updates all Magento customer records when a contact maps to multiple scopes', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-1', 'owner-1'),
    ]);
    // Two Magento accounts for this HubSpot contact (website 1 and website 2)
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['100', '200']);
    mockGetCustomerById
      .mockResolvedValueOnce({ id: 100, website_id: 1, custom_attributes: [] })
      .mockResolvedValueOnce({ id: 200, website_id: 2, custom_attributes: [] });

    const result = await syncOwnersReverse(since, 'run-1');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledTimes(2);
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('100', '42');
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('200', '42');
    expect(result.updated).toBe(2);
  });

  it('skips scopes already assigned the correct salesrep', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-1', 'owner-1'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['100', '200']);
    // scope 100 already has the right salesrep; scope 200 does not
    mockGetCustomerById
      .mockResolvedValueOnce({
        id: 100,
        website_id: 1,
        custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '42' }],
      })
      .mockResolvedValueOnce({ id: 200, website_id: 2, custom_attributes: [] });

    const result = await syncOwnersReverse(since, 'run-1');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledTimes(1);
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('200', '42');
    expect(result.updated).toBe(1);
    expect(result.skipped).toBe(1);
  });

  it('skips a deleted Magento customer (404) without counting it as a failure', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-3', 'owner-1', 'deleted@test.com'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['266228']);
    const notFound = Object.assign(new Error('Not Found'), {
      response: { status: 404 },
    });
    mockGetCustomerById.mockRejectedValueOnce(notFound);

    const result = await syncOwnersReverse(since, 'run-3');

    expect(mockUpdateCustomerSalesrep).not.toHaveBeenCalled();
    expect(result.failed).toBe(0);
    expect(result.skipped).toBe(1);
  });

  it('updates the single Magento record for a single-scope customer', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-2', 'owner-1', 'single@test.com'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['300']);
    mockGetCustomerById.mockResolvedValueOnce({
      id: 300,
      website_id: 2,
      custom_attributes: [],
    });

    const result = await syncOwnersReverse(since, 'run-2');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledOnce();
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('300', '42');
    expect(result.updated).toBe(1);
  });
});
