/**
 * Wraps an async function that accepts an AbortSignal with a hard timeout.
 * If the function doesn't resolve within `ms` milliseconds, the signal aborts
 * and the returned promise rejects with an error.
 *
 * @param {(signal: AbortSignal) => Promise<T>} fn - async function receiving an AbortSignal
 * @param {number} ms - timeout in milliseconds
 * @returns {Promise<T>}
 */
export function withTimeout(fn, ms) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), ms);
  return fn(controller.signal).finally(() => clearTimeout(timer));
}
