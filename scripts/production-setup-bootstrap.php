<?php
/**
 * Production Setup - Using Application Bootstrap
 * This uses the app's own database connection
 */

// Load application bootstrap
require_once __DIR__ . '/../includes/bootstrap.php';

echo "🚀 Starting Production Setup...\n\n";

try {
    $conn = $db->getConnection();

    // Step 1: Create client_profiles table
    echo "📋 Step 1: Creating client_profiles table...\n";

    $sql = "
    CREATE TABLE IF NOT EXISTS client_profiles (
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL UNIQUE,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        height DECIMAL(5,2) COMMENT 'Santimetre',
        target_weight DECIMAL(5,2) COMMENT 'Kilogram',
        health_conditions TEXT,
        allergies TEXT,
        dietary_preferences TEXT,
        activity_level ENUM('sedentary', 'light', 'moderate', 'active', 'very_active'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->exec($sql);
    echo "✅ Client profiles table created\n\n";

    // Step 2: Create weight_tracking table
    echo "📋 Step 2: Creating weight_tracking table...\n";

    $sql = "
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->exec($sql);
    echo "✅ Weight tracking table created\n\n";

    // Step 3: Capitalize names
    echo "📋 Step 3: Capitalizing user names...\n";

    $stmt = $conn->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " users\n";

    $updateStmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $updated = 0;

    foreach ($users as $user) {
        $oldName = $user['full_name'];
        $newName = mb_convert_case($oldName, MB_CASE_TITLE, 'UTF-8');

        if ($oldName !== $newName) {
            $updateStmt->execute([$newName, $user['id']]);
            echo "  '{$oldName}' → '{$newName}'\n";
            $updated++;
        }
    }

    echo "\n✅ Updated {$updated} names\n\n";

    echo "🎉 SETUP COMPLETED SUCCESSFULLY!\n\n";

    // Verify tables exist
    echo "📊 Verification:\n";
    $tables = $conn->query("SHOW TABLES LIKE 'client_profiles'")->fetchAll();
    echo ($tables ? "✅" : "❌") . " client_profiles table exists\n";

    $tables = $conn->query("SHOW TABLES LIKE 'weight_tracking'")->fetchAll();
    echo ($tables ? "✅" : "❌") . " weight_tracking table exists\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
