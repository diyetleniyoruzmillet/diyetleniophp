-- ============================================
-- Complete Production Setup SQL Script
-- ============================================
-- This script will:
-- 1. Create client_profiles table
-- 2. Create weight_tracking table
-- 3. Capitalize all user names
-- ============================================

USE diyetlenio_db;

-- ============================================
-- Step 1: Create client_profiles table
-- ============================================

SELECT '============================================' as '';
SELECT 'Step 1: Creating client_profiles table...' as '';
SELECT '============================================' as '';

CREATE TABLE IF NOT EXISTS client_profiles (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    height DECIMAL(5,2) COMMENT 'Santimetre',
    target_weight DECIMAL(5,2) COMMENT 'Kilogram',
    health_conditions TEXT,
    allergies TEXT,
    dietary_preferences TEXT COMMENT 'Vejeteryan, vegan, vs.',
    activity_level ENUM('sedentary', 'light', 'moderate', 'active', 'very_active'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ Client profiles table created!' as '';
SELECT '' as '';

-- ============================================
-- Step 2: Create weight_tracking table
-- ============================================

SELECT '============================================' as '';
SELECT 'Step 2: Creating weight_tracking table...' as '';
SELECT '============================================' as '';

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

SELECT '✅ Weight tracking table created!' as '';
SELECT '' as '';

-- ============================================
-- Step 3: Capitalize User Names
-- ============================================

SELECT '============================================' as '';
SELECT 'Step 3: Capitalizing user names...' as '';
SELECT '============================================' as '';

-- Show preview of changes (first 5 users)
SELECT 'Preview of name changes (first 5):' as '';
SELECT
    full_name as 'Old Name',
    CONCAT(
        UPPER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', 1), 1, 1)),
        LOWER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', 1), 2)),
        IF(LOCATE(' ', full_name) > 0,
            CONCAT(' ',
                UPPER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', -1), 1, 1)),
                LOWER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', -1), 2))
            ),
            ''
        )
    ) as 'New Name'
FROM users
WHERE full_name IS NOT NULL
AND full_name != ''
AND full_name != CONCAT(
    UPPER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', 1), 1, 1)),
    LOWER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', 1), 2)),
    IF(LOCATE(' ', full_name) > 0,
        CONCAT(' ',
            UPPER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', -1), 1, 1)),
            LOWER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', -1), 2))
        ),
        ''
    )
)
LIMIT 5;

SELECT '' as '';

-- Update names
UPDATE users
SET full_name = CONCAT(
    UPPER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', 1), 1, 1)),
    LOWER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', 1), 2)),
    IF(LOCATE(' ', full_name) > 0,
        CONCAT(' ',
            UPPER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', -1), 1, 1)),
            LOWER(SUBSTRING(SUBSTRING_INDEX(full_name, ' ', -1), 2))
        ),
        ''
    )
)
WHERE full_name IS NOT NULL
AND full_name != '';

SELECT CONCAT('✅ Updated ', ROW_COUNT(), ' user names') as '';
SELECT '' as '';

-- ============================================
-- Verification
-- ============================================

SELECT '============================================' as '';
SELECT 'Verification Results' as '';
SELECT '============================================' as '';

-- Check tables exist
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ client_profiles table exists'
        ELSE '❌ client_profiles table NOT found'
    END as 'Table Check'
FROM information_schema.tables
WHERE table_schema = 'diyetlenio_db'
AND table_name = 'client_profiles';

SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ weight_tracking table exists'
        ELSE '❌ weight_tracking table NOT found'
    END as 'Table Check'
FROM information_schema.tables
WHERE table_schema = 'diyetlenio_db'
AND table_name = 'weight_tracking';

-- Show sample of capitalized names
SELECT '' as '';
SELECT 'Sample of updated user names:' as '';
SELECT full_name FROM users WHERE full_name IS NOT NULL ORDER BY RAND() LIMIT 10;

SELECT '' as '';
SELECT '============================================' as '';
SELECT '🎉 SETUP COMPLETED SUCCESSFULLY!' as '';
SELECT '============================================' as '';
SELECT '' as '';
SELECT 'Next steps:' as '';
SELECT '1. Test client profile page: /client/profile.php' as '';
SELECT '2. Test weight tracking page: /client/weight-tracking.php' as '';
SELECT '3. Verify user names look properly capitalized' as '';
SELECT '============================================' as '';
