import { Router } from 'express';
import { authenticate } from '../middlewares/auth.middleware';

const router = Router();

router.use(authenticate);

// Appointment routes will be implemented here
router.get('/', (req, res) => res.json({ message: 'Get appointments' }));
router.post('/', (req, res) => res.json({ message: 'Create appointment' }));
router.get('/:id', (req, res) => res.json({ message: 'Get appointment by id' }));
router.put('/:id', (req, res) => res.json({ message: 'Update appointment' }));
router.delete('/:id', (req, res) => res.json({ message: 'Cancel appointment' }));

export default router;
