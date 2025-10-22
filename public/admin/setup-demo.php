<?php
/**
 * Demo Data Setup - Web Interface
 * WARNING: Delete this file after use!
 */

// Security token
$token = $_GET['token'] ?? '';
$expectedToken = md5('setup-demo-2025-' . date('Y-m-d'));

if ($token !== $expectedToken) {
    http_response_code(403);
    die('Invalid security token. Use: ?token=' . $expectedToken . '<br>Token: ' . $expectedToken);
}

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin only
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    die('Access denied. Admin only.');
}

$conn = $db->getConnection();
$results = [];
$setupRun = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    $setupRun = true;

    // Include the setup scripts
    ob_start();
    try {
        include __DIR__ . '/../../scripts/add-demo-dietitians.php';
        $output1 = ob_get_clean();

        ob_start();
        include __DIR__ . '/../../scripts/add-demo-content.php';
        $output2 = ob_get_clean();

        $results['success'] = true;
        $results['output'] = $output1 . "\n\n" . $output2;
    } catch (Exception $e) {
        ob_end_clean();
        $results['success'] = false;
        $results['error'] = $e->getMessage();
    }
}

// Check current state
$dietitiansCount = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'dietitian'")->fetchColumn();
$articlesCount = $conn->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$recipesCount = $conn->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Data Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 40px 0; }
        .setup-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        .stat-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .output-box { background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 14px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <div class="setup-card">
            <h1 class="mb-4">
                <i class="fas fa-database text-primary"></i>
                Demo Data Setup
            </h1>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>UYARI:</strong> Bu dosyayı kullandıktan sonra mutlaka silin!
                <code>rm public/admin/setup-demo.php</code>
            </div>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-info-circle text-info"></i>
                Mevcut Durum
            </h3>

            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <i class="fas fa-user-md fa-2x text-primary mb-2"></i>
                        <h4><?= $dietitiansCount ?></h4>
                        <small>Diyetisyen</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <i class="fas fa-newspaper fa-2x text-success mb-2"></i>
                        <h4><?= $articlesCount ?></h4>
                        <small>Makale</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <i class="fas fa-utensils fa-2x text-warning mb-2"></i>
                        <h4><?= $recipesCount ?></h4>
                        <small>Tarif</small>
                    </div>
                </div>
            </div>

            <?php if ($setupRun && isset($results['output'])): ?>
                <hr>
                <h3 class="mt-4 mb-3">
                    <i class="fas fa-terminal text-success"></i>
                    Kurulum Çıktısı
                </h3>
                <div class="output-box"><?= htmlspecialchars($results['output']) ?></div>
            <?php elseif ($setupRun && isset($results['error'])): ?>
                <hr>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Hata:</strong> <?= htmlspecialchars($results['error']) ?>
                </div>
            <?php endif; ?>

            <hr>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-play-circle text-success"></i>
                Demo Verileri Ekle
            </h3>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Eklenecekler:</strong>
                <ul class="mb-0 mt-2">
                    <li>6 Demo Diyetisyen (farklı uzmanlık alanları)</li>
                    <li>5 Demo Makale (blog yazıları)</li>
                    <li>6 Demo Tarif (sağlıklı yemek tarifleri)</li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="run_setup" class="btn btn-success btn-lg">
                    <i class="fas fa-rocket"></i>
                    Demo Verileri Ekle
                </button>
            </form>

            <hr>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-key text-warning"></i>
                Demo Giriş Bilgileri
            </h3>

            <div class="table-responsive">
                <table class="table table-sm">
                    <tr>
                        <th>Diyetisyen:</th>
                        <td>
                            <code>ayse.yilmaz@diyetlenio.com</code> / <code>Demo123!</code>
                            <span class="badge bg-primary">Spor Beslenmesi</span>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <code>mehmet.demir@diyetlenio.com</code> / <code>Demo123!</code>
                            <span class="badge bg-info">Klinik Beslenme</span>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <code>zeynep.kaya@diyetlenio.com</code> / <code>Demo123!</code>
                            <span class="badge bg-success">Çocuk Beslenmesi</span>
                        </td>
                    </tr>
                </table>
            </div>

            <hr>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-link text-info"></i>
                Test Linkleri
            </h3>

            <div class="list-group">
                <a href="/dietitians.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-user-md"></i> Diyetisyenler Sayfası
                </a>
                <a href="/blog.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-newspaper"></i> Blog Sayfası
                </a>
                <a href="/recipes.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-utensils"></i> Tarifler Sayfası
                </a>
                <a href="/admin/dashboard.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </a>
            </div>

            <div class="mt-4 text-center text-muted small">
                <i class="fas fa-shield-alt"></i>
                Security Token: <?= $expectedToken ?>
            </div>
        </div>
    </div>
</body>
</html>
