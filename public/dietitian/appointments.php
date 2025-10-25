<?php
/**
 * Diyetlenio - Diyetisyen Randevularım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'scheduled';
$clientId = $_GET['client_id'] ?? null;

// Randevuları çek
$whereClause = "WHERE a.dietitian_id = ?";
$params = [$userId];

if ($status === 'scheduled') {
    $whereClause .= " AND a.status = 'scheduled' AND a.appointment_date >= NOW()";
} elseif ($status === 'completed') {
    $whereClause .= " AND a.status = 'completed'";
} elseif ($status === 'cancelled') {
    $whereClause .= " AND a.status = 'cancelled'";
}

if ($clientId) {
    $whereClause .= " AND a.client_id = ?";
    $params[] = $clientId;
}

$stmt = $conn->prepare("
    SELECT a.*, u.full_name as client_name, u.email as client_email,
           u.phone as client_phone
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    {$whereClause}
    ORDER BY a.appointment_date DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Randevularım';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .appointment-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid #f093fb;
        transition: transform 0.3s;
    }
    .appointment-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Randevularım</h2>
    <div class="btn-group">
        <a href="?status=scheduled" class="btn btn-sm <?= $status === 'scheduled' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Yaklaşan
        </a>
        <a href="?status=completed" class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Tamamlanan
        </a>
        <a href="?status=cancelled" class="btn btn-sm <?= $status === 'cancelled' ? 'btn-primary' : 'btn-outline-primary' ?>">
            İptal
        </a>
    </div>
</div>

<?php if (count($appointments) === 0): ?>
    <div class="text-center py-5">
        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">Randevu bulunamadı</h4>
    </div>
<?php else: ?>
    <?php foreach ($appointments as $apt): ?>
        <div class="appointment-card">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="h3 mb-0 text-primary"><?= date('d', strtotime($apt['appointment_date'])) ?></div>
                    <div class="text-muted"><?= date('F Y', strtotime($apt['appointment_date'])) ?></div>
                    <div class="fw-bold mt-2"><?= date('H:i', strtotime($apt['appointment_date'])) ?></div>
                </div>
                <div class="col-md-5">
                    <h5 class="mb-1"><?= clean($apt['client_name']) ?></h5>
                    <p class="text-muted mb-0">
                        <i class="fas fa-envelope me-2"></i><?= clean($apt['client_email']) ?><br>
                        <i class="fas fa-phone me-2"></i><?= clean($apt['client_phone']) ?>
                    </p>
                </div>
                <div class="col-md-3">
                    <?php
                    $badges = [
                        'scheduled' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger'
                    ];
                    ?>
                    <span class="badge bg-<?= $badges[$apt['status']] ?? 'secondary' ?>">
                        <?= ucfirst($apt['status']) ?>
                    </span>
                    <div class="mt-2 small text-muted">
                        <?= $apt['duration'] ?? 45 ?> dakika
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <a href="/dietitian/appointment-detail.php?id=<?= $apt['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Detay
                    </a>
                </div>
            </div>
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
