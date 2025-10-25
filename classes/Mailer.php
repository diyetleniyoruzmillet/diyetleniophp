<?php
/**
 * Diyetlenio - Mailer Sınıfı
 * E-posta gönderme işlemlerini yönetir
 */

class Mailer
{
    private string $from;
    private string $fromName;
    private array $config;

    public function __construct()
    {
        // .env anahtarları ile hizalanır
        $this->from = $_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['MAIL_FROM'] ?? 'noreply@diyetlenio.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Diyetlenio';
        
        $this->config = [
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        ];
    }

    /**
     * E-posta gönderir (basit PHP mail() fonksiyonu ile)
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        try {
            $headers = [
                'From' => "{$this->fromName} <{$this->from}>",
                'Reply-To' => $this->from,
                'X-Mailer' => 'PHP/' . phpversion(),
                'MIME-Version' => '1.0',
            ];

            if ($isHtml) {
                $headers['Content-Type'] = 'text/html; charset=UTF-8';
            } else {
                $headers['Content-Type'] = 'text/plain; charset=UTF-8';
            }

            $headerString = '';
            foreach ($headers as $key => $value) {
                $headerString .= "{$key}: {$value}\r\n";
            }

            return mail($to, $subject, $body, $headerString);
        } catch (Exception $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Hoş geldin e-postası gönderir
     */
    public function sendWelcome(string $to, string $name, string $verificationToken = null): bool
    {
        $subject = 'Diyetlenio\'ya Hoş Geldiniz!';
        
        $verificationLink = $verificationToken 
            ? BASE_URL . '/verify-email.php?token=' . $verificationToken 
            : null;

        $body = $this->getTemplate('welcome', [
            'name' => $name,
            'verification_link' => $verificationLink
        ]);

        return $this->send($to, $subject, $body);
    }

    /**
     * Şifre sıfırlama e-postası gönderir
     */
    public function sendPasswordReset(string $to, string $name, string $token): bool
    {
        $subject = 'Şifre Sıfırlama Talebi';
        $resetLink = BASE_URL . '/reset-password.php?token=' . $token;

        $body = $this->getTemplate('password-reset', [
            'name' => $name,
            'reset_link' => $resetLink
        ]);

        return $this->send($to, $subject, $body);
    }

    /**
     * Randevu onay e-postası gönderir
     */
    public function sendAppointmentConfirmation(string $to, array $appointmentData): bool
    {
        $subject = 'Randevu Onayı - Diyetlenio';
        $body = $this->getTemplate('appointment-confirmation', $appointmentData);
        return $this->send($to, $subject, $body);
    }

    /**
     * Randevu hatırlatma e-postası
     */
    public function sendAppointmentReminder(string $to, array $appointmentData): bool
    {
        $subject = 'Randevu Hatırlatma - Diyetlenio';
        $body = $this->getTemplate('appointment-reminder', $appointmentData);
        return $this->send($to, $subject, $body);
    }

    /**
     * Ödeme onay e-postası
     */
    public function sendPaymentApproved(string $to, array $paymentData): bool
    {
        $subject = 'Ödeme Onaylandı - Diyetlenio';
        $body = $this->getTemplate('payment-approved', $paymentData);
        return $this->send($to, $subject, $body);
    }

    /**
     * Randevu iptal e-postası
     */
    public function sendAppointmentCancellation(string $to, array $appointmentData): bool
    {
        $subject = 'Randevu İptali - Diyetlenio';
        $body = $this->getTemplate('appointment-cancellation', $appointmentData);
        return $this->send($to, $subject, $body);
    }

    /**
     * Toplu email gönderir
     */
    public function sendBulk(array $recipients, string $subject, string $body, bool $isHtml = true): array
    {
        $results = [];
        foreach ($recipients as $email) {
            $results[$email] = $this->send($email, $subject, $body, $isHtml);
        }
        return $results;
    }

