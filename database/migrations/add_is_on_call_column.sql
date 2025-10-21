-- Add is_on_call column to dietitian_profiles table
ALTER TABLE dietitian_profiles
ADD COLUMN is_on_call TINYINT(1) DEFAULT 0 COMMENT 'Acil nöbetçi durumu (1: nöbetçi, 0: değil)'
AFTER is_approved;

-- Add index for faster queries
CREATE INDEX idx_on_call ON dietitian_profiles(is_on_call, is_approved, user_id);
