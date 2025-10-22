-- Danışan-Diyetisyen Atama Tablosu
-- Admin tarafından danışanlara diyetisyen atanması için

CREATE TABLE IF NOT EXISTS client_dietitian_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    dietitian_id INT NOT NULL,
    assigned_by INT NOT NULL, -- Admin user ID
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,

    -- Her client için bir aktif diyetisyen olabilir
    UNIQUE KEY unique_active_assignment (client_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index'ler
CREATE INDEX idx_client_id ON client_dietitian_assignments(client_id);
CREATE INDEX idx_dietitian_id ON client_dietitian_assignments(dietitian_id);
CREATE INDEX idx_is_active ON client_dietitian_assignments(is_active);
