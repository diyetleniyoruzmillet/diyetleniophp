<?php
/**
 * Debug Endpoint - Environment Variables ve Database Test
 * SADECE GELIŞTIRME İÇİN - PRODUCTION'DA SİLİN!
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Debug Info</h1>";

echo "<h2>1. Environment Variables</h2>";
echo "<pre>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
echo "DB_DATABASE: " . (getenv('DB_DATABASE') ?: 'NOT SET') . "\n";
echo "DB_USERNAME: " . (getenv('DB_USERNAME') ?: 'NOT SET') . "\n";
echo "DB_PASSWORD: " . (getenv('DB_PASSWORD') ? '***SET***' : 'NOT SET') . "\n";
echo "DB_CONNECTION: " . (getenv('DB_CONNECTION') ?: 'NOT SET') . "\n";
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'NOT SET') . "\n";
echo "APP_DEBUG: " . (getenv('APP_DEBUG') ?: 'NOT SET') . "\n";
echo "</pre>";

echo "<h2>2. Database Connection Test</h2>";
echo "<pre>";

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'railway';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    echo "Connecting to: {$host}:{$port}/{$database}\n";
    echo "Username: {$username}\n\n";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Database connection SUCCESSFUL!\n\n";

    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table test: {$result['count']} users found\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTotal tables: " . count($tables) . "\n";
    echo "Tables: " . implode(', ', $tables) . "\n";

} catch (PDOException $e) {
    echo "❌ Database connection FAILED!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "</pre>";

echo "<h2>3. PHP Info</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "</pre>";

echo "<h2>4. File System Check</h2>";
echo "<pre>";
$dirs = [
    'Root' => dirname(__DIR__),
    'Config' => dirname(__DIR__) . '/config',
    'Classes' => dirname(__DIR__) . '/classes',
    'Includes' => dirname(__DIR__) . '/includes',
];

foreach ($dirs as $name => $path) {
    echo "{$name}: " . ($path) . " - " . (is_dir($path) ? '✅ EXISTS' : '❌ NOT FOUND') . "\n";
}
echo "</pre>";

echo "<hr>";
echo "<p><a href='/'>← Back to Home</a> | <a href='/health.php'>Health Check</a></p>";
