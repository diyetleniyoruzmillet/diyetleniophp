<?php
/**
 * Diyetlenio - Bildirimler (Client)
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Bildirimleri çek
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Okunmamış bildirimleri işaretle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    // CSRF protection
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirect('/client/notifications.php');
    }

    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    redirect('/client/notifications.php');
}

require __DIR__ . '/../../views/partials/client-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require __DIR__ . '/../../views/partials/client-sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Bildirimler</h1>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button type="submit" name="mark_read" class="btn btn-sm btn-success">
                        <i class="fas fa-check-double me-1"></i>Tümünü Okundu İşaretle
                    </button>
                </form>
            </div>

            <?php if (count($notifications) > 0): ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item list-group-item-action <?= $notif['is_read'] ? '' : 'list-group-item-primary' ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= clean($notif['title']) ?></h5>
                                <small><?= timeAgo($notif['created_at']) ?></small>
                            </div>
                            <p class="mb-1"><?= clean($notif['message']) ?></p>
                            <?php if ($notif['link']): ?>
                                <a href="<?= clean($notif['link']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    Görüntüle <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Henüz bildiriminiz yok.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
