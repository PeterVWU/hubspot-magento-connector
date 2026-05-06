import { describe, it, expect, vi, beforeEach } from 'vitest';

// --- mock external dependencies ---
const mockGetMagentoIdsByHubspotId = vi.fn();
const mockUpsertMapping = vi.fn();
const mockLogSync = vi.fn();
const mockUpdateLastSyncedAt = vi.fn();

vi.mock('../../db/sync-state.js', () => ({
  getMagentoIdsByHubspotId: mockGetMagentoIdsByHubspotId,
  upsertMapping: mockUpsertMapping,
  logSync: mockLogSync,
  updateLastSyncedAt: mockUpdateLastSyncedAt,
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

function makeContact(id, ownerId, email = `${id}@test.com`, lastmodifieddate = '1704067200000') {
  return {
    id,
    properties: { hubspot_owner_id: ownerId, email, lastmodifieddate },
  };
}

describe('syncOwnersReverse – multi-scope customers', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockLogSync.mockResolvedValue();
    mockUpdateLastSyncedAt.mockResolvedValue();
    mockUpdateCustomerSalesrep.mockResolvedValue({});
  });

  it('updates all Magento customer records when a contact maps to multiple scopes', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-1', 'owner-1'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['100', '200']);
    const customer100 = { id: 100, website_id: 1, custom_attributes: [] };
    const customer200 = { id: 200, website_id: 2, custom_attributes: [] };
    mockGetCustomerById
      .mockResolvedValueOnce(customer100)
      .mockResolvedValueOnce(customer200);

    const result = await syncOwnersReverse(since, 'run-1');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledTimes(2);
    // existing customer object is passed through to avoid a second fetch
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('100', '42', customer100);
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('200', '42', customer200);
    expect(result.updated).toBe(2);
  });

  it('skips scopes already assigned the correct salesrep', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-1', 'owner-1'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['100', '200']);
    mockGetCustomerById
      .mockResolvedValueOnce({
        id: 100,
        website_id: 1,
        custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '42' }],
      })
      .mockResolvedValueOnce({ id: 200, website_id: 2, custom_attributes: [] });

    const result = await syncOwnersReverse(since, 'run-1');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledTimes(1);
    expect(result.updated).toBe(1);
    expect(result.skipped).toBe(1);
  });

  it('skips a deleted Magento customer (404) without counting it as a failure', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-3', 'owner-1', 'deleted@test.com'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['266228']);
    mockGetCustomerById.mockRejectedValueOnce(
      Object.assign(new Error('Not Found'), { response: { status: 404 } }),
    );

    const result = await syncOwnersReverse(since, 'run-3');

    expect(mockUpdateCustomerSalesrep).not.toHaveBeenCalled();
    expect(result.failed).toBe(0);
    expect(result.skipped).toBe(1);
  });

  it('skips a customer with invalid Magento data (400) without counting it as a failure', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-4', 'owner-1', 'baddata@test.com'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['42469']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 42469, website_id: 1, custom_attributes: [] });
    mockUpdateCustomerSalesrep.mockRejectedValueOnce(
      Object.assign(new Error('Bad Request'), {
        response: { status: 400, data: { message: 'Invalid City. Please use A-Z, a-z, 0-9, -, \', spaces' } },
      }),
    );

    const result = await syncOwnersReverse(since, 'run-4');

    expect(result.failed).toBe(0);
    expect(result.skipped).toBe(1);
  });

  it('saves the owner_reverse timestamp to DB after a successful run', async () => {
    const lastModifiedMs = '1704153600000'; // 2024-01-02
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-5', 'owner-1', 'ok@test.com', lastModifiedMs),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['500']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 500, website_id: 1, custom_attributes: [] });

    await syncOwnersReverse(since, 'run-5');

    expect(mockUpdateLastSyncedAt).toHaveBeenCalledWith(
      'owner_reverse',
      new Date(Number(lastModifiedMs)),
    );
  });

  it('does not save the timestamp when there are failures', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-6', 'owner-1', 'fail@test.com'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['600']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 600, website_id: 1, custom_attributes: [] });
    mockUpdateCustomerSalesrep.mockRejectedValueOnce(new Error('Network error'));

    const result = await syncOwnersReverse(since, 'run-6');

    expect(result.failed).toBe(1);
    expect(mockUpdateLastSyncedAt).not.toHaveBeenCalled();
  });

  it('updates the single Magento record for a single-scope customer', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce([
      makeContact('hs-2', 'owner-1', 'single@test.com'),
    ]);
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['300']);
    const customer = { id: 300, website_id: 2, custom_attributes: [] };
    mockGetCustomerById.mockResolvedValueOnce(customer);

    const result = await syncOwnersReverse(since, 'run-2');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledOnce();
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('300', '42', customer);
    expect(result.updated).toBe(1);
  });
});
