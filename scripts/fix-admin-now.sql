-- Admin kullanıcısını kontrol et ve düzelt
-- Kullanım: sudo mysql diyetlenio < scripts/fix-admin-now.sql

-- Önce mevcut admin kullanıcılarını göster
SELECT 'Mevcut admin kullanıcıları:' as '';
SELECT id, email, full_name, user_type, is_active, is_email_verified,
       LEFT(password, 30) as password_hash,
       created_at
FROM users
WHERE user_type = 'admin' OR email LIKE '%admin%';

-- Tüm admin kayıtlarını sil
DELETE FROM users WHERE email = 'admin@diyetlenio.com';

-- Yeni admin kullanıcısını ekle
-- Email: admin@diyetlenio.com
-- Şifre: Admin123!
INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);

-- Yeni durumu göster
SELECT '' as '';
SELECT 'Yeni admin kullanıcısı:' as '';
SELECT id, email, full_name, user_type, is_active, is_email_verified,
       LEFT(password, 30) as password_hash,
       created_at
FROM users
WHERE email = 'admin@diyetlenio.com';

SELECT '' as '';
SELECT '================================' as '';
SELECT 'GİRİŞ BİLGİLERİ:' as '';
SELECT '================================' as '';
SELECT 'URL: http://localhost:8000/login.php' as '';
SELECT 'Email: admin@diyetlenio.com' as '';
SELECT 'Şifre: Admin123!' as '';
SELECT '================================' as '';
