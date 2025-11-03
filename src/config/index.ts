import dotenv from 'dotenv';
import path from 'path';

// Load environment variables
dotenv.config();

export const config = {
  app: {
    name: process.env.APP_NAME || 'Diyetlenio',
    env: process.env.NODE_ENV || 'development',
    url: process.env.APP_URL || 'http://localhost:3000',
    port: parseInt(process.env.APP_PORT || '3000', 10),
    key: process.env.APP_KEY || '',
  },

  database: {
    url: process.env.DATABASE_URL || '',
  },

  jwt: {
    secret: process.env.JWT_SECRET || '',
    expiresIn: process.env.JWT_EXPIRES_IN || '7d',
    refreshSecret: process.env.JWT_REFRESH_SECRET || '',
    refreshExpiresIn: process.env.JWT_REFRESH_EXPIRES_IN || '30d',
  },

  session: {
    secret: process.env.SESSION_SECRET || '',
    lifetime: parseInt(process.env.SESSION_LIFETIME || '7200', 10),
  },

  mail: {
    driver: process.env.MAIL_DRIVER || 'smtp',
    host: process.env.MAIL_HOST || 'smtp.gmail.com',
    port: parseInt(process.env.MAIL_PORT || '587', 10),
    username: process.env.MAIL_USERNAME || '',
    password: process.env.MAIL_PASSWORD || '',
    encryption: process.env.MAIL_ENCRYPTION || 'tls',
    from: {
      address: process.env.MAIL_FROM_ADDRESS || 'noreply@diyetlenio.com',
      name: process.env.MAIL_FROM_NAME || 'Diyetlenio',
    },
  },

  upload: {
    maxSize: parseInt(process.env.UPLOAD_MAX_SIZE || '10485760', 10), // 10MB
    path: process.env.UPLOAD_PATH || path.join(__dirname, '../../assets/uploads'),
    allowedTypes: {
      images: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
      documents: ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
    },
  },

  rateLimit: {
    windowMs: parseInt(process.env.RATE_LIMIT_WINDOW || '60000', 10),
    maxRequests: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS || '100', 10),
  },

  webrtc: {
    signalingPort: parseInt(process.env.SIGNALING_SERVER_PORT || '3001', 10),
    stunServer: process.env.STUN_SERVER || 'stun:stun.l.google.com:19302',
  },

  cache: {
    enabled: process.env.CACHE_ENABLED === 'true',
    ttl: parseInt(process.env.CACHE_TTL || '3600', 10),
  },

  appointments: {
    defaultDuration: parseInt(process.env.APPOINTMENT_DURATION || '45', 10),
    cancellationHours: parseInt(process.env.CANCELLATION_HOURS || '2', 10),
    reminderHours: parseInt(process.env.REMINDER_HOURS || '1', 10),
  },

  commission: {
    rate: parseFloat(process.env.COMMISSION_RATE || '0.15'),
  },

  cors: {
    origin: process.env.CORS_ORIGIN || 'http://localhost:3000',
  },

  logging: {
    level: process.env.LOG_LEVEL || 'info',
    file: process.env.LOG_FILE || path.join(__dirname, '../../logs/app.log'),
  },
};

// Validate required environment variables
const requiredEnvVars = ['DATABASE_URL', 'JWT_SECRET', 'SESSION_SECRET'];

if (config.app.env === 'production') {
  requiredEnvVars.push('APP_KEY', 'MAIL_USERNAME', 'MAIL_PASSWORD');
}

for (const envVar of requiredEnvVars) {
  if (!process.env[envVar]) {
    throw new Error(`Missing required environment variable: ${envVar}`);
  }
}
