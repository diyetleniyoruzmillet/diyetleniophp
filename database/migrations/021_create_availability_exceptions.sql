-- Migration: Diyetisyen Müsaitlik İstisnaları
-- İzin günleri, tatiller, özel çalışma günleri

CREATE TABLE IF NOT EXISTS dietitian_availability_exceptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dietitian_id INT NOT NULL,
    exception_date DATE NOT NULL,
    is_available BOOLEAN DEFAULT FALSE COMMENT 'FALSE=İzinli/Tatil, TRUE=Özel çalışma günü',
    start_time TIME NULL,
    end_time TIME NULL,
    reason VARCHAR(255) NULL COMMENT 'İzin nedeni veya açıklama',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dietitian_date (dietitian_id, exception_date),
    INDEX idx_date (exception_date),
    INDEX idx_dietitian_date (dietitian_id, exception_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek: Resmi tatiller 2025
INSERT IGNORE INTO dietitian_availability_exceptions (dietitian_id, exception_date, is_available, reason) VALUES
(1, '2025-01-01', FALSE, 'Yılbaşı'),
(1, '2025-04-23', FALSE, '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı'),
(1, '2025-05-01', FALSE, 'İşçi Bayramı'),
(1, '2025-05-19', FALSE, 'Atatürk''ü Anma, Gençlik ve Spor Bayramı'),
(1, '2025-08-30', FALSE, 'Zafer Bayramı'),
(1, '2025-10-29', FALSE, 'Cumhuriyet Bayramı');
