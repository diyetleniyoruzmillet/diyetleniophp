-- Admin kullanıcısı ekleme SQL
-- Kullanım: mysql -u root -p diyetlenio < scripts/create-admin.sql

-- Önce mevcut admin kullanıcısını kontrol et ve sil (varsa)
DELETE FROM users WHERE email = 'admin@diyetlenio.com';

-- Yeni admin kullanıcısını ekle
-- Şifre: Admin123!
INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);

-- Sonucu göster
SELECT id, email, full_name, user_type, is_active FROM users WHERE user_type = 'admin';
