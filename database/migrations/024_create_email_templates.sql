-- Migration: Create email templates table
-- Description: Database-based email template system
-- Created: 2025-10-26

CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique identifier for the template (e.g., password_reset)',
    template_name VARCHAR(255) NOT NULL COMMENT 'Human-readable name',
    subject VARCHAR(500) NOT NULL COMMENT 'Email subject line',
    body_html TEXT NOT NULL COMMENT 'HTML email body',
    body_text TEXT NULL COMMENT 'Plain text email body (optional)',
    description TEXT NULL COMMENT 'Template description and usage',
    variables JSON NULL COMMENT 'Available variables for this template',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether template is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_key (template_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Email template management system';

-- Insert default templates from current system
INSERT INTO email_templates (template_key, template_name, subject, body_html, description, variables) VALUES
(
    'password_reset',
    'Şifre Sıfırlama',
    'Şifre Sıfırlama - Diyetlenio',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
        <div style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">Diyetlenio</h1>
            <p style="margin: 10px 0 0; font-size: 16px;">Şifre Sıfırlama Talebi</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <p style="font-size: 16px; color: #333; margin-bottom: 20px;">Merhaba {FIRST_NAME},</p>
            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 20px;">
                Hesabınız için şifre sıfırlama talebi aldık. Şifrenizi sıfırlamak için aşağıdaki butona tıklayın:
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{RESET_LINK}" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">
                    Şifremi Sıfırla
                </a>
            </div>
            <p style="font-size: 13px; color: #999; line-height: 1.6; margin-top: 20px;">
                Bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz. Link 24 saat geçerlidir.
            </p>
            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
            <p style="font-size: 12px; color: #999; text-align: center; margin: 0;">
                © 2025 Diyetlenio. Tüm hakları saklıdır.
            </p>
        </div>
    </div>',
    'Kullanıcı şifre sıfırlama talep ettiğinde gönderilir',
    '["FIRST_NAME", "RESET_LINK"]'
),
(
    'contact_notification',
    'İletişim Formu Bildirimi',
    'Yeni İletişim Formu Mesajı',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
        <div style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">Yeni İletişim Mesajı</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2 style="color: #56ab2f; margin-top: 0;">Gönderen Bilgileri</h2>
            <table style="width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">İsim:</td>
                    <td style="padding: 8px 0; color: #333;">{NAME}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">E-posta:</td>
                    <td style="padding: 8px 0; color: #333;">{EMAIL}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Telefon:</td>
                    <td style="padding: 8px 0; color: #333;">{PHONE}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Konu:</td>
                    <td style="padding: 8px 0; color: #333;">{SUBJECT}</td>
                </tr>
            </table>
            <h2 style="color: #56ab2f;">Mesaj İçeriği</h2>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; color: #333; line-height: 1.6;">
                {MESSAGE}
            </div>
        </div>
    </div>',
    'İletişim formundan mesaj geldiğinde admin''e gönderilir',
    '["NAME", "EMAIL", "PHONE", "SUBJECT", "MESSAGE"]'
),
(
    'appointment_confirmation',
    'Randevu Onayı',
    'Randevunuz Onaylandı - Diyetlenio',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
        <div style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">Randevu Onayı</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <p style="font-size: 16px; color: #333; margin-bottom: 20px;">Merhaba {CLIENT_NAME},</p>
            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 20px;">
                Randevunuz başarıyla oluşturuldu! Detaylar aşağıdaki gibidir:
            </p>
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; margin: 20px 0;">
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 10px 0; color: #666; font-weight: bold;">Diyetisyen:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: bold;">{DIETITIAN_NAME}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #666; font-weight: bold;">Tarih:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: bold;">{DATE}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #666; font-weight: bold;">Saat:</td>
                        <td style="padding: 10px 0; color: #333; font-weight: bold;">{TIME}</td>
                    </tr>
                </table>
            </div>
            <p style="font-size: 13px; color: #999; line-height: 1.6; margin-top: 20px;">
                Randevunuzdan 24 saat önce size hatırlatma e-postası göndereceğiz.
            </p>
        </div>
    </div>',
    'Randevu oluşturulduğunda danışana gönderilir',
    '["CLIENT_NAME", "DIETITIAN_NAME", "DATE", "TIME"]'
),
(
    'dietitian_verification',
    'Diyetisyen Email Doğrulama',
    'Email Doğrulama - Diyetlenio',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
        <div style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">Email Doğrulama</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <p style="font-size: 16px; color: #333; margin-bottom: 20px;">Merhaba {FIRST_NAME},</p>
            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 20px;">
                Diyetlenio''ya hoş geldiniz! E-posta adresinizi doğrulamak için aşağıdaki butona tıklayın:
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{VERIFICATION_LINK}" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">
                    E-postamı Doğrula
                </a>
            </div>
            <p style="font-size: 13px; color: #999; line-height: 1.6; margin-top: 20px;">
                Bu link 24 saat geçerlidir. Hesabınızı oluşturmadıysanız, bu e-postayı görmezden gelebilirsiniz.
            </p>
        </div>
    </div>',
    'Diyetisyen kayıt olduğunda email doğrulama için gönderilir',
    '["FIRST_NAME", "VERIFICATION_LINK"]'
),
(
    'dietitian_approval',
    'Diyetisyen Onay Bildirimi',
    'Başvurunuz Onaylandı - Diyetlenio',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
        <div style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">Tebrikler!</h1>
            <p style="margin: 10px 0 0; font-size: 16px;">Başvurunuz Onaylandı</p>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <p style="font-size: 16px; color: #333; margin-bottom: 20px;">Merhaba {FIRST_NAME},</p>
            <p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 20px;">
                Diyetlenio platformuna başvurunuz incelendi ve onaylandı! Artık platformumuza giriş yapabilir ve danışanlarınıza hizmet vermeye başlayabilirsiniz.
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{LOGIN_URL}" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">
                    Giriş Yap
                </a>
            </div>
            <p style="font-size: 13px; color: #999; line-height: 1.6; margin-top: 20px;">
                Herhangi bir sorunuz olursa bizimle iletişime geçmekten çekinmeyin.
            </p>
        </div>
    </div>',
    'Diyetisyen başvurusu onaylandığında gönderilir',
    '["FIRST_NAME", "LOGIN_URL"]'
),
(
    'admin_new_dietitian',
    'Admin - Yeni Diyetisyen Başvurusu',
    'Yeni Diyetisyen Başvurusu',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
        <div style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center;">
            <h1 style="margin: 0; font-size: 28px;">Yeni Diyetisyen Başvurusu</h1>
        </div>
        <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">Yeni bir diyetisyen başvurusu yapıldı. Detaylar:</p>
            <table style="width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Ad Soyad:</td>
                    <td style="padding: 8px 0; color: #333;">{FULL_NAME}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">E-posta:</td>
                    <td style="padding: 8px 0; color: #333;">{EMAIL}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Telefon:</td>
                    <td style="padding: 8px 0; color: #333;">{PHONE}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Unvan:</td>
                    <td style="padding: 8px 0; color: #333;">{TITLE}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Deneyim:</td>
                    <td style="padding: 8px 0; color: #333;">{EXPERIENCE}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666; font-weight: bold;">Uzmanlık:</td>
                    <td style="padding: 8px 0; color: #333;">{SPECIALIZATION}</td>
                </tr>
            </table>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{ADMIN_PANEL_URL}" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">
                    Başvuruyu İncele
                </a>
            </div>
        </div>
    </div>',
    'Yeni diyetisyen başvurusu geldiğinde admin''e gönderilir',
    '["FULL_NAME", "EMAIL", "PHONE", "TITLE", "EXPERIENCE", "SPECIALIZATION", "ADMIN_PANEL_URL"]'
);
