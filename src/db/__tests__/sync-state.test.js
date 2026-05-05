import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock the pool before importing the module under test
const mockQuery = vi.fn();
vi.mock('../index.js', () => ({
  default: { query: mockQuery },
}));

const { getMagentoIdsByHubspotId } = await import('../sync-state.js');

describe('getMagentoIdsByHubspotId', () => {
  beforeEach(() => mockQuery.mockReset());

  it('returns all magento IDs mapped to a hubspot ID', async () => {
    mockQuery.mockResolvedValueOnce({
      rows: [{ magento_id: '100' }, { magento_id: '200' }],
    });

    const ids = await getMagentoIdsByHubspotId('customer', 'hs-X');

    expect(ids).toEqual(['100', '200']);
  });

  it('returns empty array when no mapping exists', async () => {
    mockQuery.mockResolvedValueOnce({ rows: [] });

    const ids = await getMagentoIdsByHubspotId('customer', 'hs-unknown');

    expect(ids).toEqual([]);
  });

  it('queries on entity_type and hubspot_id', async () => {
    mockQuery.mockResolvedValueOnce({ rows: [] });

    await getMagentoIdsByHubspotId('customer', 'hs-X');

    expect(mockQuery).toHaveBeenCalledWith(
      expect.stringContaining('hubspot_id'),
      ['customer', 'hs-X'],
    );
  });
});
