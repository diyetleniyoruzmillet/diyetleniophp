import { Request, Response } from 'express';
import { AuthService } from '../../services/auth.service';
import { ValidationError } from '../../utils/errors';
import { AuthRequest } from '../../middlewares/auth.middleware';

const authService = new AuthService();

export class AuthController {
  async register(req: Request, res: Response) {
    const { email, password, fullName, phone, userType } = req.body;

    // Basic validation
    if (!email || !password || !fullName || !userType) {
      throw new ValidationError('Missing required fields');
    }

    if (password.length < 8) {
      throw new ValidationError('Password must be at least 8 characters');
    }

    const result = await authService.register({
      email,
      password,
      fullName,
      phone,
      userType,
    });

    res.status(201).json({
      status: 'success',
      message: 'Registration successful',
      data: result,
    });
  }

  async login(req: Request, res: Response) {
    const { email, password } = req.body;

    if (!email || !password) {
      throw new ValidationError('Email and password are required');
    }

    const result = await authService.login({ email, password });

    res.json({
      status: 'success',
      message: 'Login successful',
      data: result,
    });
  }

  async verifyEmail(req: Request, res: Response) {
    const { token } = req.params;

    if (!token) {
      throw new ValidationError('Token is required');
    }

    const result = await authService.verifyEmail(token);

    res.json({
      status: 'success',
      data: result,
    });
  }

  async forgotPassword(req: Request, res: Response) {
    const { email } = req.body;

    if (!email) {
      throw new ValidationError('Email is required');
    }

    const result = await authService.forgotPassword(email);

    res.json({
      status: 'success',
      data: result,
    });
  }

  async resetPassword(req: Request, res: Response) {
    const { token } = req.params;
    const { password } = req.body;

    if (!token || !password) {
      throw new ValidationError('Token and password are required');
    }

    if (password.length < 8) {
      throw new ValidationError('Password must be at least 8 characters');
    }

    const result = await authService.resetPassword(token, password);

    res.json({
      status: 'success',
      data: result,
    });
  }

  async changePassword(req: AuthRequest, res: Response) {
    const { currentPassword, newPassword } = req.body;
    const userId = req.user!.id;

    if (!currentPassword || !newPassword) {
      throw new ValidationError('Current and new password are required');
    }

    if (newPassword.length < 8) {
      throw new ValidationError('New password must be at least 8 characters');
    }

    const result = await authService.changePassword(userId, currentPassword, newPassword);

    res.json({
      status: 'success',
      data: result,
    });
  }

  async refreshToken(req: Request, res: Response) {
    const { refreshToken } = req.body;

    if (!refreshToken) {
      throw new ValidationError('Refresh token is required');
    }

    const result = await authService.refreshToken(refreshToken);

    res.json({
      status: 'success',
      data: result,
    });
  }

  async logout(req: AuthRequest, res: Response) {
    // In a stateless JWT system, logout is handled client-side
    // But you can implement token blacklisting here if needed
    res.json({
      status: 'success',
      message: 'Logged out successfully',
    });
  }

  async me(req: AuthRequest, res: Response) {
    const userId = req.user!.id;

    const user = await authService.getProfile(userId);

    res.json({
      status: 'success',
      data: { user },
    });
  }
}
