<?php
/**
 * Mail Helper Class
 * Simple email sending functionality
 */

class Mail
{
    private static $fromEmail = 'noreply@diyetlenio.com';
    private static $fromName = 'Diyetlenio';

    /**
     * Send an email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param array $options Additional options (fromEmail, fromName, isHtml)
     * @return bool
     */
    public static function send(string $to, string $subject, string $body, array $options = []): bool
    {
        $fromEmail = $options['fromEmail'] ?? self::$fromEmail;
        $fromName = $options['fromName'] ?? self::$fromName;
        $isHtml = $options['isHtml'] ?? true;

        // Email headers
        $headers = [];
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "MIME-Version: 1.0";

        if ($isHtml) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $body = self::wrapHtml($body, $subject);
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }

        // Log email for development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Email to {$to}: {$subject}");
            error_log("Body: " . strip_tags($body));
        }

        // Send email
        try {
            $result = mail($to, $subject, $body, implode("\r\n", $headers));

            // Log result
            if ($result) {
                error_log("Email sent successfully to {$to}");
            } else {
                error_log("Failed to send email to {$to}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset email
     *
     * @param string $email
     * @param string $token
     * @param string $firstName
     * @return bool
     */
    public static function sendPasswordReset(string $email, string $token, string $firstName): bool
    {
        $resetLink = url('/reset-password.php?token=' . $token);
        $subject = 'Şifre Sıfırlama - Diyetlenio';

        $body = "
            <h2>Merhaba {$firstName},</h2>
            <p>Şifre sıfırlama talebinizi aldık. Şifrenizi sıfırlamak için aşağıdaki linke tıklayın:</p>
            <p style='margin: 30px 0;'>
                <a href='{$resetLink}'
                   style='background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
                          color: white;
                          padding: 15px 30px;
                          text-decoration: none;
                          border-radius: 8px;
                          display: inline-block;'>
                    Şifremi Sıfırla
                </a>
            </p>
            <p><strong>Not:</strong> Bu link 1 saat geçerlidir.</p>
            <p>Eğer bu talebi siz yapmadıysanız, bu emaili görmezden gelebilirsiniz.</p>
        ";

        return self::send($email, $subject, $body);
    }

    /**
     * Send contact form notification to admin
     *
     * @param array $data Contact form data
     * @return bool
     */
    public static function sendContactNotification(array $data): bool
    {
        $adminEmail = 'admin@diyetlenio.com'; // TODO: Get from config
        $subject = 'Yeni İletişim Mesajı - ' . $data['subject'];

        $body = "
            <h2>Yeni İletişim Mesajı</h2>
            <p><strong>Gönderen:</strong> {$data['name']}</p>
            <p><strong>Email:</strong> {$data['email']}</p>
            <p><strong>Konu:</strong> {$data['subject']}</p>
            <p><strong>Mesaj:</strong></p>
            <p style='background: #f7fafc; padding: 20px; border-radius: 8px;'>
                " . nl2br(htmlspecialchars($data['message'])) . "
            </p>
        ";

        return self::send($adminEmail, $subject, $body);
    }

    /**
     * Send appointment confirmation email
     *
     * @param string $email
     * @param array $appointmentData
     * @return bool
     */
    public static function sendAppointmentConfirmation(string $email, array $appointmentData): bool
    {
        $subject = 'Randevu Onayı - Diyetlenio';

        $body = "
            <h2>Randevunuz Onaylandı!</h2>
            <p><strong>Diyetisyen:</strong> {$appointmentData['dietitian']}</p>
            <p><strong>Tarih:</strong> {$appointmentData['date']}</p>
            <p><strong>Saat:</strong> {$appointmentData['time']}</p>
            <p><strong>Tutar:</strong> {$appointmentData['amount']} ₺</p>
            <p style='margin: 30px 0;'>
                <a href='" . url('/client/appointments.php') . "'
                   style='background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
                          color: white;
                          padding: 15px 30px;
                          text-decoration: none;
                          border-radius: 8px;
                          display: inline-block;'>
                    Randevularımı Görüntüle
                </a>
            </p>
            <p>Randevunuza 15 dakika kala size hatırlatma emaili göndereceğiz.</p>
        ";

        return self::send($email, $subject, $body);
    }

    /**
     * Wrap email body in HTML template
     *
     * @param string $content
     * @param string $subject
     * @return string
     */
    private static function wrapHtml(string $content, string $subject): string
    {
        return "
<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$subject}</title>
</head>
<body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; background: #f8fafc;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background: #f8fafc; padding: 40px 20px;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);'>
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); padding: 30px; text-align: center;'>
                            <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 700;'>
                                <span style='font-size: 32px;'>❤️</span> Diyetlenio
                            </h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style='padding: 40px 30px; color: #2d3748; line-height: 1.6;'>
                            {$content}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style='background: #f7fafc; padding: 30px; text-align: center; color: #718096; font-size: 14px;'>
                            <p style='margin: 0 0 10px 0;'>© 2024 Diyetlenio. Tüm hakları saklıdır.</p>
                            <p style='margin: 0;'>
                                <a href='" . url('/privacy-policy.php') . "' style='color: #0ea5e9; text-decoration: none; margin: 0 10px;'>Gizlilik Politikası</a>
                                <a href='" . url('/terms.php') . "' style='color: #0ea5e9; text-decoration: none; margin: 0 10px;'>Kullanım Şartları</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }
}
