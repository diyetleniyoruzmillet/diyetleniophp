import { Request, Response, NextFunction } from 'express';
import { config } from '../config';

/**
 * Middleware to redirect non-www to www
 * Redirects diyetlenio.com -> www.diyetlenio.com
 */
export const domainRedirect = (req: Request, res: Response, next: NextFunction) => {
  // Skip in development
  if (config.app.env === 'development') {
    return next();
  }

  const host = req.get('host');
  const protocol = req.get('x-forwarded-proto') || req.protocol;

  // If host doesn't start with www., redirect to www version
  if (host && !host.startsWith('www.') && host.includes('diyetlenio.com')) {
    const newHost = `www.${host}`;
    const newUrl = `${protocol}://${newHost}${req.originalUrl}`;
    return res.redirect(301, newUrl);
  }

  // Force HTTPS in production
  if (protocol !== 'https') {
    const newUrl = `https://${host}${req.originalUrl}`;
    return res.redirect(301, newUrl);
  }

  next();
};
