-- Migration: Diyetisyen Müsaitlik Sistemi
-- Diyetisyenlerin haftalık çalışma saatlerini yönetir

CREATE TABLE IF NOT EXISTS dietitian_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dietitian_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Pazar, 1=Pazartesi, 2=Salı, 3=Çarşamba, 4=Perşembe, 5=Cuma, 6=Cumartesi',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 45 COMMENT 'Dakika cinsinden (30, 45, 60)',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dietitian_day (dietitian_id, day_of_week),
    INDEX idx_active (is_active),
    INDEX idx_day_time (day_of_week, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek veri (İlk diyetisyen için default müsaitlik)
-- Pazartesi-Cuma 09:00-17:00 (öğle arası 12:00-13:00)
INSERT IGNORE INTO dietitian_availability (dietitian_id, day_of_week, start_time, end_time, slot_duration) VALUES
-- Pazartesi
(1, 1, '09:00:00', '12:00:00', 45),
(1, 1, '13:00:00', '17:00:00', 45),
-- Salı
(1, 2, '09:00:00', '12:00:00', 45),
(1, 2, '13:00:00', '17:00:00', 45),
-- Çarşamba
(1, 3, '09:00:00', '12:00:00', 45),
(1, 3, '13:00:00', '17:00:00', 45),
-- Perşembe
(1, 4, '09:00:00', '12:00:00', 45),
(1, 4, '13:00:00', '17:00:00', 45),
-- Cuma
(1, 5, '09:00:00', '12:00:00', 45),
(1, 5, '13:00:00', '17:00:00', 45);
