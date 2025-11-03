import { Router } from 'express';
import { authenticate, authorize } from '../middlewares/auth.middleware';

const router = Router();

// Public routes
router.get('/', (req, res) => res.json({ message: 'Get all recipes' }));
router.get('/:slug', (req, res) => res.json({ message: 'Get recipe by slug' }));

// Protected routes
router.post('/', authenticate, authorize('dietitian', 'admin'), (req, res) => res.json({ message: 'Create recipe' }));
router.put('/:id', authenticate, (req, res) => res.json({ message: 'Update recipe' }));
router.delete('/:id', authenticate, (req, res) => res.json({ message: 'Delete recipe' }));

export default router;
