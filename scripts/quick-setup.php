<?php
/**
 * Quick Production Setup
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ .env file not found\n");
}

$env = parse_ini_file($envFile);

// Database connection
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $env['DB_HOST'],
        $env['DB_PORT'],
        $env['DB_DATABASE'],
        $env['DB_CHARSET']
    );

    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Database connected\n\n";

} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Step 1: Create weight_tracking table
echo "📋 Creating weight_tracking table...\n";

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

try {
    $pdo->exec($sql);
    echo "✅ Weight tracking table created\n\n";
} catch (PDOException $e) {
    echo "⚠️  Weight tracking table error: " . $e->getMessage() . "\n\n";
}

// Step 2: Capitalize names
echo "📋 Capitalizing user names...\n";

try {
    $stmt = $pdo->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");
    $users = $stmt->fetchAll();

    echo "Found " . count($users) . " users\n";

    $updateStmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
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

} catch (PDOException $e) {
    echo "❌ Name update error: " . $e->getMessage() . "\n\n";
}

echo "🎉 SETUP COMPLETED!\n";
