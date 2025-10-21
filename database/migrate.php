<?php
/**
 * Database Migration Runner
 * Runs all SQL migration files in the migrations directory
 */

require_once __DIR__ . '/../includes/bootstrap.php';

echo "=== Diyetlenio Database Migration Runner ===\n\n";

$migrationsDir = __DIR__ . '/migrations';
$migrationFiles = glob($migrationsDir . '/*.sql');

if (empty($migrationFiles)) {
    echo "No migration files found.\n";
    exit(0);
}

sort($migrationFiles);

echo "Found " . count($migrationFiles) . " migration file(s).\n\n";

$successCount = 0;
$failCount = 0;

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    echo "Running: {$filename}... ";

    try {
        $sql = file_get_contents($file);

        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->exec($statement);
            }
        }

        echo "✓ SUCCESS\n";
        $successCount++;

    } catch (PDOException $e) {
        echo "✗ FAILED\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "\n=== Migration Summary ===\n";
echo "Success: {$successCount}\n";
echo "Failed: {$failCount}\n";
echo "Total: " . count($migrationFiles) . "\n";

if ($failCount === 0) {
    echo "\n✓ All migrations completed successfully!\n";
    exit(0);
} else {
    echo "\n✗ Some migrations failed. Please check errors above.\n";
    exit(1);
}
