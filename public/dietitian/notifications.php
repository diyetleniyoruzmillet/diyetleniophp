<?php
/**
 * Diyetlenio - Bildirimler (Dietitian)
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
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
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    redirect('/dietitian/notifications.php');
}

// Tek bir bildirimi sil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notificationId = (int)$_POST['notification_id'];
    $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([$notificationId, $userId]);
    redirect('/dietitian/notifications.php');
}

require __DIR__ . '/../../views/partials/dietitian-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require __DIR__ . '/../../views/partials/dietitian-sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-bell me-2"></i>Bildirimler
                </h1>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_read" class="btn btn-sm btn-success">
                        <i class="fas fa-check-double me-1"></i>Tümünü Okundu İşaretle
                    </button>
                </form>
            </div>

            <?php if (count($notifications) > 0): ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item list-group-item-action <?= $notif['is_read'] ? '' : 'list-group-item-info' ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="badge bg-primary me-2">Yeni</span>
                                            <?php endif; ?>
                                            <?= clean($notif['title']) ?>
                                        </h5>
                                        <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                                    </div>
                                    <p class="mb-1"><?= clean($notif['message']) ?></p>
                                    <?php if ($notif['link']): ?>
                                        <a href="<?= clean($notif['link']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-eye me-1"></i>Görüntüle
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" class="ms-3">
                                    <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Bu bildirimi silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <p class="text-muted fs-5">Henüz bildiriminiz yok.</p>
                    <p class="text-muted">Yeni randevular, mesajlar ve sistem bildirimleri burada görünecektir.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
