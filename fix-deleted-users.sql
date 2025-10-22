-- Fix Deleted Users with Multiple Prefixes
--
-- This SQL script fixes users with corrupted email addresses like:
-- deleted_1761150056_deleted_1761150044_muradcev@gmail.com
--
-- Strategy: Keep the first timestamp, remove all other deleted_ prefixes

-- Step 1: Check corrupted users
SELECT
    id,
    email,
    is_active,
    CHAR_LENGTH(email) - CHAR_LENGTH(REPLACE(email, 'deleted_', '')) as prefix_count,
    created_at
FROM users
WHERE email LIKE 'deleted_%deleted_%'
ORDER BY id;

-- Step 2: Fix each user individually
-- User ID 2
UPDATE users
SET email = 'deleted_1761150056_muradcev@gmail.com'
WHERE id = 2 AND email LIKE 'deleted_%deleted_%';

-- User ID 9
UPDATE users
SET email = 'deleted_1761146683_ayse.yilmaz@diyetlenio.com'
WHERE id = 9 AND email LIKE 'deleted_%deleted_%';

-- User ID 10
UPDATE users
SET email = 'deleted_1761146681_mehmet.demir@diyetlenio.com'
WHERE id = 10 AND email LIKE 'deleted_%deleted_%';

-- User ID 11
UPDATE users
SET email = 'deleted_1761146677_zeynep.kaya@diyetlenio.com'
WHERE id = 11 AND email LIKE 'deleted_%deleted_%';

-- User ID 12
UPDATE users
SET email = 'deleted_1761146673_ahmet.ozturk@diyetlenio.com'
WHERE id = 12 AND email LIKE 'deleted_%deleted_%';

-- User ID 13 (en kötü durum - 11 deleted_ prefix'i var!)
UPDATE users
SET email = 'deleted_1761146652_elif.sahin@diyetlenio.com'
WHERE id = 13 AND email LIKE 'deleted_%deleted_%';

-- User ID 14
UPDATE users
SET email = 'deleted_1761146666_can.yildirim@diyetlenio.com'
WHERE id = 14 AND email LIKE 'deleted_%deleted_%';

-- User ID 15
UPDATE users
SET email = 'deleted_1761146662_ayse.yilmaz@diyetlenio.com'
WHERE id = 15 AND email LIKE 'deleted_%deleted_%';

-- Step 3: Verify the fix
SELECT
    COUNT(*) as remaining_corrupted_users
FROM users
WHERE email LIKE 'deleted_%deleted_%';

-- Step 4: Show all deleted users after fix
SELECT
    id,
    email,
    is_active,
    created_at
FROM users
WHERE email LIKE 'deleted_%'
ORDER BY id;
