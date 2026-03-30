import { Worker, isMainThread, parentPort } from 'node:worker_threads';

if (!isMainThread) {
  // Worker thread: independent event loop that monitors heartbeats
  let lastHeartbeat = Date.now();
  let timeoutMs = 15 * 60 * 1000; // default 15 min

  parentPort.on('message', (msg) => {
    if (msg.type === 'config') {
      timeoutMs = msg.timeoutMs;
    }
    lastHeartbeat = Date.now();
  });

  setInterval(() => {
    const elapsed = Date.now() - lastHeartbeat;
    if (elapsed > timeoutMs) {
      console.error(`[watchdog] No heartbeat for ${Math.round(elapsed / 60000)} minutes, forcing process exit`);
      process.exit(1);
    }
  }, 60 * 1000); // check every minute
}

/**
 * Starts a worker thread watchdog that kills the process if no heartbeat
 * is received within the given timeout. Works even when the main event
 * loop is completely inert.
 *
 * @param {number} timeoutMinutes - minutes without heartbeat before exit
 * @returns {{ heartbeat: () => void }}
 */
export function startWatchdog(timeoutMinutes = 15) {
  const worker = new Worker(new URL(import.meta.url));
  worker.unref(); // don't prevent graceful shutdown
  worker.postMessage({ type: 'config', timeoutMs: timeoutMinutes * 60 * 1000 });
  return {
    heartbeat: () => worker.postMessage({ type: 'heartbeat' }),
  };
}
