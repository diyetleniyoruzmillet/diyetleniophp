<?php
/**
 * Admin Kullanıcısı Ekleme Script
 *
 * Bu script veritabanına admin kullanıcısı ekler
 * Kullanım: php scripts/add-admin.php
 */

// Projenin ana dizinine gider
chdir(dirname(__DIR__));

// Gerekli dosyaları yükle
require_once 'config/constants.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();

    // Admin kullanıcısı var mı kontrol et
    $existingAdmin = $db->query("SELECT id, email FROM users WHERE user_type = 'admin' LIMIT 1")
                         ->fetch();

    if ($existingAdmin) {
        echo "✓ Admin kullanıcısı zaten mevcut:\n";
        echo "  Email: {$existingAdmin['email']}\n";
        echo "  ID: {$existingAdmin['id']}\n\n";

        // Şifreyi güncelle
        $hashedPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
        $db->update('users',
            ['password' => $hashedPassword],
            ['id' => $existingAdmin['id']]
        );
        echo "✓ Admin şifresi güncellendi: Admin123!\n";
    } else {
        // Yeni admin kullanıcısı ekle
        $hashedPassword = password_hash('Admin123!', PASSWORD_DEFAULT);

        $adminData = [
            'email' => 'admin@diyetlenio.com',
            'password' => $hashedPassword,
            'full_name' => 'Sistem Yöneticisi',
            'phone' => '05001234567',
            'user_type' => 'admin',
            'is_active' => 1,
            'is_email_verified' => 1
        ];

        $result = $db->insert('users', $adminData);

        if ($result) {
            $adminId = $db->lastInsertId();
            echo "✓ Admin kullanıcısı başarıyla eklendi!\n\n";
            echo "Giriş Bilgileri:\n";
            echo "================\n";
            echo "Email: admin@diyetlenio.com\n";
            echo "Şifre: Admin123!\n";
            echo "ID: {$adminId}\n";
            echo "================\n\n";
            echo "Admin paneline giriş için: http://localhost:8000/login.php\n";
        } else {
            echo "✗ Admin kullanıcısı eklenemedi!\n";
        }
    }

} catch (Exception $e) {
    echo "✗ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