    /**
     * E-posta template'ini yükler ve değişkenleri değiştirir
     */
    private function getTemplate(string $template, array $vars = []): string
    {
        $templatePath = ROOT_DIR . "/templates/emails/{$template}.html";
        
        if (!file_exists($templatePath)) {
            // Template yoksa basit HTML döndür
            return $this->getDefaultTemplate($template, $vars);
        }

        $content = file_get_contents($templatePath);
        
        // Değişkenleri değiştir
        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Varsayılan template döndürür
     */
    private function getDefaultTemplate(string $type, array $vars): string
    {
        $baseStyle = '
        <style>
            body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 30px; text-align: center; color: white; }
            .content { padding: 30px; }
            .button { display: inline-block; background: #11998e; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
        ';

        switch ($type) {
            case 'welcome':
                $verificationHtml = $vars['verification_link'] 
                    ? '<p><a href="' . $vars['verification_link'] . '" class="button">E-posta Adresimi Doğrula</a></p>' 
                    : '';
                
                return "
                <!DOCTYPE html>
                <html>
                <head><meta charset='UTF-8'>{$baseStyle}</head>
                <body>
                    <div class='container'>
                        <div class='header'><h1>Hoş Geldiniz!</h1></div>
                        <div class='content'>
                            <p>Merhaba {$vars['name']},</p>
                            <p>Diyetlenio ailesine katıldığınız için teşekkür ederiz!</p>
                            {$verificationHtml}
                            <p>Herhangi bir sorunuz varsa bize ulaşabilirsiniz.</p>
                        </div>
                        <div class='footer'>© 2025 Diyetlenio. Tüm hakları saklıdır.</div>
                    </div>
                </body>
                </html>
                ";

            case 'password-reset':
                return "
                <!DOCTYPE html>
                <html>
                <head><meta charset='UTF-8'>{$baseStyle}</head>
                <body>
                    <div class='container'>
                        <div class='header'><h1>Şifre Sıfırlama</h1></div>
                        <div class='content'>
                            <p>Merhaba {$vars['name']},</p>
                            <p>Şifrenizi sıfırlamak için aşağıdaki butona tıklayın:</p>
                            <p><a href='{$vars['reset_link']}' class='button'>Şifremi Sıfırla</a></p>
                            <p>Bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
                        </div>
                        <div class='footer'>© 2025 Diyetlenio. Tüm hakları saklıdır.</div>
                    </div>
                </body>
                </html>
                ";

            case 'appointment-confirmation':
                $appointmentDate = $vars['appointment_date'] ?? '';
                $appointmentTime = $vars['start_time'] ?? '';
                $dietitianName = $vars['dietitian_name'] ?? 'Diyetisyeniniz';
                $clientName = $vars['client_name'] ?? 'Değerli Danışan';
                $appointmentUrl = $vars['appointment_url'] ?? BASE_URL . '/client/appointments.php';

                return "
                <!DOCTYPE html>
                <html>
                <head><meta charset='UTF-8'>{$baseStyle}</head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>✅ Randevunuz Oluşturuldu!</h1>
                        </div>
                        <div class='content'>
                            <p>Merhaba {$clientName},</p>
                            <p>Randevunuz başarıyla oluşturuldu!</p>
                            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 5px 0;'><strong>Diyetisyen:</strong> {$dietitianName}</p>
                                <p style='margin: 5px 0;'><strong>Tarih:</strong> {$appointmentDate}</p>
                                <p style='margin: 5px 0;'><strong>Saat:</strong> {$appointmentTime}</p>
                                <p style='margin: 5px 0;'><strong>Görüşme Tipi:</strong> Online (Video Görüşme)</p>
                            </div>
                            <p>Randevu saatinden 30 dakika önce görüşme linkine erişebilirsiniz.</p>
                            <p><a href='{$appointmentUrl}' class='button'>Randevularımı Görüntüle</a></p>
                            <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                                <strong>Önemli:</strong> Randevunuzdan önce size hatırlatma e-postaları göndereceğiz.
                            </p>
                        </div>
                        <div class='footer'>© 2025 Diyetlenio. Tüm hakları saklıdır.</div>
                    </div>
                </body>
                </html>
                ";

            case 'appointment-reminder':
                $appointmentDate = $vars['appointment_date'] ?? '';
                $appointmentTime = $vars['start_time'] ?? '';
                $dietitianName = $vars['dietitian_name'] ?? 'Diyetisyeniniz';
                $clientName = $vars['client_name'] ?? 'Değerli Danışan';
                $hoursUntil = $vars['hours_until'] ?? '';
                $videoUrl = $vars['video_url'] ?? BASE_URL . '/client/appointments.php';

                return "
                <!DOCTYPE html>
                <html>
                <head><meta charset='UTF-8'>{$baseStyle}</head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>⏰ Randevu Hatırlatma</h1>
                        </div>
                        <div class='content'>
                            <p>Merhaba {$clientName},</p>
                            <p><strong>{$hoursUntil}</strong> sonra randevunuz var!</p>
                            <div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                                <p style='margin: 5px 0;'><strong>Diyetisyen:</strong> {$dietitianName}</p>
                                <p style='margin: 5px 0;'><strong>Tarih:</strong> {$appointmentDate}</p>
                                <p style='margin: 5px 0;'><strong>Saat:</strong> {$appointmentTime}</p>
                            </div>
                            <p>Lütfen görüşme saatinden birkaç dakika önce hazır olun.</p>
                            <p><a href='{$videoUrl}' class='button'>Görüşmeye Katıl</a></p>
                        </div>
                        <div class='footer'>© 2025 Diyetlenio. Tüm hakları saklıdır.</div>
                    </div>
                </body>
                </html>
                ";

            case 'appointment-cancellation':
                $appointmentDate = $vars['appointment_date'] ?? '';
                $appointmentTime = $vars['start_time'] ?? '';
                $dietitianName = $vars['dietitian_name'] ?? 'Diyetisyeniniz';
                $clientName = $vars['client_name'] ?? 'Değerli Danışan';
                $reason = $vars['reason'] ?? 'Belirtilmedi';

                return "
                <!DOCTYPE html>
                <html>
                <head><meta charset='UTF-8'>{$baseStyle}</head>
                <body>
                    <div class='container'>
                        <div class='header' style='background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);'>
                            <h1>❌ Randevu İptali</h1>
                        </div>
                        <div class='content'>
                            <p>Merhaba {$clientName},</p>
                            <p>Aşağıdaki randevunuz iptal edilmiştir:</p>
                            <div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef4444;'>
                                <p style='margin: 5px 0;'><strong>Diyetisyen:</strong> {$dietitianName}</p>
                                <p style='margin: 5px 0;'><strong>Tarih:</strong> {$appointmentDate}</p>
                                <p style='margin: 5px 0;'><strong>Saat:</strong> {$appointmentTime}</p>
                                <p style='margin: 5px 0;'><strong>İptal Nedeni:</strong> {$reason}</p>
                            </div>
                            <p>Yeni bir randevu almak için platformumuzu ziyaret edebilirsiniz.</p>
                        </div>
                        <div class='footer'>© 2025 Diyetlenio. Tüm hakları saklıdır.</div>
                    </div>
                </body>
                </html>
                ";

            default:
                return "<html><body><p>Email template not found.</p></body></html>";
        }
    }
}
