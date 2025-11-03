import express, { Application } from 'express';
import 'express-async-errors';
import cors from 'cors';
import helmet from 'helmet';
import compression from 'compression';
import cookieParser from 'cookie-parser';
import morgan from 'morgan';
import http from 'http';
import { config } from './config';
import { logger } from './utils/logger';
import { errorHandler } from './middlewares/error-handler';
import { notFoundHandler } from './middlewares/not-found-handler';
import { domainRedirect } from './middlewares/domain-redirect';
import { SignalingServer } from './signaling-server';

// Routes
import authRoutes from './routes/auth.routes';
import userRoutes from './routes/user.routes';
import appointmentRoutes from './routes/appointment.routes';
import messageRoutes from './routes/message.routes';
import articleRoutes from './routes/article.routes';
import recipeRoutes from './routes/recipe.routes';
import paymentRoutes from './routes/payment.routes';

const app: Application = express();

// ============================================
// MIDDLEWARES
// ============================================

// Domain redirect (must be first)
app.use(domainRedirect);

// Security
app.use(helmet());
app.use(cors({
  origin: config.cors.origin,
  credentials: true,
}));

// Compression
app.use(compression());

// Body parsing
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(cookieParser());

// Logging
if (config.app.env === 'development') {
  app.use(morgan('dev'));
} else {
  app.use(morgan('combined', { stream: logger.stream }));
}

// ============================================
// ROUTES
// ============================================

// Health check
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
  });
});

// API routes
app.use('/api/auth', authRoutes);
app.use('/api/users', userRoutes);
app.use('/api/appointments', appointmentRoutes);
app.use('/api/messages', messageRoutes);
app.use('/api/articles', articleRoutes);
app.use('/api/recipes', recipeRoutes);
app.use('/api/payments', paymentRoutes);

// Static files
app.use('/uploads', express.static('assets/uploads'));

// ============================================
// ERROR HANDLING
// ============================================

app.use(notFoundHandler);
app.use(errorHandler);

// ============================================
// START SERVER
// ============================================

const PORT = config.app.port;
const httpServer = http.createServer(app);

// Initialize WebRTC Signaling Server
const signalingServer = new SignalingServer(httpServer);

httpServer.listen(PORT, () => {
  logger.info(`🚀 Server running on port ${PORT} in ${config.app.env} mode`);
  logger.info(`📍 API available at ${config.app.url}`);
  logger.info(`🎥 WebRTC signaling available at ws://${config.app.url}/socket.io`);
});

export default app;
