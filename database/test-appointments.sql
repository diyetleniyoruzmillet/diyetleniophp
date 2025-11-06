-- Test Randevuları ve Kullanıcıları
-- Bu dosyayı çalıştırarak test verisi oluşturabilirsiniz

-- Test Diyetisyen (eğer yoksa)
INSERT INTO users (full_name, email, password_hash, user_type, is_active, email_verified, created_at)
VALUES (
    'Test Diyetisyen',
    'diyetisyen@test.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'dietitian',
    1,
    1,
    NOW()
) ON DUPLICATE KEY UPDATE id=id;

SET @dietitian_id = LAST_INSERT_ID();
IF @dietitian_id = 0 THEN
    SET @dietitian_id = (SELECT id FROM users WHERE email = 'diyetisyen@test.com' LIMIT 1);
END IF;

-- Diyetisyen profili
INSERT INTO dietitian_profiles (
    user_id, title, specialization, consultation_fee,
    is_approved, rating_avg, total_clients, created_at
)
VALUES (
    @dietitian_id,
    'Diyetisyen',
    'Kilo Yönetimi',
    300.00,
    1,
    4.8,
    25,
    NOW()
) ON DUPLICATE KEY UPDATE user_id=user_id;

-- Test Danışan (eğer yoksa)
INSERT INTO users (full_name, email, password_hash, user_type, is_active, email_verified, created_at)
VALUES (
    'Test Danışan',
    'danisan@test.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'client',
    1,
    1,
    NOW()
) ON DUPLICATE KEY UPDATE id=id;

SET @client_id = LAST_INSERT_ID();
IF @client_id = 0 THEN
    SET @client_id = (SELECT id FROM users WHERE email = 'danisan@test.com' LIMIT 1);
END IF;

-- Test Randevuları Oluştur

-- Randevu 1: ŞU ANDA aktif (5 dakika önce başladı, 40 dakika daha devam edecek)
INSERT INTO appointments (
    dietitian_id,
    client_id,
    appointment_date,
    start_time,
    end_time,
    duration,
    status,
    is_paid,
    payment_amount,
    created_at
) VALUES (
    @dietitian_id,
    @client_id,
    CURDATE(),
    DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MINUTE), '%H:%i:00'),
    DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 40 MINUTE), '%H:%i:00'),
    45,
    'scheduled',
    1,
    300.00,
    NOW()
);

-- Randevu 2: 15 dakika sonra (katılım için hazır)
INSERT INTO appointments (
    dietitian_id,
    client_id,
    appointment_date,
    start_time,
    end_time,
    duration,
    status,
    is_paid,
    payment_amount,
    created_at
) VALUES (
    @dietitian_id,
    @client_id,
    CURDATE(),
    DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 15 MINUTE), '%H:%i:00'),
    DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 60 MINUTE), '%H:%i:00'),
    45,
    'scheduled',
    1,
    300.00,
    NOW()
);

-- Randevu 3: Yarın saat 10:00
INSERT INTO appointments (
    dietitian_id,
    client_id,
    appointment_date,
    start_time,
    end_time,
    duration,
    status,
    is_paid,
    payment_amount,
    created_at
) VALUES (
    @dietitian_id,
    @client_id,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY),
    '10:00:00',
    '10:45:00',
    45,
    'scheduled',
    1,
    300.00,
    NOW()
);

-- Randevu 4: Yarın saat 14:00
INSERT INTO appointments (
    dietitian_id,
    client_id,
    appointment_date,
    start_time,
    end_time,
    duration,
    status,
    is_paid,
    payment_amount,
    created_at
) VALUES (
    @dietitian_id,
    @client_id,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY),
    '14:00:00',
    '14:45:00',
    45,
    'scheduled',
    1,
    300.00,
    NOW()
);

-- Randevu 5: Geçmiş randevu (tamamlanmış)
INSERT INTO appointments (
    dietitian_id,
    client_id,
    appointment_date,
    start_time,
    end_time,
    duration,
    status,
    is_paid,
    payment_amount,
    created_at
) VALUES (
    @dietitian_id,
    @client_id,
    DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    '10:00:00',
    '10:45:00',
    45,
    'completed',
    1,
    300.00,
    DATE_SUB(NOW(), INTERVAL 2 DAY)
);

-- Sonuçları göster
SELECT
    a.id,
    a.appointment_date,
    a.start_time,
    a.status,
    u1.full_name as client,
    u2.full_name as dietitian,
    CASE
        WHEN CONCAT(a.appointment_date, ' ', a.start_time) > NOW() THEN 'Gelecek'
        WHEN CONCAT(a.appointment_date, ' ', a.start_time) < DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'Geçmiş'
        ELSE 'AKTİF - Şimdi Katılabilir!'
    END as durumu
FROM appointments a
LEFT JOIN users u1 ON a.client_id = u1.id
LEFT JOIN users u2 ON a.dietitian_id = u2.id
ORDER BY a.appointment_date DESC, a.start_time DESC
LIMIT 10;

-- Kullanıcı bilgilerini göster
SELECT
    'GİRİŞ BİLGİLERİ' as bilgi,
    'TEST HESAPLARI' as aciklama;

SELECT
    user_type as 'Hesap Tipi',
    email as 'Email',
    'password' as 'Şifre',
    full_name as 'Ad Soyad'
FROM users
WHERE email IN ('diyetisyen@test.com', 'danisan@test.com')
ORDER BY user_type;
