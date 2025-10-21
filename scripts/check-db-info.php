<?php
/**
 * Veritabanı Bilgisi Kontrol Script
 */

echo "====================================\n";
echo "VERİTABANI BİLGİ KONTROLÜ\n";
echo "====================================\n\n";

echo "Farklı veritabanı isimleri deneniyor...\n\n";

$databases = ['diyetlenio', 'diyetlenio_db'];
$users = [
    ['diyetlenio_user', 'diyetlenio2025'],
    ['diyetlenio_user', 'Vw88kX74Y_P5@_'],
    ['root', ''],
    ['root', 'root']
];

foreach ($databases as $dbName) {
    foreach ($users as list($username, $password)) {
        echo "Deneniyor: DB={$dbName}, User={$username}, Pass=" . ($password ? str_repeat('*', min(strlen($password), 10)) : '(boş)') . "\n";

        try {
            $dsn = "mysql:host=localhost;dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            echo "✓✓✓ BAŞARILI! ✓✓✓\n\n";
            echo "ÇALIŞAN AYARLAR:\n";
            echo "  Veritabanı: {$dbName}\n";
            echo "  Kullanıcı: {$username}\n";
            echo "  Şifre: {$password}\n\n";

            // Admin kullanıcısını kontrol et
            $stmt = $pdo->query("SELECT id, email, full_name, user_type FROM users WHERE user_type = 'admin' LIMIT 5");
            $admins = $stmt->fetchAll();

            if ($admins) {
                echo "Admin Kullanıcıları:\n";
                foreach ($admins as $admin) {
                    echo "  - ID: {$admin['id']}, Email: {$admin['email']}, Ad: {$admin['full_name']}\n";
                }
                echo "\n";
            } else {
                echo "⚠️  Admin kullanıcısı bulunamadı!\n\n";
            }

            echo ".env dosyasını güncelle:\n";
            echo "DB_DATABASE={$dbName}\n";
            echo "DB_USERNAME={$username}\n";
            echo "DB_PASSWORD={$password}\n\n";

            exit(0);

        } catch (PDOException $e) {
            echo "✗ Başarısız: " . $e->getMessage() . "\n\n";
        }
    }
}

echo "====================================\n";
echo "HİÇBİR BAĞLANTI BAŞARILI OLMADI!\n";
echo "====================================\n\n";

echo "ÇÖZÜM ÖNERİLERİ:\n\n";
echo "1. MySQL çalışıyor mu kontrol edin:\n";
echo "   systemctl status mysql\n\n";

echo "2. Veritabanı var mı kontrol edin:\n";
echo "   sudo mysql -e \"SHOW DATABASES;\"\n\n";

echo "3. Veritabanı ve kullanıcı oluşturun:\n";
echo "   sudo mysql\n";
echo "   CREATE DATABASE IF NOT EXISTS diyetlenio;\n";
echo "   CREATE USER IF NOT EXISTS 'diyetlenio_user'@'localhost' IDENTIFIED BY 'diyetlenio2025';\n";
echo "   GRANT ALL PRIVILEGES ON diyetlenio.* TO 'diyetlenio_user'@'localhost';\n";
echo "   FLUSH PRIVILEGES;\n";
echo "   EXIT;\n\n";
