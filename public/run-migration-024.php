<?php
/**
 * Migration Runner for 024_create_email_templates
 */

// Parse .env file manually
function loadEnv($path) {
    if (!file_exists($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove quotes
        $value = trim($value, '"\'');

        $env[$name] = $value;
        $_ENV[$name] = $value;
    }

    return $env;
}

loadEnv(__DIR__ . '/../.env');

try {
    // Direct database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? '';
    $username = $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = file_get_contents(__DIR__ . '/../database/migrations/024_create_email_templates.sql');
    $conn->exec($sql);

    echo "✅ Migration 024 (Email Templates) executed successfully!\n";
    echo "📧 Email templates table created and populated with default templates.\n";
} catch (PDOException $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
}
