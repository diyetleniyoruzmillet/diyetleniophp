-- Admin şifresini güncelle
-- Yeni şifre: Admin123!
-- Hash: $2y$10$FwmI0XC.c48tiKZIkLlwm.nrWoaeKBanVU61KPe3YPjzVg7rl8EZS

-- Önce admin kullanıcıları kontrol et
SELECT id, email, full_name, user_type, is_active, created_at
FROM users
WHERE user_type = 'admin';

-- Eğer admin yoksa, yeni admin oluştur
INSERT INTO users (email, password, full_name, user_type, is_active, created_at, updated_at)
SELECT 'admin@diyetlenio.com',
       '$2y$10$FwmI0XC.c48tiKZIkLlwm.nrWoaeKBanVU61KPe3YPjzVg7rl8EZS',
       'Admin User',
       'admin',
       1,
       NOW(),
       NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE user_type = 'admin');

-- Tüm adminlerin şifresini güncelle
UPDATE users
SET password = '$2y$10$FwmI0XC.c48tiKZIkLlwm.nrWoaeKBanVU61KPe3YPjzVg7rl8EZS',
    updated_at = NOW()
WHERE user_type = 'admin';

-- Güncellenmiş admin kullanıcıları göster
SELECT id, email, full_name, user_type, is_active, updated_at
FROM users
WHERE user_type = 'admin';
