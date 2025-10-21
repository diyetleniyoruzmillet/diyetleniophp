#!/usr/bin/env php
<?php
/**
 * Run Database Migrations
 * Execute all pending SQL migrations
 */

// Change to project root
chdir(__DIR__ . '/..');

require_once __DIR__ . '/../includes/bootstrap.php';

echo "=== Diyetlenio Database Migration Runner ===\n\n";

try {
    $pdo = $db->getConnection();
    echo "✓ Database connected successfully!\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Migration files
$migrations = [
    'database/migrations/007_create_contact_messages_table.sql',
    'database/migrations/008_create_password_resets_table.sql',
    'database/migrations/009_create_article_comments_table.sql',
    'database/migrations/010_add_search_indexes.sql',
    'database/migrations/011_create_notifications_table.sql',
    'database/migrations/add_is_on_call_column.sql',
    'database/add_diet_plan_meals.sql',
    'database/add_iban_to_dietitians.sql',
];

$success = 0;
$failed = 0;
$skipped = 0;

foreach ($migrations as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $filename = basename($file);

    if (!file_exists($fullPath)) {
        echo "⚠ {$filename} - SKIPPED (file not found)\n";
        $skipped++;
        continue;
    }

    echo "{$filename}... ";

    try {
        $sql = file_get_contents($fullPath);

        // Handle multi-statement SQL
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);

        // Execute the SQL
        $result = $pdo->exec($sql);

        echo "✓ SUCCESS\n";
        $success++;

    } catch (PDOException $e) {
        $errorCode = $e->getCode();
        $errorMsg = $e->getMessage();

        // Check if error is "already exists" - treat as success
        if (strpos($errorMsg, 'already exists') !== false ||
            strpos($errorMsg, 'Duplicate column') !== false ||
            strpos($errorMsg, 'Duplicate key') !== false) {
            echo "✓ ALREADY EXISTS (OK)\n";
            $success++;
        } else {
            echo "✗ FAILED\n";
            echo "  Error: {$errorMsg}\n";
            $failed++;
        }
    }
}

echo "\n=== Migration Summary ===\n";
echo "✓ Success: {$success}\n";
echo "✗ Failed: {$failed}\n";
echo "⚠ Skipped: {$skipped}\n";
echo "Total: " . count($migrations) . "\n";

if ($failed === 0) {
    echo "\n✓✓✓ All migrations completed successfully! ✓✓✓\n";
    exit(0);
} else {
    echo "\n⚠ Some migrations failed. Please check errors above.\n";
    exit(1);
}
