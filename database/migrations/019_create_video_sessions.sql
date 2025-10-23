-- Video Sessions Table for WebRTC Video Calls
-- Created: 2025-10-23

CREATE TABLE IF NOT EXISTS video_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(100) UNIQUE NOT NULL,
    appointment_id INT NOT NULL,
    client_id INT NOT NULL,
    dietitian_id INT NOT NULL,
    session_status ENUM('waiting', 'active', 'ended', 'failed') DEFAULT 'waiting',
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    duration_minutes INT DEFAULT 0,
    client_joined_at TIMESTAMP NULL,
    dietitian_joined_at TIMESTAMP NULL,
    connection_quality ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_room_id (room_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_session_status (session_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video Session Events (ICE candidates, offers, answers)
CREATE TABLE IF NOT EXISTS video_session_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    event_type ENUM('offer', 'answer', 'ice_candidate', 'join', 'leave', 'error') NOT NULL,
    user_id INT NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (session_id) REFERENCES video_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_session_id (session_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add video_room_url column to appointments table if not exists
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS video_room_url VARCHAR(255) DEFAULT NULL AFTER notes,
ADD COLUMN IF NOT EXISTS video_session_id INT DEFAULT NULL AFTER video_room_url,
ADD FOREIGN KEY IF NOT EXISTS (video_session_id) REFERENCES video_sessions(id) ON DELETE SET NULL;
