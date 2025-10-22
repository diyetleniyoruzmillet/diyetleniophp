<?php
/**
 * Migration Runner - Admin Only
 * WARNING: Remove this file after running migrations!
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin only
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    die('Access denied. Admin only.');
}

// Security token
$token = $_GET['token'] ?? '';
$expectedToken = md5('diyetlenio-migrate-2025');

if ($token !== $expectedToken) {
    die('Invalid token. Use: ?token=' . $expectedToken);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Database Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        pre { background: #000; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Database Migration Runner</h1>
    <pre><?php

echo "=== Diyetlenio Database Migrations ===\n\n";

try {
    $pdo = $db->getConnection();
    echo "<span class='success'>✓ Database connected!</span>\n\n";
} catch (Exception $e) {
    echo "<span class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    exit;
}

$baseDir = __DIR__ . '/../..';
$migrations = [
    'database/migrations/007_create_contact_messages_table.sql',
    'database/migrations/008_create_password_resets_table.sql',
    'database/migrations/009_create_article_comments_table.sql',
    'database/migrations/010_add_search_indexes.sql',
    'database/migrations/011_create_notifications_table.sql',
    'database/migrations/012_add_phone_to_contact_messages.sql',
    'database/migrations/013_create_weight_tracking_table.sql',
    'database/migrations/014_create_client_profiles_table.sql',
    'database/migrations/015_create_client_dietitian_assignments_table.sql',
    'database/migrations/016_create_payments_table.sql',
    'database/migrations/017_create_rate_limits_table.sql',
    'database/migrations/018_add_profile_photo_to_users.sql',
    'database/migrations/add_is_on_call_column.sql',
    'database/add_diet_plan_meals.sql',
    'database/add_iban_to_dietitians.sql',
];

$success = 0;
$failed = 0;
$skipped = 0;

foreach ($migrations as $file) {
    $path = $baseDir . '/' . $file;
    $name = basename($file);

    if (!file_exists($path)) {
        echo "<span class='warning'>⚠ {$name} - FILE NOT FOUND</span>\n";
        $skipped++;
        continue;
    }

    echo "{$name}... ";
    flush();

    try {
        $sql = file_get_contents($path);

        // Execute multi-statement SQL
        $pdo->exec($sql);

        echo "<span class='success'>✓ SUCCESS</span>\n";
        $success++;

    } catch (PDOException $e) {
        $msg = $e->getMessage();

        // Check if already exists
        if (strpos($msg, 'already exists') !== false ||
            strpos($msg, 'Duplicate column') !== false ||
            strpos($msg, 'Duplicate key') !== false ||
            strpos($msg, 'Table') !== false && strpos($msg, 'already exists') !== false) {
            echo "<span class='info'>✓ ALREADY EXISTS (OK)</span>\n";
            $success++;
        } else {
            echo "<span class='error'>✗ FAILED</span>\n";
            echo "<span class='error'>  Error: " . htmlspecialchars($msg) . "</span>\n";
            $failed++;
        }
    }
}

// Extra: Capitalize user names
echo "\n=== Additional Tasks ===\n";
echo "Capitalizing user names... ";
flush();

try {
    $stmt = $pdo->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL AND full_name != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $updated = 0;

    foreach ($users as $user) {
        $oldName = $user['full_name'];
        $newName = mb_convert_case($oldName, MB_CASE_TITLE, 'UTF-8');

        if ($oldName !== $newName) {
            $updateStmt->execute([$newName, $user['id']]);
            $updated++;
        }
    }

    echo "<span class='success'>✓ Updated {$updated} names (out of " . count($users) . " total)</span>\n";
} catch (PDOException $e) {
    echo "<span class='error'>✗ Failed: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

echo "\n=== Migration Summary ===\n";
echo "<span class='success'>✓ Success: {$success}</span>\n";
echo "<span class='error'>✗ Failed: {$failed}</span>\n";
echo "<span class='warning'>⚠ Skipped: {$skipped}</span>\n";
echo "Total: " . count($migrations) . "\n\n";

if ($failed === 0) {
    echo "<span class='success'>✓✓✓ ALL MIGRATIONS COMPLETED SUCCESSFULLY! ✓✓✓</span>\n\n";
    echo "<span class='warning'>⚠ IMPORTANT: Delete this file now!</span>\n";
    echo "Run: rm " . __FILE__ . "\n";
} else {
    echo "<span class='error'>⚠ Some migrations failed. Check errors above.</span>\n";
}

?></pre>

<h3>Next Steps:</h3>
<ol>
    <li>Verify all migrations completed successfully</li>
    <li><strong>DELETE THIS FILE:</strong> <code>rm <?= __FILE__ ?></code></li>
    <li>Clear browser cache and reload admin panel</li>
</ol>

</body>
</html>
