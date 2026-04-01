import { config } from '../config/index.js';

const LEVELS = { error: 0, warn: 1, info: 2, debug: 3 };
const configuredLevel = LEVELS[config.log.level] ?? LEVELS.info;

function write(level, message, meta = {}) {
  if (LEVELS[level] > configuredLevel) return;

  let metaStr = '';
  if (Object.keys(meta).length) {
    try {
      metaStr = ` ${JSON.stringify(meta)}`;
    } catch {
      metaStr = ' [meta serialization failed]';
    }
  }

  const line = `${new Date().toISOString()} [${level}] ${message}${metaStr}\n`;
  process.stdout.write(line);
}

const logger = {
  error: (message, meta) => write('error', message, meta),
  warn: (message, meta) => write('warn', message, meta),
  info: (message, meta) => write('info', message, meta),
  debug: (message, meta) => write('debug', message, meta),
};

export default logger;
