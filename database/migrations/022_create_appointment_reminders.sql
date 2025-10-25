-- Migration: Randevu Hatırlatıcıları
-- Email ve SMS hatırlatıcılarını yönetir

CREATE TABLE IF NOT EXISTS appointment_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    reminder_type ENUM('email', 'sms') NOT NULL,
    scheduled_for DATETIME NOT NULL COMMENT 'Hatırlatıcı gönderilecek zaman',
    sent_at DATETIME NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT NULL,
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    INDEX idx_scheduled (scheduled_for, status),
    INDEX idx_appointment (appointment_id),
    INDEX idx_status (status),
    INDEX idx_pending_scheduled (status, scheduled_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
