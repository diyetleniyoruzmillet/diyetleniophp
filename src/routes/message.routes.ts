import { Router } from 'express';
import { authenticate } from '../middlewares/auth.middleware';

const router = Router();

router.use(authenticate);

// Message routes will be implemented here
router.get('/', (req, res) => res.json({ message: 'Get messages' }));
router.post('/', (req, res) => res.json({ message: 'Send message' }));
router.get('/:id', (req, res) => res.json({ message: 'Get message by id' }));
router.put('/:id/read', (req, res) => res.json({ message: 'Mark as read' }));

export default router;
