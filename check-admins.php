<?php
/**
 * Admin bilgilerini kontrol et
 */

require_once __DIR__ . '/includes/bootstrap.php';

try {
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT id, email, full_name, user_type, is_active, created_at, updated_at
        FROM users
        WHERE user_type = 'admin'
        ORDER BY id ASC
    ");

    $stmt->execute();
    $admins = $stmt->fetchAll();

    echo "=== ADMIN KULLANICILAR ===\n\n";
    echo "Toplam admin sayısı: " . count($admins) . "\n\n";

    foreach ($admins as $admin) {
        echo "ID: {$admin['id']}\n";
        echo "Email: {$admin['email']}\n";
        echo "Ad Soyad: {$admin['full_name']}\n";
        echo "Aktif: " . ($admin['is_active'] ? 'Evet' : 'Hayır') . "\n";
        echo "Oluşturma: {$admin['created_at']}\n";
        echo "Güncelleme: {$admin['updated_at']}\n";
        echo "---\n\n";
    }

    // Şifre bilgisi için (hash'li)
    $stmt = $conn->prepare("
        SELECT id, email, password
        FROM users
        WHERE user_type = 'admin'
        LIMIT 1
    ");
    $stmt->execute();
    $firstAdmin = $stmt->fetch();

    if ($firstAdmin) {
        echo "NOT: Şifreler hash'li olarak saklanır.\n";
        echo "Örnek password hash: " . substr($firstAdmin['password'], 0, 30) . "...\n";
    }

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
