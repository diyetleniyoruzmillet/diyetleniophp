-- Admin şifresini düzelt
-- Kullanım: mysql -u root -p diyetlenio < scripts/create-admin.sql
-- VEYA: sudo mysql diyetlenio < scripts/create-admin.sql

-- Admin kullanıcısının şifresini güncelle
-- Yeni Şifre: Admin123!
UPDATE users
SET password = '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    is_active = 1,
    is_email_verified = 1
WHERE email = 'admin@diyetlenio.com';

-- Eğer admin kullanıcısı yoksa, oluştur
INSERT IGNORE INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);

-- Sonucu göster
SELECT 'Admin bilgileri:' as '';
SELECT id, email, full_name, user_type, is_active, is_email_verified FROM users WHERE email = 'admin@diyetlenio.com';
SELECT '' as '';
SELECT 'Giriş Bilgileri:' as '';
SELECT 'Email: admin@diyetlenio.com' as '';
SELECT 'Şifre: Admin123!' as '';
