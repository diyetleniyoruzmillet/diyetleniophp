<?php
/**
 * Direct Production Setup - Standalone
 * No bootstrap, direct database connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 Starting Production Setup (Direct Method)...\n\n";

// Try to connect using mysqli (more reliable than PDO sometimes)
$mysqli = @new mysqli('localhost', 'root', '', 'diyetlenio_db');

// Check connection
if ($mysqli->connect_error) {
    echo "❌ Connection failed with root user to diyetlenio_db\n";
    echo "Error: " . $mysqli->connect_error . "\n\n";

    echo "Trying with root user to 'diyetlenio' database...\n";
    $mysqli = @new mysqli('localhost', 'root', '', 'diyetlenio');

    if ($mysqli->connect_error) {
        die("❌ Connection failed to 'diyetlenio' as well\nError: " . $mysqli->connect_error . "\n");
    }
}

$mysqli->set_charset('utf8mb4');
echo "✅ Database connected\n\n";

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

if ($mysqli->query($sql)) {
    echo "✅ Client profiles table created\n\n";
} else {
    echo "⚠️  Client profiles error: " . $mysqli->error . "\n\n";
}

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

if ($mysqli->query($sql)) {
    echo "✅ Weight tracking table created\n\n";
} else {
    echo "⚠️  Weight tracking error: " . $mysqli->error . "\n\n";
}

// Step 3: Capitalize names
echo "📋 Step 3: Capitalizing user names...\n";

$result = $mysqli->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");

if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($users) . " users\n";

    $updated = 0;
    $stmt = $mysqli->prepare("UPDATE users SET full_name = ? WHERE id = ?");

    foreach ($users as $user) {
        $oldName = $user['full_name'];
        $newName = mb_convert_case($oldName, MB_CASE_TITLE, 'UTF-8');

        if ($oldName !== $newName) {
            $stmt->bind_param('si', $newName, $user['id']);
            if ($stmt->execute()) {
                echo "  '{$oldName}' → '{$newName}'\n";
                $updated++;
            }
        }
    }

    $stmt->close();
    echo "\n✅ Updated {$updated} names\n\n";
} else {
    echo "⚠️  Name update error: " . $mysqli->error . "\n\n";
}

// Verify tables exist
echo "📊 Verification:\n";

$result = $mysqli->query("SHOW TABLES LIKE 'client_profiles'");
echo ($result && $result->num_rows > 0 ? "✅" : "❌") . " client_profiles table exists\n";

$result = $mysqli->query("SHOW TABLES LIKE 'weight_tracking'");
echo ($result && $result->num_rows > 0 ? "✅" : "❌") . " weight_tracking table exists\n";

$mysqli->close();

echo "\n🎉 SETUP COMPLETED!\n";
