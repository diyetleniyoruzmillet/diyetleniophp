<?php
/**
 * Admin User Fix Tool
 * WARNING: Delete this file after use!
 */

// Security token
$token = $_GET['token'] ?? '';
$expectedToken = md5('fix-admin-2025-' . date('Y-m-d'));

if ($token !== $expectedToken) {
    http_response_code(403);
    die('Invalid security token. Use: ?token=' . $expectedToken . '<br><br>Token: ' . $expectedToken);
}

require_once __DIR__ . '/../includes/bootstrap.php';

$message = '';
$adminUsers = [];

try {
    $conn = $db->getConnection();

    // Get current admin users
    $stmt = $conn->query("SELECT id, email, full_name, user_type, is_active FROM users WHERE user_type = 'admin' OR email LIKE '%admin%'");
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_admin'])) {

        // Delete old admin users
        $conn->exec("DELETE FROM users WHERE email = 'admin@diyetlenio.com'");

        // Create new admin user
        // Password: Admin123!
        $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            'admin@diyetlenio.com',
            $passwordHash,
            'Sistem Yöneticisi',
            '05001234567',
            'admin',
            1,
            1
        ]);

        $message = '<div class="alert alert-success">✅ Admin kullanıcısı başarıyla oluşturuldu!<br><br><strong>Giriş Bilgileri:</strong><br>Email: admin@diyetlenio.com<br>Şifre: Admin123!</div>';

        // Refresh admin users list
        $stmt = $conn->query("SELECT id, email, full_name, user_type, is_active FROM users WHERE user_type = 'admin' OR email LIKE '%admin%'");
        $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Test password verification
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_password'])) {
        $testEmail = $_POST['test_email'] ?? '';
        $testPassword = $_POST['test_password'] ?? '';

        $stmt = $conn->prepare("SELECT id, email, password, user_type FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $verified = password_verify($testPassword, $user['password']);
            if ($verified) {
                $message = '<div class="alert alert-success">✅ Şifre DOĞRU! Giriş yapabilirsiniz.</div>';
            } else {
                $message = '<div class="alert alert-danger">❌ Şifre YANLIŞ!<br>Hash: ' . htmlspecialchars(substr($user['password'], 0, 50)) . '...</div>';
            }
        } else {
            $message = '<div class="alert alert-warning">⚠️ Bu email ile kullanıcı bulunamadı.</div>';
        }
    }

} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">❌ Veritabanı hatası: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Fix Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 40px 0; }
        .tool-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        .security-warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">

        <div class="tool-card">
            <h1 class="mb-4">
                <i class="fas fa-user-shield text-danger"></i>
                Admin User Fix Tool
            </h1>

            <div class="security-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>UYARI:</strong> Bu dosyayı kullandıktan sonra mutlaka silin!
                <code>rm public/fix-admin.php</code>
            </div>

            <?php if ($message): ?>
                <?= $message ?>
            <?php endif; ?>

            <!-- Current Admin Users -->
            <div class="mb-4">
                <h3><i class="fas fa-users text-primary"></i> Mevcut Admin Kullanıcıları</h3>

                <?php if (empty($adminUsers)): ?>
                    <div class="alert alert-warning">
                        ⚠️ Admin kullanıcısı bulunamadı!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Ad Soyad</th>
                                    <th>Tip</th>
                                    <th>Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminUsers as $admin): ?>
                                    <tr>
                                        <td><?= $admin['id'] ?></td>
                                        <td><?= htmlspecialchars($admin['email']) ?></td>
                                        <td><?= htmlspecialchars($admin['full_name']) ?></td>
                                        <td><span class="badge bg-danger"><?= $admin['user_type'] ?></span></td>
                                        <td>
                                            <?php if ($admin['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <hr>

            <!-- Fix Admin User -->
            <div class="mb-4">
                <h3><i class="fas fa-wrench text-success"></i> Admin Kullanıcısını Düzelt</h3>
                <p class="text-muted">
                    Bu işlem:
                </p>
                <ul>
                    <li>Eski admin@diyetlenio.com kullanıcısını siler</li>
                    <li>Yeni admin kullanıcısı oluşturur</li>
                    <li>Email: <strong>admin@diyetlenio.com</strong></li>
                    <li>Şifre: <strong>Admin123!</strong></li>
                </ul>

                <form method="POST">
                    <button type="submit" name="fix_admin" class="btn btn-success btn-lg">
                        <i class="fas fa-user-plus"></i>
                        Admin Kullanıcısını Oluştur/Düzelt
                    </button>
                </form>
            </div>

            <hr>

            <!-- Test Password -->
            <div class="mb-4">
                <h3><i class="fas fa-key text-warning"></i> Şifre Testi</h3>
                <p class="text-muted">Giriş bilgilerini test et:</p>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email:</label>
                        <input type="email" name="test_email" class="form-control"
                               value="admin@diyetlenio.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Şifre:</label>
                        <input type="text" name="test_password" class="form-control"
                               value="Admin123!" required>
                    </div>

                    <button type="submit" name="test_password" class="btn btn-warning">
                        <i class="fas fa-vial"></i>
                        Şifreyi Test Et
                    </button>
                </form>
            </div>

            <hr>

            <!-- Quick Links -->
            <div>
                <h3><i class="fas fa-link text-info"></i> Hızlı Linkler</h3>
                <div class="d-grid gap-2">
                    <a href="/login.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-sign-in-alt"></i>
                        Giriş Sayfası
                    </a>
                    <a href="/admin/dashboard.php" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-tachometer-alt"></i>
                        Admin Dashboard
                    </a>
                </div>
            </div>

            <div class="mt-4 text-center text-muted small">
                <i class="fas fa-shield-alt"></i>
                Security Token: <?= $expectedToken ?>
            </div>
        </div>
    </div>
</body>
</html>
