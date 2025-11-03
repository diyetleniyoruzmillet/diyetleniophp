import { Router } from 'express';
import { authenticate, authorize } from '../middlewares/auth.middleware';

const router = Router();

// Public routes
router.get('/', (req, res) => res.json({ message: 'Get all articles' }));
router.get('/:slug', (req, res) => res.json({ message: 'Get article by slug' }));

// Protected routes
router.post('/', authenticate, authorize('dietitian', 'admin'), (req, res) => res.json({ message: 'Create article' }));
router.put('/:id', authenticate, (req, res) => res.json({ message: 'Update article' }));
router.delete('/:id', authenticate, (req, res) => res.json({ message: 'Delete article' }));

export default router;
