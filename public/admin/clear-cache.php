<?php
/**
 * Clear PHP OpCache
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    die('Bu sayfaya erişim yetkiniz yok.');
}

$success = false;
$message = '';

if (function_exists('opcache_reset')) {
    $success = opcache_reset();
    $message = $success ? '✅ OpCache başarıyla temizlendi!' : '❌ OpCache temizlenemedi.';
} else {
    $message = '⚠️ OpCache yüklü değil veya devre dışı.';
}

// Check opcache status
$status = function_exists('opcache_get_status') ? opcache_get_status() : null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cache Clear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h2>🔄 Clear Cache</h2>

        <div class="alert alert-<?= $success ? 'success' : 'warning' ?> mt-4">
            <?= $message ?>
        </div>

        <?php if ($status): ?>
            <div class="card mt-4">
                <div class="card-body">
                    <h5>OpCache Status</h5>
                    <ul>
                        <li><strong>Enabled:</strong> <?= $status['opcache_enabled'] ? 'Yes' : 'No' ?></li>
                        <li><strong>Cache Full:</strong> <?= $status['cache_full'] ? 'Yes' : 'No' ?></li>
                        <li><strong>Cached Scripts:</strong> <?= $status['opcache_statistics']['num_cached_scripts'] ?? 'N/A' ?></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="/admin/users.php" class="btn btn-primary">Go to Users</a>
            <a href="/admin/fix-deleted-users.php" class="btn btn-warning">Fix Deleted Users</a>
            <a href="?refresh=1" class="btn btn-secondary">Clear Again</a>
        </div>
    </div>
</body>
</html>
