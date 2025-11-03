import nodemailer, { Transporter } from 'nodemailer';
import { config } from '../config';
import { logger } from '../utils/logger';
import fs from 'fs';
import path from 'path';
import Handlebars from 'handlebars';

export class EmailService {
  private transporter: Transporter;

  constructor() {
    this.transporter = nodemailer.createTransport({
      host: config.mail.host,
      port: config.mail.port,
      secure: config.mail.encryption === 'ssl',
      auth: {
        user: config.mail.username,
        pass: config.mail.password,
      },
    });
  }

  async sendEmail(to: string, subject: string, html: string) {
    try {
      const info = await this.transporter.sendMail({
        from: `"${config.mail.from.name}" <${config.mail.from.address}>`,
        to,
        subject,
        html,
      });

      logger.info('Email sent:', { messageId: info.messageId, to });
      return info;
    } catch (error) {
      logger.error('Email sending failed:', error);
      throw error;
    }
  }

  async sendVerificationEmail(to: string, token: string, userName: string) {
    const verificationUrl = `${config.app.url}/verify-email/${token}`;
    const template = this.loadTemplate('email-verification');
    const html = template({ userName, verificationUrl });

    await this.sendEmail(to, 'Email Doğrulama - Diyetlenio', html);
  }

  async sendPasswordResetEmail(to: string, token: string, userName: string) {
    const resetUrl = `${config.app.url}/reset-password/${token}`;
    const template = this.loadTemplate('password-reset');
    const html = template({ userName, resetUrl });

    await this.sendEmail(to, 'Şifre Sıfırlama - Diyetlenio', html);
  }

  async sendAppointmentConfirmation(
    to: string,
    appointmentDetails: {
      dietitianName: string;
      date: string;
      time: string;
      duration: number;
    }
  ) {
    const template = this.loadTemplate('appointment-confirmation');
    const html = template(appointmentDetails);

    await this.sendEmail(to, 'Randevu Onayı - Diyetlenio', html);
  }

  async sendAppointmentReminder(
    to: string,
    appointmentDetails: {
      name: string;
      date: string;
      time: string;
    }
  ) {
    const template = this.loadTemplate('appointment-reminder');
    const html = template(appointmentDetails);

    await this.sendEmail(to, 'Randevu Hatırlatması - Diyetlenio', html);
  }

  private loadTemplate(templateName: string): HandlebarsTemplateDelegate {
    const templatePath = path.join(__dirname, '../templates/emails', `${templateName}.hbs`);

    // If template doesn't exist, return a simple HTML function
    if (!fs.existsSync(templatePath)) {
      logger.warn(`Template not found: ${templateName}, using default`);
      return (context: any) => `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>Diyetlenio</title>
        </head>
        <body style="font-family: Arial, sans-serif; padding: 20px;">
          <div style="max-width: 600px; margin: 0 auto;">
            <h1 style="color: #10b981;">Diyetlenio</h1>
            <div style="margin: 20px 0;">
              ${Object.entries(context).map(([key, value]) => `<p><strong>${key}:</strong> ${value}</p>`).join('')}
            </div>
            <hr style="margin: 20px 0;">
            <p style="color: #666; font-size: 12px;">Bu e-posta Diyetlenio tarafından gönderilmiştir.</p>
          </div>
        </body>
        </html>
      `;
    }

    const templateContent = fs.readFileSync(templatePath, 'utf-8');
    return Handlebars.compile(templateContent);
  }
}
