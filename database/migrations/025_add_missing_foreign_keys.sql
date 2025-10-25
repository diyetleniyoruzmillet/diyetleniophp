-- Migration: 025_add_missing_foreign_keys.sql
-- Description: Add missing foreign keys to existing tables for data integrity
-- Created: 2025-10-26

-- Note: This migration adds foreign keys that may be missing from tables
-- created in earlier migrations or existing in the database without proper constraints

-- Add foreign key to emergency_consultations if not exists
ALTER TABLE emergency_consultations
ADD CONSTRAINT IF NOT EXISTS fk_emergency_consultations_user_id
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add index to emergency_consultations for performance
ALTER TABLE emergency_consultations
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_urgency (urgency_level),
ADD INDEX IF NOT EXISTS idx_created (created_at);

-- Add indexes to contact_messages for performance
ALTER TABLE contact_messages
ADD INDEX IF NOT EXISTS idx_created (created_at),
ADD INDEX IF NOT EXISTS idx_status (status);

-- Add indexes to password_resets for cleanup queries
ALTER TABLE password_resets
ADD INDEX IF NOT EXISTS idx_expires (expires_at);

-- Ensure all UNSIGNED types for ID fields (data integrity)
-- Note: These are informational only - actual ALTER would require data backup
-- ALTER TABLE payments MODIFY appointment_id INT UNSIGNED NOT NULL;
-- ALTER TABLE video_sessions MODIFY appointment_id INT UNSIGNED NOT NULL;
