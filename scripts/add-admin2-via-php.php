<?php
/**
 * İkinci Admin Kullanıcısını PHP ile Ekle
 * Kullanım: php scripts/add-admin2-via-php.php
 */

chdir(dirname(__DIR__));
require_once 'config/constants.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();

    echo "İkinci admin kullanıcısı ekleniyor...\n\n";

    // Mevcut admin2 kullanıcısını kontrol et
    $existing = $db->query("SELECT id, email FROM users WHERE email = 'admin2@diyetlenio.com'")->fetch();

    if ($existing) {
        echo "⚠️  admin2@diyetlenio.com zaten mevcut. Şifre güncelleniyor...\n";

        // Şifreyi güncelle
        $hashedPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
        $result = $db->update('users',
            [
                'password' => $hashedPassword,
                'is_active' => 1,
                'is_email_verified' => 1
            ],
            ['email' => 'admin2@diyetlenio.com']
        );

        if ($result) {
            echo "✓ Şifre güncellendi!\n";
        }
    } else {
        // Yeni admin ekle
        $hashedPassword = password_hash('Admin123!', PASSWORD_DEFAULT);

        $adminData = [
            'email' => 'admin2@diyetlenio.com',
            'password' => $hashedPassword,
            'full_name' => 'Admin Kullanıcı 2',
            'phone' => '05009876543',
            'user_type' => 'admin',
            'is_active' => 1,
            'is_email_verified' => 1
        ];

        $result = $db->insert('users', $adminData);

        if ($result) {
            $adminId = $db->lastInsertId();
            echo "✓ İkinci admin kullanıcısı başarıyla eklendi! (ID: {$adminId})\n";
        } else {
            echo "✗ Admin kullanıcısı eklenemedi!\n";
            exit(1);
        }
    }

    // Tüm admin kullanıcılarını listele
    echo "\n";
    echo "================================\n";
    echo "TÜM ADMIN KULLANICILARI:\n";
    echo "================================\n";

    $admins = $db->query("SELECT id, email, full_name, phone, is_active FROM users WHERE user_type = 'admin' ORDER BY id")->fetchAll();

    foreach ($admins as $admin) {
        echo "\nID: {$admin['id']}\n";
        echo "Email: {$admin['email']}\n";
        echo "Ad: {$admin['full_name']}\n";
        echo "Telefon: {$admin['phone']}\n";
        echo "Durum: " . ($admin['is_active'] ? 'Aktif' : 'Pasif') . "\n";
        echo "Şifre: Admin123!\n";
        echo "---\n";
    }

    echo "\n✓ Tamamlandı!\n";
    echo "Giriş URL: http://localhost:8000/login.php\n\n";

} catch (Exception $e) {
    echo "✗ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
