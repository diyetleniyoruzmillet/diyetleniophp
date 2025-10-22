-- Create Payments Table with Receipt Upload
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Ödeme bilgileri
    client_id INT UNSIGNED NOT NULL COMMENT 'Ödemeyi yapan danışan',
    dietitian_id INT UNSIGNED NOT NULL COMMENT 'Diyetisyen',
    appointment_id INT UNSIGNED NULL COMMENT 'İlgili randevu',

    -- Tutar bilgileri
    amount DECIMAL(10,2) NOT NULL COMMENT 'Ödeme tutarı',
    commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Komisyon tutarı (%10)',

    -- Ödeme tipi
    payment_type ENUM('client_payment', 'commission_payment') DEFAULT 'client_payment' COMMENT 'Ödeme tipi',

    -- Dekont bilgileri
    receipt_path VARCHAR(500) NULL COMMENT 'Dekont dosya yolu',

    -- Durum
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT 'Ödeme durumu',

    -- Komisyon durumu
    commission_paid BOOLEAN DEFAULT FALSE COMMENT 'Diyetisyen komisyonu ödedi mi',
    commission_receipt_path VARCHAR(500) NULL COMMENT 'Komisyon dekontu',

    -- Notlar
    admin_note TEXT NULL COMMENT 'Admin notu',

    -- Tarihler
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Ödeme tarihi',
    approved_date DATETIME NULL COMMENT 'Onay tarihi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- İndeksler
    INDEX idx_client (client_id),
    INDEX idx_dietitian (dietitian_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),

    -- Foreign keys (opsiyonel - sadece tablo varsa)
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
