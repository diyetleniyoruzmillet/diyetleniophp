#!/usr/bin/env php
<?php
/**
 * CLI script to fix deleted users
 * This version doesn't require sessions or CSRF tokens
 */

// Prevent running from web
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

// Load only Database class
define('BASE_PATH', dirname(__DIR__));

// Load .env file manually
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv(sprintf('%s=%s', $name, $value));
        }
    }
}

// Direct PDO connection using env vars
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_DATABASE') ?: 'diyetlenio_db';
$username = getenv('DB_USERNAME') ?: 'diyetlenio_user';
$password = getenv('DB_PASSWORD') ?: '';

echo "Connecting to database...\n";
echo "Host: $host\n";
echo "Database: $dbname\n";
echo "Username: $username\n\n";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✅ Database connected successfully!\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

echo "=== STEP 1: CHECKING CORRUPTED USERS ===\n\n";

$stmt = $conn->query("
    SELECT id, email, is_active, created_at
    FROM users
    WHERE email LIKE 'deleted_%deleted_%'
    ORDER BY id
");
$corruptedUsers = $stmt->fetchAll();

echo "Found " . count($corruptedUsers) . " users with multiple deleted_ prefixes:\n\n";

if (count($corruptedUsers) === 0) {
    echo "No corrupted users found. Everything is clean!\n";
    exit(0);
}

foreach ($corruptedUsers as $user) {
    $prefixCount = substr_count($user['email'], 'deleted_');
    echo "  ID: {$user['id']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Prefixes: {$prefixCount}\n";
    echo "  Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    echo "  ---\n";
}

echo "\n=== STEP 2: FIXING USERS ===\n\n";
echo "Do you want to proceed with the fix? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'yes') {
    echo "Aborted by user.\n";
    exit(0);
}

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

        echo "  ✅ Fixed user ID {$user['id']}\n";
        echo "     OLD: {$user['email']}\n";
        echo "     NEW: {$newEmail}\n\n";

        $fixed++;
    } catch (Exception $e) {
        echo "  ❌ Error fixing user ID {$user['id']}: " . $e->getMessage() . "\n\n";
        $errors++;
    }
}

echo "\n=== STEP 3: VERIFICATION ===\n\n";

$stmt = $conn->query("
    SELECT COUNT(*) as count
    FROM users
    WHERE email LIKE 'deleted_%deleted_%'
");
$result = $stmt->fetch();

echo "Summary:\n";
echo "  Fixed: {$fixed}\n";
echo "  Errors: {$errors}\n";
echo "  Remaining corrupted: {$result['count']}\n\n";

if ($result['count'] == 0) {
    echo "✅ All users fixed successfully!\n";
} else {
    echo "⚠️  Some users still have issues. Check manually.\n";
}
