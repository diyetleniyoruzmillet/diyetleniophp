<?php
/**
 * Database Connection Test
 * Test veritabanı bağlantısını kontrol eder
 */

// Direkt PDO bağlantısı dene
$dbHost = getenv('DB_HOST') ?: 'nozomi.proxy.rlwy.net';
$dbPort = getenv('DB_PORT') ?: '12434';
$dbName = getenv('DB_DATABASE') ?: 'railway';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: 'HrpWATAjzmJhHeUuUWuItKmmwvtVXGZf';

echo "<!DOCTYPE html><html><head><title>DB Test</title></head><body>";
echo "<h1>Database Connection Test</h1>";
echo "<pre>";

echo "Environment Variables:\n";
echo "DB_HOST: " . ($dbHost ?: 'NOT SET') . "\n";
echo "DB_PORT: " . ($dbPort ?: 'NOT SET') . "\n";
echo "DB_DATABASE: " . ($dbName ?: 'NOT SET') . "\n";
echo "DB_USERNAME: " . ($dbUser ?: 'NOT SET') . "\n";
echo "DB_PASSWORD: " . (empty($dbPass) ? 'NOT SET' : '***SET***') . "\n\n";

// Test 1: Direct PDO Connection
echo "=== TEST 1: Direct PDO Connection ===\n";
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "✅ Direct PDO connection successful!\n\n";

    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "\n";

} catch (PDOException $e) {
    echo "❌ Direct PDO connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n\n";
}

// Test 2: Bootstrap Connection
echo "\n=== TEST 2: Bootstrap Connection ===\n";
try {
    require_once __DIR__ . '/../includes/bootstrap.php';
    $conn = $db->getConnection();
    echo "✅ Bootstrap connection successful!\n\n";

    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users via bootstrap: " . $result['count'] . "\n";

} catch (Exception $e) {
    echo "❌ Bootstrap connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 3: .env file check
echo "\n=== TEST 3: .env File Check ===\n";
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    echo "✅ .env file exists\n";
    echo "Path: " . $envFile . "\n";
    echo "Size: " . filesize($envFile) . " bytes\n";
    echo "Readable: " . (is_readable($envFile) ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ .env file NOT found!\n";
    echo "Expected path: " . $envFile . "\n";
}

// Test 4: PHP Extensions
echo "\n=== TEST 4: PHP Extensions ===\n";
echo "PDO: " . (extension_loaded('pdo') ? '✅ Loaded' : '❌ Not loaded') . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Not loaded') . "\n";

echo "</pre></body></html>";
?>
