-- Create weight_tracking table
-- Run this in production: mysql -u root -p diyetlenio_db < 01-create-weight-tracking.sql

USE diyetlenio_db;

CREATE TABLE IF NOT EXISTS weight_tracking (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED,
    weight DECIMAL(5,2) NOT NULL COMMENT 'Kilogram',
    measurement_date DATE NOT NULL,
    notes TEXT,
    entered_by ENUM('client', 'dietitian') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_date (measurement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table was created
SELECT 'weight_tracking table created successfully!' as status;
SHOW TABLES LIKE 'weight_tracking';
