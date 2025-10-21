-- İkinci Admin Kullanıcısı Ekleme
-- Kullanım: sudo mysql diyetlenio < scripts/add-second-admin.sql
-- VEYA: mysql -u root -p diyetlenio < scripts/add-second-admin.sql

-- İkinci admin kullanıcısını ekle
-- Email: admin2@diyetlenio.com
-- Şifre: Admin123!
INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin2@diyetlenio.com',
    '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    'Admin Kullanıcı 2',
    '05009876543',
    'admin',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    password = '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    is_active = 1,
    is_email_verified = 1;

-- Tüm admin kullanıcılarını göster
SELECT 'Tüm Admin Kullanıcıları:' as '';
SELECT id, email, full_name, phone, user_type, is_active, is_email_verified, created_at
FROM users
WHERE user_type = 'admin'
ORDER BY id;

SELECT '' as '';
SELECT '================================' as '';
SELECT 'Giriş Bilgileri - Admin 1:' as '';
SELECT 'Email: admin@diyetlenio.com' as '';
SELECT 'Şifre: Admin123!' as '';
SELECT '' as '';
SELECT 'Giriş Bilgileri - Admin 2:' as '';
SELECT 'Email: admin2@diyetlenio.com' as '';
SELECT 'Şifre: Admin123!' as '';
SELECT '================================' as '';
