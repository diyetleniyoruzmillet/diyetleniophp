<?php
/**
 * Admin Login Test Script
 * Bu script admin kullanıcısını ve login sistemini test eder
 */

echo "====================================\n";
echo "ADMIN LOGIN TEST SCRIPT\n";
echo "====================================\n\n";

// 1. Veritabanı bağlantısını test et
echo "1. Veritabanı Bağlantısı Test Ediliyor...\n";
echo "-------------------------------------------\n";

chdir(dirname(__DIR__));

// Config dosyasını oku
$config = require 'config/database.php';

echo "Host: {$config['host']}\n";
echo "Database: {$config['database']}\n";
echo "Username: {$config['username']}\n";
echo "Password: " . (empty($config['password']) ? '(boş)' : str_repeat('*', strlen($config['password']))) . "\n\n";

try {
    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );

    echo "✓ Veritabanı bağlantısı başarılı!\n\n";

    // 2. Admin kullanıcısını kontrol et
    echo "2. Admin Kullanıcısı Kontrol Ediliyor...\n";
    echo "-------------------------------------------\n";

    $stmt = $pdo->prepare("SELECT id, email, password, full_name, user_type, is_active, is_email_verified FROM users WHERE email = ?");
    $stmt->execute(['admin@diyetlenio.com']);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "✗ Admin kullanıcısı bulunamadı!\n";
        echo "Çözüm: sudo mysql diyetlenio < scripts/create-admin.sql\n\n";
        exit(1);
    }

    echo "✓ Admin kullanıcısı bulundu!\n";
    echo "  ID: {$admin['id']}\n";
    echo "  Email: {$admin['email']}\n";
    echo "  Ad: {$admin['full_name']}\n";
    echo "  Tip: {$admin['user_type']}\n";
    echo "  Aktif: " . ($admin['is_active'] ? 'Evet' : 'Hayır') . "\n";
    echo "  Email Doğrulandı: " . ($admin['is_email_verified'] ? 'Evet' : 'Hayır') . "\n";
    echo "  Şifre Hash: " . substr($admin['password'], 0, 20) . "...\n\n";

    // 3. Şifre doğrulamasını test et
    echo "3. Şifre Doğrulama Test Ediliyor...\n";
    echo "-------------------------------------------\n";

    $testPasswords = ['Admin123!', 'password', 'admin123', 'Admin123'];

    foreach ($testPasswords as $testPassword) {
        $result = password_verify($testPassword, $admin['password']);
        $status = $result ? '✓' : '✗';
        echo "{$status} Şifre: '{$testPassword}' - " . ($result ? 'DOĞRU' : 'Yanlış') . "\n";
    }

    echo "\n";

    // 4. Login sistemini simüle et
    echo "4. Login Sistemi Simülasyonu...\n";
    echo "-------------------------------------------\n";

    $testEmail = 'admin@diyetlenio.com';
    $testPassword = 'Admin123!';

    echo "Email: {$testEmail}\n";
    echo "Şifre: {$testPassword}\n\n";

    // Kullanıcıyı bul
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "✗ BAŞARISIZ: Kullanıcı bulunamadı\n\n";
        exit(1);
    }

    echo "→ Kullanıcı bulundu\n";

    // Şifre kontrolü
    if (!password_verify($testPassword, $user['password'])) {
        echo "✗ BAŞARISIZ: Şifre yanlış\n";
        echo "  Beklenen: Admin123!\n";
        echo "  Hash: {$user['password']}\n\n";
        exit(1);
    }

    echo "→ Şifre doğru\n";

    // Aktif mi?
    if (!$user['is_active']) {
        echo "✗ BAŞARISIZ: Hesap aktif değil\n\n";
        exit(1);
    }

    echo "→ Hesap aktif\n";

    echo "\n✓✓✓ GİRİŞ BAŞARILI! ✓✓✓\n\n";

    // 5. Tüm admin kullanıcılarını listele
    echo "5. Tüm Admin Kullanıcıları:\n";
    echo "-------------------------------------------\n";

    $stmt = $pdo->query("SELECT id, email, full_name, is_active, created_at FROM users WHERE user_type = 'admin' ORDER BY id");
    $admins = $stmt->fetchAll();

    foreach ($admins as $adm) {
        echo "\nID: {$adm['id']}\n";
        echo "Email: {$adm['email']}\n";
        echo "Ad: {$adm['full_name']}\n";
        echo "Durum: " . ($adm['is_active'] ? 'Aktif' : 'Pasif') . "\n";
        echo "Oluşturulma: {$adm['created_at']}\n";
    }

    echo "\n====================================\n";
    echo "TEST TAMAMLANDI!\n";
    echo "====================================\n\n";

    echo "GİRİŞ BİLGİLERİ:\n";
    echo "URL: http://localhost:8000/login.php\n";
    echo "Email: admin@diyetlenio.com\n";
    echo "Şifre: Admin123!\n\n";

} catch (PDOException $e) {
    echo "✗ Veritabanı Hatası: " . $e->getMessage() . "\n\n";

    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "ÇÖZÜM:\n";
        echo "1. .env dosyasındaki veritabanı bilgilerini kontrol edin\n";
        echo "2. MySQL kullanıcısını oluşturun:\n";
        echo "   sudo mysql -e \"CREATE USER 'diyetlenio_user'@'localhost' IDENTIFIED BY 'diyetlenio2025';\"\n";
        echo "   sudo mysql -e \"GRANT ALL PRIVILEGES ON diyetlenio.* TO 'diyetlenio_user'@'localhost';\"\n";
        echo "   sudo mysql -e \"FLUSH PRIVILEGES;\"\n\n";
    }

    exit(1);
} catch (Exception $e) {
    echo "✗ Hata: " . $e->getMessage() . "\n\n";
    exit(1);
}
