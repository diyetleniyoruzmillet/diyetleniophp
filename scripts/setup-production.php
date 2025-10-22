#!/usr/bin/env php
<?php
/**
 * Production Setup Script
 * - Creates weight_tracking table
 * - Capitalizes user names
 */

require_once __DIR__ . '/../includes/bootstrap.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           DIYETLENIO - PRODUCTION SETUP                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $conn = $db->getConnection();

    // Step 1: Create weight_tracking table
    echo "📋 Step 1: Creating weight_tracking table...\n";

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
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_client (client_id),
        INDEX idx_date (measurement_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->exec($sql);
    echo "✅ Weight tracking table created/verified\n\n";

    // Step 2: Capitalize user names
    echo "📋 Step 2: Capitalizing user names...\n";

    $stmt = $conn->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " users to process.\n";

    $updateStmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $updated = 0;

    foreach ($users as $user) {
        $oldName = $user['full_name'];

        // Capitalize first letter of each word (Turkish character support)
        $newName = mb_convert_case($oldName, MB_CASE_TITLE, 'UTF-8');

        if ($oldName !== $newName) {
            $updateStmt->execute([$newName, $user['id']]);
            echo "  Updated: '{$oldName}' → '{$newName}'\n";
            $updated++;
        }
    }

    echo "\n✅ Name capitalization completed!\n";
    echo "   Total users: " . count($users) . "\n";
    echo "   Updated: {$updated}\n";
    echo "   Unchanged: " . (count($users) - $updated) . "\n\n";

    // Step 3: Verify
    echo "📋 Step 3: Verification...\n";

    // Check weight_tracking table
    $stmt = $conn->query("SHOW TABLES LIKE 'weight_tracking'");
    $tableExists = $stmt->fetch();
    echo ($tableExists ? "✅" : "❌") . " weight_tracking table: " . ($tableExists ? "EXISTS" : "NOT FOUND") . "\n";

    // Check if any lowercase names remain
    $stmt = $conn->query("
        SELECT COUNT(*) as count FROM users
        WHERE full_name IS NOT NULL
        AND full_name != ''
        AND full_name REGEXP BINARY '^[a-z]'
    ");
    $lowercaseCount = $stmt->fetch()['count'];
    echo ($lowercaseCount == 0 ? "✅" : "⚠️") . " Names starting with lowercase: {$lowercaseCount}\n";

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    SETUP COMPLETED! ✅                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
