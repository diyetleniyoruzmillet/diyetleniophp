-- Migration 023: Emergency Consultation Requests
-- Acil diyetisyen talepleri için tablo

CREATE TABLE IF NOT EXISTS emergency_consultations (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NULL, -- NULL ise misafir kullanıcı
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    age INT NULL,
    gender ENUM('male', 'female', 'other') NULL,
    height DECIMAL(5,2) NULL COMMENT 'cm cinsinden',
    weight DECIMAL(5,2) NULL COMMENT 'kg cinsinden',
    health_conditions TEXT NULL COMMENT 'Sağlık durumu, hastalıklar',
    medications TEXT NULL COMMENT 'Kullandığı ilaçlar',
    urgency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium' COMMENT 'Aciliyet seviyesi',
    message TEXT NOT NULL COMMENT 'Acil danışma talebi mesajı',
    status ENUM('pending', 'in_progress', 'responded', 'closed') DEFAULT 'pending',
    admin_notes TEXT NULL COMMENT 'Admin notları',
    assigned_to INT UNSIGNED NULL COMMENT 'Atanan diyetisyen ID',
    response_message TEXT NULL COMMENT 'Admin/diyetisyen cevabı',
    responded_at DATETIME NULL,
    responded_by INT UNSIGNED NULL COMMENT 'Cevaplayan admin/diyetisyen ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_status (status),
    INDEX idx_urgency (urgency_level),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek veri (test için)
-- INSERT INTO emergency_consultations (
--     full_name, email, phone, age, weight, height,
--     urgency_level, message, status
-- ) VALUES (
--     'Test Kullanıcı',
--     'test@example.com',
--     '5321234567',
--     30,
--     75.5,
--     170.0,
--     'high',
--     'Acil kilo verme danışmanlığı gerekiyor. Sağlık sorunlarım var.',
--     'pending'
-- );
