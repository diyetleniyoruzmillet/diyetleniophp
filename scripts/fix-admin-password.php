<?php
/**
 * Admin şifresini düzelt
 * Kullanım: php scripts/fix-admin-password.php
 */

chdir(dirname(__DIR__));
require_once 'config/constants.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();

    // Yeni şifre hash'i oluştur
    $newPassword = 'Admin123!';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    echo "Şifre hash'i oluşturuluyor...\n";
    echo "Şifre: {$newPassword}\n";
    echo "Hash: {$hashedPassword}\n\n";

    // Admin kullanıcısının şifresini güncelle
    $result = $db->update('users',
        ['password' => $hashedPassword],
        ['email' => 'admin@diyetlenio.com']
    );

    if ($result) {
        echo "✓ Admin şifresi başarıyla güncellendi!\n\n";
        echo "Giriş Bilgileri:\n";
        echo "================\n";
        echo "Email: admin@diyetlenio.com\n";
        echo "Şifre: Admin123!\n";
        echo "================\n\n";

        // Şifrenin doğru çalıştığını test et
        $admin = $db->query("SELECT password FROM users WHERE email = 'admin@diyetlenio.com'")->fetch();
        $testResult = password_verify($newPassword, $admin['password']);

        if ($testResult) {
            echo "✓ Şifre doğrulaması başarılı!\n";
            echo "Şimdi http://localhost:8000/login.php adresinden giriş yapabilirsiniz.\n";
        } else {
            echo "✗ Şifre doğrulaması başarısız!\n";
        }
    } else {
        echo "✗ Şifre güncellenemedi!\n";
    }

} catch (Exception $e) {
    echo "✗ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
