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

const { getOrdersUpdatedSince } = await import('../magento.js');

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
});
