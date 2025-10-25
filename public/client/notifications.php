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
    // CSRF koruması
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form isteği. Lütfen tekrar deneyin.');
        redirect('/client/notifications.php');
    }

    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    redirect('/client/notifications.php');
}

$pageTitle = 'Bildirimler';
include __DIR__ . '/../../includes/client_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bildirimler</h2>
    <form method="POST" class="d-inline">
        <?= csrfField() ?>
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

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
