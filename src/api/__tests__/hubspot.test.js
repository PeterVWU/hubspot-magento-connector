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

  function pageOf(...ids) {
    return {
      data: {
        results: ids.map(id => ({
          id, properties: { email: `${id}@b.com`, hubspot_owner_id: '10', lastmodifieddate: String(1704067200000 + Number(id)) },
        })),
        paging: null,
      },
    };
  }
  function pageWithNext(after, ...ids) {
    const r = pageOf(...ids);
    r.data.paging = { next: { after } };
    return r;
  }

  it('returns hasMore=false when only one page exists', async () => {
    mockPost.mockResolvedValueOnce(pageOf('1'));

    const { contacts, hasMore } = await searchContactsModifiedSince(since);

    expect(contacts).toHaveLength(1);
    expect(hasMore).toBe(false);
  });

  it('stops pagination and flags hasMore when a mid-pagination 400 occurs (10k cap)', async () => {
    mockPost.mockResolvedValueOnce(pageWithNext('100', '1', '2'));
    const apiError = Object.assign(new Error('Bad Request'), {
      response: { status: 400, data: { message: 'There was a problem with the request.' } },
      config: { url: '/crm/v3/objects/contacts/search', _retryCount: 0 },
    });
    mockPost.mockRejectedValueOnce(apiError);

    const { contacts, hasMore } = await searchContactsModifiedSince(since);

    expect(contacts.map(r => r.id)).toEqual(['1', '2']);
    expect(hasMore).toBe(true);
  });

  it('throws when a 400 occurs on the first page (genuine request error)', async () => {
    const apiError = Object.assign(new Error('Bad Request'), {
      response: { status: 400, data: { message: 'Invalid filter.' } },
      config: { url: '/crm/v3/objects/contacts/search', _retryCount: 0 },
    });
    mockPost.mockRejectedValueOnce(apiError);

    await expect(searchContactsModifiedSince(since)).rejects.toThrow('Bad Request');
  });

  it('stops paginating once maxResults is reached and reports hasMore=true', async () => {
    // Page 1 (100 contacts) brings total to 100; pages remain. Cap at 100 -> stop.
    const ids = Array.from({ length: 100 }, (_, i) => String(i + 1));
    mockPost.mockResolvedValueOnce(pageWithNext('next-cursor', ...ids));

    const { contacts, hasMore } = await searchContactsModifiedSince(since, 100);

    expect(contacts).toHaveLength(100);
    expect(hasMore).toBe(true);
    expect(mockPost).toHaveBeenCalledTimes(1);
  });

  it('keeps paginating past maxResults if the API has no more pages (hasMore=false)', async () => {
    // Page 1 returns 50 contacts, no next page. Cap at 100 doesn't trigger.
    const ids = Array.from({ length: 50 }, (_, i) => String(i + 1));
    mockPost.mockResolvedValueOnce(pageOf(...ids));

    const { contacts, hasMore } = await searchContactsModifiedSince(since, 100);

    expect(contacts).toHaveLength(50);
    expect(hasMore).toBe(false);
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
