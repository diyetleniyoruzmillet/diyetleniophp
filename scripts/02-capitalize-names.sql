-- Capitalize user names (first letter of each word)
-- Run this in production: mysql -u root -p diyetlenio_db < 02-capitalize-names.sql

USE diyetlenio_db;

-- Show names that will be updated (PREVIEW)
SELECT
    id,
    full_name as old_name,
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
    ) as new_name
FROM users
WHERE full_name IS NOT NULL
AND full_name != ''
LIMIT 10;

-- If preview looks good, uncomment and run this:
/*
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

SELECT CONCAT('Updated ', ROW_COUNT(), ' names') as status;
*/
