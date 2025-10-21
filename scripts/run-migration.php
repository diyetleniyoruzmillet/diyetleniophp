<?php
/**
 * Run is_on_call migration
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

echo "🔧 Running migration: add_is_on_call_column...\n\n";

try {
    // Check if column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM dietitian_profiles LIKE 'is_on_call'");
    if ($stmt->fetch()) {
        echo "⚠️  Column 'is_on_call' already exists, skipping...\n";
        exit(0);
    }

    // Add column
    $conn->exec("
        ALTER TABLE dietitian_profiles
        ADD COLUMN is_on_call TINYINT(1) DEFAULT 0 COMMENT 'Acil nöbetçi durumu (1: nöbetçi, 0: değil)'
        AFTER is_approved
    ");

    echo "✅ Column 'is_on_call' added successfully\n";

    // Add index
    $conn->exec("
        CREATE INDEX idx_on_call ON dietitian_profiles(is_on_call, is_approved, user_id)
    ");

    echo "✅ Index created successfully\n\n";
    echo "🎉 Migration completed!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
