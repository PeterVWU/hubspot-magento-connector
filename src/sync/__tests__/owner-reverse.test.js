import { describe, it, expect, vi, beforeEach } from 'vitest';

// --- mock external dependencies ---
const mockGetMagentoIdsByHubspotId = vi.fn();
const mockUpsertMapping = vi.fn();
const mockLogSync = vi.fn();
const mockUpdateLastSyncedAt = vi.fn();
const mockQuarantineOwnerReverse = vi.fn();
const mockRemoveQuarantinedOwnerReverse = vi.fn();

vi.mock('../../db/sync-state.js', () => ({
  getMagentoIdsByHubspotId: mockGetMagentoIdsByHubspotId,
  upsertMapping: mockUpsertMapping,
  logSync: mockLogSync,
  updateLastSyncedAt: mockUpdateLastSyncedAt,
  quarantineOwnerReverse: mockQuarantineOwnerReverse,
  removeQuarantinedOwnerReverse: mockRemoveQuarantinedOwnerReverse,
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

vi.mock('../../config/index.js', () => ({
  config: { sync: { ownerReverseBatchSize: 500, ownerReverseProtectedSalesrepIds: ['175'] } },
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
    mockQuarantineOwnerReverse.mockResolvedValue();
    mockRemoveQuarantinedOwnerReverse.mockResolvedValue();
  });

  it('updates all Magento customer records when a contact maps to multiple scopes', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-1', 'owner-1'),
    ], hasMore: false });
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
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-1', 'owner-1'),
    ], hasMore: false });
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

  it('never overwrites a customer assigned to a protected Magento salesrep', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-protected', 'owner-1', 'anissa@vapeguysinc.com'),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['1750']);
    mockGetCustomerById.mockResolvedValueOnce({
      id: 1750,
      custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '175' }],
    });

    const result = await syncOwnersReverse(since, 'run-protected');

    expect(mockUpdateCustomerSalesrep).not.toHaveBeenCalled();
    expect(result.updated).toBe(0);
    expect(result.skipped).toBe(1);
  });

  it('skips a deleted Magento customer (404) without counting it as a failure', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-3', 'owner-1', 'deleted@test.com'),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['266228']);
    mockGetCustomerById.mockRejectedValueOnce(
      Object.assign(new Error('Not Found'), { response: { status: 404 } }),
    );

    const result = await syncOwnersReverse(since, 'run-3');

    expect(mockUpdateCustomerSalesrep).not.toHaveBeenCalled();
    expect(result.failed).toBe(0);
    expect(result.skipped).toBe(1);
  });

  it('quarantines a customer with invalid Magento data (400) instead of silently dropping it', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-4', 'owner-1', 'baddata@test.com'),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['42469']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 42469, website_id: 1, custom_attributes: [] });
    mockUpdateCustomerSalesrep.mockRejectedValueOnce(
      Object.assign(new Error('Bad Request'), {
        response: { status: 400, data: { message: 'Invalid City. Please use A-Z, a-z, 0-9, -, \', spaces' } },
      }),
    );

    const result = await syncOwnersReverse(since, 'run-4');

    expect(result.failed).toBe(0);
    expect(result.quarantined).toBe(1);
    expect(mockQuarantineOwnerReverse).toHaveBeenCalledWith(
      'hs-4', '42469', '42',
      'Invalid City. Please use A-Z, a-z, 0-9, -, \', spaces',
    );
  });

  it('saves the owner_reverse timestamp to DB after a successful run', async () => {
    const lastModifiedMs = '1704153600000'; // 2024-01-02
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-5', 'owner-1', 'ok@test.com', lastModifiedMs),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['500']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 500, website_id: 1, custom_attributes: [] });

    await syncOwnersReverse(since, 'run-5');

    expect(mockUpdateLastSyncedAt).toHaveBeenCalledWith(
      'owner_reverse',
      new Date(Number(lastModifiedMs)),
    );
  });

  it('skips concurrent calls while a previous run is still in progress', async () => {
    let unblock;
    const blockingSearch = new Promise(r => { unblock = r; });
    mockSearchContactsModifiedSince
      .mockReturnValueOnce(blockingSearch)
      .mockResolvedValue({ contacts: [], hasMore: false });

    const firstRun = syncOwnersReverse(since, 'run-concurrent-1');

    try {
      const secondResult = await syncOwnersReverse(since, 'run-concurrent-2');
      expect(secondResult.updated).toBe(0);
      expect(secondResult.failed).toBe(0);
      // search was only called for the first run, not the second
      expect(mockSearchContactsModifiedSince).toHaveBeenCalledTimes(1);
    } finally {
      unblock({ contacts: [], hasMore: false });
      await firstRun;
    }
  });

  it('saves the timestamp even when there are transient failures', async () => {
    const lastModifiedMs = '1704153600000'; // 2024-01-02
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-6', 'owner-1', 'fail@test.com', lastModifiedMs),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['600']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 600, website_id: 1, custom_attributes: [] });
    mockUpdateCustomerSalesrep.mockRejectedValueOnce(new Error('Network error'));

    const result = await syncOwnersReverse(since, 'run-6');

    expect(result.failed).toBe(1);
    // Transient errors (ECONNRESET etc.) must not block cursor advancement —
    // 404/400 are already classified as skips, so only network errors reach
    // failed, and re-processing 10k contacts forever is worse than skipping 3.
    expect(mockUpdateLastSyncedAt).toHaveBeenCalledWith(
      'owner_reverse',
      new Date(Number(lastModifiedMs)),
    );
  });

  it('parses ISO 8601 lastmodifieddate strings (CRM v3 search response format)', async () => {
    // HubSpot CRM v3 returns lastmodifieddate as an ISO string for some
    // accounts; the cursor must still advance correctly.
    mockSearchContactsModifiedSince.mockResolvedValueOnce({
      contacts: [makeContact('hs-iso', 'owner-1', 'iso@test.com', '2024-01-02T00:00:00.000Z')],
      hasMore: false,
    });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['910']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 910, custom_attributes: [] });

    await syncOwnersReverse(since, 'run-iso');

    expect(mockUpdateLastSyncedAt).toHaveBeenCalledWith(
      'owner_reverse',
      new Date('2024-01-02T00:00:00.000Z'),
    );
  });

  it('ignores contacts with non-numeric lastmodifieddate when advancing the cursor', async () => {
    // Mixed batch: one bad timestamp followed by one good one. The bad one
    // must not poison lastModifiedAt — postgres rejects Invalid Date.
    mockSearchContactsModifiedSince.mockResolvedValueOnce({
      contacts: [
        makeContact('hs-bad',  'owner-1', 'bad@test.com',  'not-a-number'),
        makeContact('hs-good', 'owner-1', 'good@test.com', '1704153600000'),
      ],
      hasMore: false,
    });
    mockGetMagentoIdsByHubspotId
      .mockResolvedValueOnce(['900'])
      .mockResolvedValueOnce(['901']);
    mockGetCustomerById
      .mockResolvedValueOnce({ id: 900, custom_attributes: [] })
      .mockResolvedValueOnce({ id: 901, custom_attributes: [] });

    await syncOwnersReverse(since, 'run-bad-date');

    expect(mockUpdateLastSyncedAt).toHaveBeenCalledWith(
      'owner_reverse',
      new Date(1704153600000),
    );
  });

  it('passes the configured batch size to the search and propagates hasMore', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({
      contacts: [makeContact('hs-cap', 'owner-1', 'cap@test.com', '1704067200500')],
      hasMore: true,
    });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['800']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 800, custom_attributes: [] });

    const result = await syncOwnersReverse(since, 'run-cap');

    expect(mockSearchContactsModifiedSince).toHaveBeenCalledWith(since, 500);
    expect(result.hasMore).toBe(true);
    // Cursor still advances on a capped batch so the next cycle picks up where we left off.
    expect(mockUpdateLastSyncedAt).toHaveBeenCalledWith('owner_reverse', new Date(1704067200500));
  });

  it('clears any prior quarantine row when an update succeeds', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-7', 'owner-1', 'recovered@test.com'),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['700']);
    mockGetCustomerById.mockResolvedValueOnce({ id: 700, website_id: 1, custom_attributes: [] });

    await syncOwnersReverse(since, 'run-7');

    expect(mockRemoveQuarantinedOwnerReverse).toHaveBeenCalledWith('hs-7', '700');
  });

  it('updates the single Magento record for a single-scope customer', async () => {
    mockSearchContactsModifiedSince.mockResolvedValueOnce({ contacts: [
      makeContact('hs-2', 'owner-1', 'single@test.com'),
    ], hasMore: false });
    mockGetMagentoIdsByHubspotId.mockResolvedValueOnce(['300']);
    const customer = { id: 300, website_id: 2, custom_attributes: [] };
    mockGetCustomerById.mockResolvedValueOnce(customer);

    const result = await syncOwnersReverse(since, 'run-2');

    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledOnce();
    expect(mockUpdateCustomerSalesrep).toHaveBeenCalledWith('300', '42', customer);
    expect(result.updated).toBe(1);
  });
});
