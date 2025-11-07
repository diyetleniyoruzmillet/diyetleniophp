-- Migration 024: Dietitian Availability Table
-- Diyetisyenlerin müsaitlik takvimleri

CREATE TABLE IF NOT EXISTS dietitian_availability (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dietitian_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_dietitian (dietitian_id),
    INDEX idx_day (day_of_week),
    INDEX idx_active (is_active),

    UNIQUE KEY unique_dietitian_day (dietitian_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
