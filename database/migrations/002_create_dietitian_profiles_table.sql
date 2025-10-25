-- Migration: 002_create_dietitian_profiles_table.sql
-- Description: Create dietitian_profiles table for storing professional information
-- Created: 2025-10-26
-- Depends on: 001_create_users_table.sql

-- Diyetisyen profilleri
CREATE TABLE IF NOT EXISTS dietitian_profiles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    title VARCHAR(100),
    specialization TEXT,
    experience_years INT UNSIGNED,
    about_me TEXT,
    education TEXT,
    certificates TEXT,
    diploma_file VARCHAR(255),
    certificate_files TEXT,
    iban VARCHAR(34),
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 0,
    approval_date DATETIME,
    rejection_reason TEXT,
    rating_avg DECIMAL(3,2) DEFAULT 0,
    rating_count INT UNSIGNED DEFAULT 0,
    total_clients INT UNSIGNED DEFAULT 0,
    total_sessions INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_approved (is_approved),
    INDEX idx_rating (rating_avg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
