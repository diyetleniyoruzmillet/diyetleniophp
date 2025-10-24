<?php
/**
 * Admin şifresini güncelle - Railway database
 */

require_once __DIR__ . '/includes/bootstrap.php';

$newPassword = 'Admin123!';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $conn = $db->getConnection();

    // Önce admin kullanıcıları listele
    $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE user_type = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();

    echo "=== MEVCUT ADMIN KULLANICILAR ===\n\n";

    if (empty($admins)) {
        echo "❌ Hiç admin kullanıcı bulunamadı!\n\n";
        echo "Yeni admin oluşturuluyor...\n";

        // Yeni admin oluştur
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, full_name, user_type, is_active, created_at, updated_at)
            VALUES (?, ?, ?, 'admin', 1, NOW(), NOW())
        ");
        $stmt->execute([
            'admin@diyetlenio.com',
            $hashedPassword,
            'Admin User'
        ]);

        echo "✅ Yeni admin oluşturuldu!\n";
        echo "Email: admin@diyetlenio.com\n";
        echo "Şifre: {$newPassword}\n";

    } else {
        // Tüm adminlerin şifresini güncelle
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']}\n";
            echo "Email: {$admin['email']}\n";
            echo "Ad Soyad: {$admin['full_name']}\n";
            echo "---\n";
        }

        echo "\nŞifreler güncelleniyor...\n";

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?, updated_at = NOW()
            WHERE user_type = 'admin'
        ");
        $stmt->execute([$hashedPassword]);

        $affectedRows = $stmt->rowCount();

        echo "\n✅ {$affectedRows} admin kullanıcının şifresi güncellendi!\n";
        echo "Yeni şifre: {$newPassword}\n\n";

        echo "Güncellenmiş admin bilgileri:\n";
        foreach ($admins as $admin) {
            echo "- {$admin['email']} → Şifre: {$newPassword}\n";
        }
    }

} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
}
