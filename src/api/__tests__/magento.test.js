import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';

vi.mock('axios');
vi.mock('../../config/index.js', () => ({
  config: {
    magento: { baseUrl: 'http://magento.test', token: 't', pageSize: 100, maxRecordsPerSync: 0, timeout: 5000 },
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

const { getOrdersUpdatedSince, getQualifyingOrdersByCustomerId } = await import('../magento.js');

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
