import { Router } from 'express';
import { authenticate, authorize } from '../middlewares/auth.middleware';

const router = Router();

// All routes require authentication
router.use(authenticate);

// User routes will be implemented here
router.get('/profile', (req, res) => res.json({ message: 'Get profile' }));
router.put('/profile', (req, res) => res.json({ message: 'Update profile' }));

// Admin only
router.get('/all', authorize('admin'), (req, res) => res.json({ message: 'Get all users' }));

export default router;
