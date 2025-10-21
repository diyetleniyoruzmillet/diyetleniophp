<?php
/**
 * Diyetlenio - Veritabanı Yapılandırma Dosyası
 *
 * Bu dosya veritabanı bağlantı ayarlarını içerir.
 * .env dosyasından yapılandırma bilgilerini okur.
 */

// .env dosyasını yükle
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envFile);

    foreach ($lines as $line) {
        $line = trim($line);

        // Boş satırları ve yorumları atla
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // KEY=VALUE formatını parse et
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Tırnak işaretlerini temizle
            $value = trim($value, '"\'');

            // Ortam değişkenine kaydet
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Veritabanı yapılandırma ayarları
return [
    'driver'    => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'database'  => $_ENV['DB_DATABASE'] ?? 'diyetlenio',
    'username'  => $_ENV['DB_USERNAME'] ?? 'diyetlenio_user',
    'password'  => $_ENV['DB_PASSWORD'] ?? 'diyetlenio2025',
    'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
    'prefix'    => $_ENV['DB_PREFIX'] ?? '',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
