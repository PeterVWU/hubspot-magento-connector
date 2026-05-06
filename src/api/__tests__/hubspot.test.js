import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';

vi.mock('axios');
vi.mock('../../config/index.js', () => ({
  config: {
    hubspot: { accessToken: 'test-token', timeout: 5000, maxRetries: 3 },
  },
}));
vi.mock('../../utils/logger.js', () => ({
  default: { info: vi.fn(), debug: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));
vi.mock('../../utils/timeout.js', () => ({
  withTimeout: (fn) => fn(new AbortController().signal),
}));

// Capture the interceptors registered on the mock client
let errorInterceptor;
const mockPost = vi.fn();
const mockRequest = vi.fn();

axios.create.mockReturnValue({
  interceptors: {
    response: {
      use: (_ok, onError) => { errorInterceptor = onError; },
    },
  },
  post: mockPost,
  request: mockRequest,
});

const { searchContactsModifiedSince, createContactProperty } = await import('../hubspot.js');

const since = new Date('2026-01-01T00:00:00Z');

describe('searchContactsModifiedSince – pagination cap', () => {
  beforeEach(() => mockPost.mockReset());

  it('returns results from page 1 when no further pages exist', async () => {
    mockPost.mockResolvedValueOnce({
      data: {
        results: [{ id: '1', properties: { email: 'a@b.com', hubspot_owner_id: '10', lastmodifieddate: '1704067200000' } }],
        paging: null,
      },
    });

    const results = await searchContactsModifiedSince(since);

    expect(results).toHaveLength(1);
    expect(results[0].id).toBe('1');
  });

  it('stops pagination and returns collected results when a mid-pagination 400 occurs', async () => {
    // Page 1 succeeds with 2 contacts
    mockPost.mockResolvedValueOnce({
      data: {
        results: [
          { id: '1', properties: { email: 'a@b.com', hubspot_owner_id: '10', lastmodifieddate: '1704067200000' } },
          { id: '2', properties: { email: 'b@b.com', hubspot_owner_id: '10', lastmodifieddate: '1704067200001' } },
        ],
        paging: { next: { after: '100' } },
      },
    });
    // Page 2 returns 400 (HubSpot 10k cap)
    const apiError = Object.assign(new Error('Bad Request'), {
      response: { status: 400, data: { message: 'There was a problem with the request.' } },
      config: { url: '/crm/v3/objects/contacts/search', _retryCount: 0 },
    });
    mockPost.mockRejectedValueOnce(apiError);

    const results = await searchContactsModifiedSince(since);

    expect(results).toHaveLength(2);
    expect(results.map(r => r.id)).toEqual(['1', '2']);
  });

  it('throws when a 400 occurs on the first page (genuine request error)', async () => {
    const apiError = Object.assign(new Error('Bad Request'), {
      response: { status: 400, data: { message: 'Invalid filter.' } },
      config: { url: '/crm/v3/objects/contacts/search', _retryCount: 0 },
    });
    mockPost.mockRejectedValueOnce(apiError);

    await expect(searchContactsModifiedSince(since)).rejects.toThrow('Bad Request');
  });
});

describe('createContactProperty – 409 suppression', () => {
  beforeEach(() => mockPost.mockReset());

  it('returns null silently when property already exists (409)', async () => {
    // Simulate the interceptor NOT firing (caller catches 409 directly via error)
    const apiError = Object.assign(new Error('Conflict'), {
      response: {
        status: 409,
        data: { message: "A property named 'foo' already exists." },
      },
      config: { url: '/crm/v3/properties/contacts', _retryCount: 0 },
    });
    mockPost.mockRejectedValueOnce(apiError);

    const result = await createContactProperty('foo', 'Foo');

    expect(result).toBeNull();
  });
});
