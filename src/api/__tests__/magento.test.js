import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';

vi.mock('axios');
vi.mock('../../config/index.js', () => ({
  config: {
    magento: { baseUrl: 'http://magento.test', token: 't', pageSize: 100, maxRecordsPerSync: 0, timeout: 5000 },
    sync: { excludedSalesrepIds: ['5', '6', '7'] },
  },
}));
vi.mock('../../utils/logger.js', () => ({
  default: { info: vi.fn(), debug: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));
vi.mock('../../utils/timeout.js', () => ({
  withTimeout: (fn) => fn(new AbortController().signal),
}));

const mockGet = vi.fn();
axios.create.mockReturnValue({
  interceptors: { response: { use: () => {} } },
  get: mockGet,
  put: vi.fn(),
});

const { getOrdersUpdatedSince, getQualifyingOrdersByCustomerId, getCustomersUpdatedSince } = await import('../magento.js');

describe('fetchAllPages – pagination loop guards', () => {
  beforeEach(() => mockGet.mockReset());

  it('breaks out when a page returns 0 items even if total_count > collected (avoids infinite loop)', async () => {
    // Simulates the production bug: total_count says 114, page 1 returns 20,
    // every subsequent page returns 0. The old code looped forever.
    mockGet.mockResolvedValueOnce({
      data: {
        items: Array.from({ length: 20 }, (_, i) => ({ entity_id: i + 1 })),
        total_count: 114,
      },
    });
    mockGet.mockResolvedValueOnce({ data: { items: [], total_count: 114 } });
    // If the bug came back, this third mock would be called and the test would
    // hang; the guard stops the loop after the first empty page.
    mockGet.mockResolvedValueOnce({ data: { items: [], total_count: 114 } });

    const since = new Date('2024-01-01T00:00:00Z');
    const orders = await getOrdersUpdatedSince(since);

    expect(orders).toHaveLength(20);
    expect(mockGet).toHaveBeenCalledTimes(2);
  });

  it('paginates fully when pages have items', async () => {
    mockGet.mockResolvedValueOnce({
      data: { items: Array.from({ length: 100 }, (_, i) => ({ entity_id: i + 1 })), total_count: 150 },
    });
    mockGet.mockResolvedValueOnce({
      data: { items: Array.from({ length: 50 }, (_, i) => ({ entity_id: 100 + i + 1 })), total_count: 150 },
    });

    const since = new Date('2024-01-01T00:00:00Z');
    const orders = await getOrdersUpdatedSince(since);

    expect(orders).toHaveLength(150);
    expect(mockGet).toHaveBeenCalledTimes(2);
  });

  it('pushes salesrep exclusion server-side (nin OR null) and returns last raw updated_at', async () => {
    const items = [
      { entity_id: 1, updated_at: '2024-01-01T01:00:00Z', custom_attributes: [{ attribute_code: 'salesrep_rep_id', value: '24' }] },
      { entity_id: 2, updated_at: '2024-01-01T02:00:00Z', custom_attributes: [] },
    ];
    mockGet.mockResolvedValueOnce({ data: { items, total_count: 2 } });

    const result = await getCustomersUpdatedSince(new Date('2024-01-01T00:00:00Z'));

    const url = decodeURIComponent(mockGet.mock.calls[0][0]);
    expect(url).toContain('searchCriteria[filterGroups][1][filters][0][field]=group_id');
    expect(url).toContain('searchCriteria[filterGroups][1][filters][0][conditionType]=nin');
    expect(url).toContain('searchCriteria[filterGroups][1][filters][0][value]=0,5,61,62,64');
    expect(url).not.toContain('searchCriteria[filterGroups][1][filters][0][value]=0,1,5,61,62,64');

    // Server-side filter: salesrep_rep_id NIN excluded list, OR is null
    expect(url).toContain('searchCriteria[filterGroups][2][filters][0][field]=salesrep_rep_id');
    expect(url).toContain('searchCriteria[filterGroups][2][filters][0][conditionType]=nin');
    expect(url).toContain('searchCriteria[filterGroups][2][filters][0][value]=5,6,7');
    expect(url).toContain('searchCriteria[filterGroups][2][filters][1][field]=salesrep_rep_id');
    expect(url).toContain('searchCriteria[filterGroups][2][filters][1][conditionType]=null');

    // High-water mark for cursor is the LAST raw record's updated_at
    expect(result.lastRawUpdatedAt).toEqual(new Date('2024-01-01T02:00:00Z'));
    expect(result.customers).toHaveLength(2);
  });

  it('filters blocked customer groups client-side while allowing group 1 through', async () => {
    const items = [
      { entity_id: 1, group_id: 1, updated_at: '2024-01-01T01:00:00Z', custom_attributes: [] },
      { entity_id: 2, group_id: 61, updated_at: '2024-01-01T02:00:00Z', custom_attributes: [] },
    ];
    mockGet.mockResolvedValueOnce({ data: { items, total_count: 2 } });

    const result = await getCustomersUpdatedSince(new Date('2024-01-01T00:00:00Z'));

    expect(result.customers).toEqual([items[0]]);
    expect(result.lastRawUpdatedAt).toEqual(new Date('2024-01-01T02:00:00Z'));
  });

  it('builds a customer qualifying-order lookup using grand_total gt min total', async () => {
    mockGet.mockResolvedValueOnce({
      data: { items: [{ entity_id: 1, customer_id: 200, grand_total: 501 }], total_count: 1 },
    });

    const orders = await getQualifyingOrdersByCustomerId(200, 500, 1);

    expect(orders).toHaveLength(1);
    const url = mockGet.mock.calls[0][0];
    expect(decodeURIComponent(url)).toContain('searchCriteria[filterGroups][0][filters][0][field]=customer_id');
    expect(decodeURIComponent(url)).toContain('searchCriteria[filterGroups][0][filters][0][conditionType]=eq');
    expect(decodeURIComponent(url)).toContain('searchCriteria[filterGroups][1][filters][0][field]=grand_total');
    expect(decodeURIComponent(url)).toContain('searchCriteria[filterGroups][1][filters][0][conditionType]=gt');
    expect(decodeURIComponent(url)).toContain('searchCriteria[filterGroups][1][filters][0][value]=500');
    expect(decodeURIComponent(url)).toContain('searchCriteria[pageSize]=1');
  });
});
