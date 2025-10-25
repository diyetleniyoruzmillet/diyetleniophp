<?php
/**
 * Diyetlenio - Danışan Randevularım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'scheduled';

// Randevuları çek
$whereClause = "WHERE a.client_id = ?";
$params = [$userId];

if ($status === 'scheduled') {
    $whereClause .= " AND a.status = 'scheduled' AND a.appointment_date >= NOW()";
} elseif ($status === 'completed') {
    $whereClause .= " AND a.status = 'completed'";
} elseif ($status === 'cancelled') {
    $whereClause .= " AND a.status = 'cancelled'";
}

$stmt = $conn->prepare("
    SELECT a.*, u.full_name as dietitian_name, dp.title as dietitian_title,
           dp.specialization, dp.consultation_fee
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    {$whereClause}
    ORDER BY a.appointment_date DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Randevularım';
include __DIR__ . '/../../includes/client_header.php';
?>

<style>
    .appointment-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid #56ab2f;
        transition: all 0.3s;
    }
    .appointment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Randevularım</h2>
    <div class="btn-group">
        <a href="?status=scheduled" class="btn <?= $status === 'scheduled' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="fas fa-clock me-2"></i>Yaklaşan
        </a>
        <a href="?status=completed" class="btn <?= $status === 'completed' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="fas fa-check me-2"></i>Tamamlanan
        </a>
        <a href="?status=cancelled" class="btn <?= $status === 'cancelled' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="fas fa-times me-2"></i>İptal
        </a>
    </div>
</div>

<?php if (count($appointments) === 0): ?>
    <div class="text-center py-5">
        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">Randevu bulunamadı</h4>
        <a href="/client/dietitians.php" class="btn btn-success mt-3">
            <i class="fas fa-search me-2"></i>Diyetisyen Bul
        </a>
    </div>
<?php else: ?>
    <?php foreach ($appointments as $apt): ?>
        <div class="appointment-card">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <div class="text-center">
                        <div class="h2 mb-0 text-success"><?= date('d', strtotime($apt['appointment_date'])) ?></div>
                        <div class="text-muted"><?= date('F', strtotime($apt['appointment_date'])) ?></div>
                        <div class="text-muted"><?= date('Y', strtotime($apt['appointment_date'])) ?></div>
                        <div class="text-primary fw-bold mt-2"><?= date('H:i', strtotime($apt['appointment_date'])) ?></div>
                    </div>
                </div>
                <div class="col-md-5">
                    <h5 class="mb-1"><?= clean($apt['dietitian_name']) ?></h5>
                    <p class="text-muted mb-1"><?= clean($apt['dietitian_title']) ?></p>
                    <span class="badge bg-light text-dark">
                        <?= clean($apt['specialization']) ?>
                    </span>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <?php if ($apt['is_online']): ?>
                            <span class="badge bg-info">
                                <i class="fas fa-video me-1"></i>Online Görüşme
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                <i class="fas fa-clinic-medical me-1"></i>Yüz Yüze
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php
                    $badges = [
                        'scheduled' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $labels = [
                        'scheduled' => 'Planlandı',
                        'completed' => 'Tamamlandı',
                        'cancelled' => 'İptal Edildi'
                    ];
                    ?>
                    <span class="badge bg-<?= $badges[$apt['status']] ?>">
                        <?= $labels[$apt['status']] ?>
                    </span>
                    <div class="mt-2">
                        <small class="text-muted">
                            Ücret: <strong><?= number_format($apt['consultation_fee'], 0) ?> ₺</strong>
                        </small>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <?php if ($apt['status'] === 'scheduled' && $apt['is_online']): ?>
                        <a href="/video-call.php?appointment=<?= $apt['id'] ?>" class="btn btn-success btn-sm w-100 mb-2">
                            <i class="fas fa-video me-1"></i>Katıl
                        </a>
                    <?php endif; ?>
                    <?php if ($apt['status'] === 'completed'): ?>
                        <a href="/client/review.php?appointment=<?= $apt['id'] ?>" class="btn btn-outline-warning btn-sm w-100">
                            <i class="fas fa-star me-1"></i>Değerlendir
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($apt['notes']): ?>
                <div class="mt-3 pt-3 border-top">
                    <strong>Notlar:</strong> <?= nl2br(clean($apt['notes'])) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
