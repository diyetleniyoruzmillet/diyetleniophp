-- Migration: 004_create_messages_and_tracking.sql
-- Description: Create messages and client tracking tables
-- Created: 2025-10-26
-- Depends on: 001_create_users_table.sql, 003_create_appointments_system.sql

-- Mesajlar
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_appointment (appointment_id),
    INDEX idx_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diyet planları
CREATE TABLE IF NOT EXISTS diet_plans (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diyetisyen notları
CREATE TABLE IF NOT EXISTS client_notes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    is_private TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client_dietitian (client_id, dietitian_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sağlık bilgileri
CREATE TABLE IF NOT EXISTS client_health_info (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL UNIQUE,
    height DECIMAL(5,2) COMMENT 'cm',
    current_weight DECIMAL(5,2) COMMENT 'kg',
    target_weight DECIMAL(5,2) COMMENT 'kg',
    birth_date DATE,
    gender ENUM('male', 'female', 'other'),
    blood_type VARCHAR(5),
    allergies TEXT,
    medications TEXT,
    chronic_diseases TEXT,
    dietary_restrictions TEXT,
    activity_level ENUM('sedentary', 'light', 'moderate', 'active', 'very_active'),
    goal ENUM('lose_weight', 'maintain', 'gain_weight', 'muscle_gain', 'health'),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
