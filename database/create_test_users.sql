-- Test Kullanıcıları ve Şifre Sıfırlama
-- Admin şifre: admin123 (hash: $2y$10$...)

-- 1. Admin şifresini sıfırla (admin123)
UPDATE users
SET password = '$2y$10$UZgWbK7rK5DB9AeWikTIGeT3AtAr2uPQrOJt6ed.6tl3nzDLEIT.m'
WHERE email = 'admin@diyetlenio.com';

-- 2. Test Admin oluştur (eğer yoksa)
INSERT INTO users (full_name, email, password, user_type, is_active, created_at)
SELECT 'Test Admin', 'testadmin@diyetlenio.com', '$2y$10$UZgWbK7rK5DB9AeWikTIGeT3AtAr2uPQrOJt6ed.6tl3nzDLEIT.m', 'admin', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'testadmin@diyetlenio.com');

-- 3. Test Diyetisyen oluştur (eğer yoksa)
INSERT INTO users (full_name, email, password, user_type, phone, is_active, created_at)
SELECT 'Dr. Test Diyetisyen', 'testdiyetisyen@diyetlenio.com', '$2y$10$3ysQwUjZW1du4h7zGMe1BegY/Htad01pH7jixZ6pbq0uWuEwAadh.', 'dietitian', '5551234567', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'testdiyetisyen@diyetlenio.com');

-- Diyetisyen profilini oluştur
INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, is_approved, consultation_fee, experience_years, rating_avg, total_clients, created_at)
SELECT u.id, 'Uzman Diyetisyen', 'Genel Beslenme & Kilo Yönetimi', 'Test amaçlı oluşturulmuş diyetisyen profili', 1, 500, 5, 4.5, 0, NOW()
FROM users u
WHERE u.email = 'testdiyetisyen@diyetlenio.com'
AND NOT EXISTS (SELECT 1 FROM dietitian_profiles WHERE user_id = u.id);

-- 4. Test Danışan oluştur (eğer yoksa)
INSERT INTO users (full_name, email, password, user_type, phone, is_active, created_at)
SELECT 'Test Danışan', 'testdanisman@diyetlenio.com', '$2y$10$9Nxrai/SyhAHm1HQhphU0u25swrAqZMl7tWHhysi2jRbYN4p4sC6G', 'client', '5559876543', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'testdanisman@diyetlenio.com');

-- Danışan profilini oluştur
INSERT INTO client_profiles (user_id, gender, height, target_weight, activity_level, created_at)
SELECT u.id, 'male', 170, 70, 'moderate', NOW()
FROM users u
WHERE u.email = 'testdanisman@diyetlenio.com'
AND NOT EXISTS (SELECT 1 FROM client_profiles WHERE user_id = u.id);

-- Sonuçları göster
SELECT 'Test kullanıcıları oluşturuldu!' as message;
SELECT id, full_name, email, user_type FROM users
WHERE email IN ('admin@diyetlenio.com', 'testadmin@diyetlenio.com', 'testdiyetisyen@diyetlenio.com', 'testdanisman@diyetlenio.com');
