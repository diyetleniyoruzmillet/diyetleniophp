-- Migration: Rate Limiting Tablosu
-- Brute force, spam ve DDoS saldırılarına karşı koruma için

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(64) NOT NULL COMMENT 'Hash key (action + identifier)',
    attempts INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Deneme sayısı',
    expires_at DATETIME NOT NULL COMMENT 'Expiry zamanı',
    last_attempt_at DATETIME NOT NULL COMMENT 'Son deneme zamanı',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_rate_key (rate_key),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Rate limiting için deneme takibi';
