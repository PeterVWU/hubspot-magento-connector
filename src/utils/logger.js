import winston from 'winston';
import { config } from '../config/index.js';

const logger = winston.createLogger({
  level: config.log.level,
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json(),
  ),
  defaultMeta: { service: 'hubspot-magento-sync' },
  transports: [
    new winston.transports.Console({
      format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.printf(({ timestamp, level, message, service, ...meta }) => {
          let metaStr = '';
          if (Object.keys(meta).length) {
            try {
              metaStr = ` ${JSON.stringify(meta)}`;
            } catch {
              metaStr = ' [meta serialization failed]';
            }
          }
          return `${timestamp} [${level}] ${message}${metaStr}`;
        }),
      ),
    }),
    new winston.transports.File({
      filename: 'logs/error.log',
      level: 'error',
      maxsize: 10 * 1024 * 1024,
      maxFiles: 5,
    }),
    new winston.transports.File({
      filename: 'logs/combined.log',
      maxsize: 10 * 1024 * 1024,
      maxFiles: 5,
    }),
  ],
});

// Prevent transport errors from silently killing logging
logger.on('error', (err) => {
  process.stderr.write(`[logger error] ${err.message}\n`);
});

export default logger;
