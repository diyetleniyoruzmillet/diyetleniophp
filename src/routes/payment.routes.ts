import { Router } from 'express';
import { authenticate, authorize } from '../middlewares/auth.middleware';

const router = Router();

router.use(authenticate);

// Payment routes will be implemented here
router.get('/', (req, res) => res.json({ message: 'Get payments' }));
router.post('/create', (req, res) => res.json({ message: 'Create payment' }));
router.post('/webhook', (req, res) => res.json({ message: 'Payment webhook' }));

// Admin only
router.get('/commissions', authorize('admin'), (req, res) => res.json({ message: 'Get commissions' }));

export default router;
