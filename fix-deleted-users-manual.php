<?php
/**
 * Manual script to fix deleted users with multiple prefixes
 * Run this from command line: php fix-deleted-users-manual.php
 */

// Direct database connection
try {
    $conn = new PDO(
        'mysql:host=localhost;dbname=diyetlenio_db;charset=utf8mb4',
        'diyetlenio_user',
        'Vw88kX74Y_P5@_',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "=== CHECKING CORRUPTED USERS ===\n\n";

// Find users with multiple deleted_ prefixes
$stmt = $conn->query("
    SELECT id, email, is_active, created_at
    FROM users
    WHERE email LIKE 'deleted_%deleted_%'
    ORDER BY id
");
$corruptedUsers = $stmt->fetchAll();

echo "Found " . count($corruptedUsers) . " users with multiple deleted_ prefixes:\n\n";

foreach ($corruptedUsers as $user) {
    $prefixCount = substr_count($user['email'], 'deleted_');
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Prefix count: {$prefixCount}\n";
    echo "---\n";
}

if (count($corruptedUsers) === 0) {
    echo "No corrupted users found. Everything is clean!\n";
    exit(0);
}

echo "\n=== STARTING FIX ===\n\n";

$fixed = 0;
$errors = 0;

foreach ($corruptedUsers as $user) {
    // Remove all 'deleted_TIMESTAMP_' patterns, extract original email
    $originalEmail = preg_replace('/^(deleted_\d+_)+/', '', $user['email']);

    // Get first timestamp from the email
    preg_match('/^deleted_(\d+)_/', $user['email'], $matches);
    $firstTimestamp = $matches[1] ?? time();

    // Create new email with single deleted_ prefix
    $newEmail = "deleted_{$firstTimestamp}_{$originalEmail}";

    try {
        $updateStmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $updateStmt->execute([$newEmail, $user['id']]);

        echo "✅ Fixed user ID {$user['id']}\n";
        echo "   OLD: {$user['email']}\n";
        echo "   NEW: {$newEmail}\n\n";

        $fixed++;
    } catch (Exception $e) {
        echo "❌ Error fixing user ID {$user['id']}: " . $e->getMessage() . "\n\n";
        $errors++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Fixed: {$fixed}\n";
echo "Errors: {$errors}\n";

// Verify
echo "\n=== VERIFICATION ===\n";
$stmt = $conn->query("
    SELECT COUNT(*) as count
    FROM users
    WHERE email LIKE 'deleted_%deleted_%'
");
$result = $stmt->fetch();
echo "Remaining corrupted users: {$result['count']}\n";

if ($result['count'] == 0) {
    echo "✅ All users fixed successfully!\n";
} else {
    echo "⚠️ Some users still have issues. Check manually.\n";
}
