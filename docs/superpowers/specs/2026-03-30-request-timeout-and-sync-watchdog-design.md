# Request Timeout & Sync Watchdog Design

## Problem

The sync service hangs indefinitely when HTTP requests to Magento or HubSpot stall. Three failure modes have been observed in production:

1. **Axios `timeout` doesn't cover the full request lifecycle.** It only fires if the server never starts responding. If a connection is established and headers trickle in, the request hangs forever.
2. **HubSpot 429 retry has no limit.** The response interceptor retries rate-limited requests indefinitely.
3. **No sync-level timeout.** A stuck HTTP call blocks `runFullSync()`, `syncInProgress` stays `true`, the cron keeps skipping, and the event loop eventually goes inert (zero TCP connections, zero timers firing). The process becomes a zombie that Docker never restarts.

## Solution

Three layers of defense, each independently preventing a different failure mode.

### Layer 1: Per-request AbortController timeout

Add a `withTimeout(fn, ms)` utility that wraps any async axios call with an `AbortController`. If the request doesn't complete within the timeout, the signal aborts it regardless of connection state.

```js
function withTimeout(fn, ms) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);
  return fn(controller.signal).finally(() => clearTimeout(timer));
}
```

**Magento client:** Every `client.get()` call passes `{ signal }` to axios. Default 120s, configurable via `MAGENTO_TIMEOUT_MS`. Replaces the current axios `timeout` option.

**HubSpot client:** Same pattern. Default 30s per request, configurable via `HUBSPOT_TIMEOUT_MS`. The signal is passed through to axios so it aborts the full request lifecycle.

### Layer 2: HubSpot 429 retry cap

The 429 interceptor tracks retry count on the request config object. After 3 attempts (configurable via `HUBSPOT_MAX_RETRIES`), it throws the error instead of retrying.

```js
if (status === 429) {
  const retries = (error.config._retryCount || 0) + 1;
  if (retries > 3) {
    logger.error('HubSpot rate limit retries exhausted', { url, retries });
    throw error;
  }
  error.config._retryCount = retries;
  // ... existing retry-after delay and re-request
}
```

### Layer 3: Sync-level timeout (10 minutes)

In `scheduler.js`, the sync body is extracted to a `runSyncBody(runId, syncStart)` helper. `runFullSync()` races it against a timeout promise:

```js
await Promise.race([
  runSyncBody(runId, syncStart),
  new Promise((_, reject) =>
    setTimeout(() => reject(new Error(`Sync timed out after ${timeoutMinutes} minutes`)), timeoutMs)
  ),
]);
```

When the timeout fires, the Promise rejects, the catch logs it, `finally` resets `syncInProgress`, and the next cron cycle runs normally.

Configurable via `SYNC_TIMEOUT_MINUTES` env var, default 10.

Note: a timed-out HTTP request may still be in-flight until the OS kills the socket, but `syncInProgress` is freed so the app is not stuck. The dangling request resolves/rejects eventually and gets garbage collected.

## Files to modify

| File | Change |
|------|--------|
| `src/utils/timeout.js` | New file: shared `withTimeout(fn, ms)` utility used by both API clients |
| `src/config/index.js` | Add `hubspot.timeout`, `hubspot.maxRetries`, `sync.timeoutMinutes` config |
| `src/api/magento.js` | Replace axios `timeout` with `withTimeout` wrapper on every request |
| `src/api/hubspot.js` | Add `withTimeout` wrapper on every request; add retry cap to 429 interceptor |
| `src/sync/scheduler.js` | Extract `runSyncBody()`, wrap with `Promise.race` timeout |

## New environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `HUBSPOT_TIMEOUT_MS` | `30000` | Per-request hard timeout for HubSpot API calls |
| `HUBSPOT_MAX_RETRIES` | `3` | Max 429 retry attempts before failing |
| `SYNC_TIMEOUT_MINUTES` | `10` | Max duration for a full sync cycle |
| `MAGENTO_TIMEOUT_MS` | `120000` | Already exists, now used with AbortController instead of axios timeout |
