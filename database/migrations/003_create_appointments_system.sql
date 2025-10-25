-- Migration: 003_create_appointments_system.sql
-- Description: Create appointments and availability tables for scheduling system
-- Created: 2025-10-26
-- Depends on: 001_create_users_table.sql

-- Diyetisyen müsaitlik takvimi
CREATE TABLE IF NOT EXISTS availability (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dietitian_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '0=Pazar, 1=Pazartesi, ..., 6=Cumartesi',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dietitian_day (dietitian_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Randevular
CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dietitian_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration INT UNSIGNED DEFAULT 45 COMMENT 'Dakika cinsinden',
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    is_first_session TINYINT(1) DEFAULT 0,
    is_paid TINYINT(1) DEFAULT 0,
    payment_amount DECIMAL(10,2),
    notes TEXT,
    cancellation_reason TEXT,
    cancelled_by INT UNSIGNED,
    cancelled_at DATETIME,
    reminder_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dietitian (dietitian_id),
    INDEX idx_client (client_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acil nöbetçi talepleri
CREATE TABLE IF NOT EXISTS emergency_calls (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED,
    status ENUM('pending', 'ongoing', 'completed', 'cancelled') DEFAULT 'pending',
    room_id VARCHAR(255) UNIQUE,
    started_at DATETIME,
    ended_at DATETIME,
    duration INT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
