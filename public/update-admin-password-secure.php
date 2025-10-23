<?php
/**
 * Admin Şifre Güncelleme - Güvenli Endpoint
 *
 * UYARI: Bu dosyayı kullandıktan sonra SİL!
 */

// Basit güvenlik: Sadece bir kere çalışsın
$lockFile = __DIR__ . '/../storage/admin-password-updated.lock';

if (file_exists($lockFile)) {
    die('❌ Bu işlem zaten gerçekleştirildi. Güvenlik için dosya kilitlendi.');
}

require_once __DIR__ . '/../includes/bootstrap.php';

$newPassword = 'Admin123!';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $conn = $db->getConnection();

    // Önce admin kullanıcıları kontrol et
    $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE user_type = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();

    echo "<h2>🔐 Admin Şifre Güncelleme</h2>";
    echo "<hr>";

    if (empty($admins)) {
        echo "<h3>❌ Hiç admin kullanıcı bulunamadı!</h3>";
        echo "<p>Yeni admin oluşturuluyor...</p>";

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

        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>✅ Yeni admin oluşturuldu!</h3>";
        echo "<p><strong>Email:</strong> admin@diyetlenio.com</p>";
        echo "<p><strong>Şifre:</strong> {$newPassword}</p>";
        echo "</div>";

    } else {
        echo "<h3>📋 Mevcut Admin Kullanıcılar:</h3>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin['id']} - {$admin['email']} ({$admin['full_name']})</li>";
        }
        echo "</ul>";

        echo "<p>Şifreler güncelleniyor...</p>";

        // Tüm adminlerin şifresini güncelle
        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?, updated_at = NOW()
            WHERE user_type = 'admin'
        ");
        $stmt->execute([$hashedPassword]);

        $affectedRows = $stmt->rowCount();

        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>✅ {$affectedRows} admin kullanıcının şifresi güncellendi!</h3>";
        echo "<p><strong>Yeni Şifre:</strong> {$newPassword}</p>";
        echo "<h4>Güncellenmiş Admin Bilgileri:</h4>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li><strong>{$admin['email']}</strong> → Şifre: <code>{$newPassword}</code></li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    // Lock dosyası oluştur
    file_put_contents($lockFile, date('Y-m-d H:i:s'));

    echo "<hr>";
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>⚠️ ÖNEMLİ GÜVENLİK UYARISI:</h4>";
    echo "<p>Şifre başarıyla güncellendi. Güvenlik için:</p>";
    echo "<ol>";
    echo "<li>Hemen <a href='/login.php'>giriş yapın</a> ve şifrenizi değiştirin</li>";
    echo "<li>Bu dosyayı <strong>SİLİN</strong>: <code>public/update-admin-password-secure.php</code></li>";
    echo "<li>Lock dosyasını <strong>SİLİN</strong>: <code>storage/admin-password-updated.lock</code></li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ HATA</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='/admin/dashboard.php'>Admin Dashboard'a Git</a></p>";
?>
