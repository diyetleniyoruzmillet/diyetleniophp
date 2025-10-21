<?php
/**
 * Simple Migration Runner - Direct PDO
 */

echo "=== Database Migration ===\n\n";

// Database credentials
$host = 'localhost';
$dbname = 'diyetlenio_db';
$username = 'diyetlenio_user';
$password = 'Vw88kX74Y_P5@_';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Database connected!\n\n";
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$baseDir = __DIR__ . '/..';
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

foreach ($migrations as $file) {
    $path = $baseDir . '/' . $file;
    $name = basename($file);

    if (!file_exists($path)) {
        echo "⚠ {$name} - NOT FOUND\n";
        continue;
    }

    echo "{$name}... ";

    try {
        $sql = file_get_contents($path);
        $pdo->exec($sql);
        echo "✓ SUCCESS\n";
        $success++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "✓ ALREADY EXISTS\n";
            $success++;
        } else {
            echo "✗ FAILED: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
}

echo "\n=== Summary ===\n";
echo "Success: {$success}\n";
echo "Failed: {$failed}\n";
echo "\n" . ($failed === 0 ? "✓ ALL DONE!" : "⚠ CHECK ERRORS") . "\n";
