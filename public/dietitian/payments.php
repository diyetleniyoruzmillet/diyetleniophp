<?php
/**
 * Diyetlenio - Diyetisyen Ödeme Takibi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'all';
$month = $_GET['month'] ?? date('Y-m');

// Ödemeleri çek
$whereClause = "WHERE p.dietitian_id = ?";
$params = [$userId];

if ($status !== 'all') {
    $whereClause .= " AND p.status = ?";
    $params[] = $status;
}

if ($month) {
    $whereClause .= " AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
    $params[] = $month;
}

$stmt = $conn->prepare("
    SELECT p.*, u.full_name as client_name, a.appointment_date
    FROM payments p
    INNER JOIN appointments a ON p.appointment_id = a.id
    INNER JOIN users u ON p.client_id = u.id
    {$whereClause}
    ORDER BY p.payment_date DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// İstatistikler
$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_count,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
    FROM payments
    WHERE dietitian_id = ?
    " . ($month ? "AND DATE_FORMAT(payment_date, '%Y-%m') = ?" : "")
);
$statsParams = [$userId];
if ($month) $statsParams[] = $month;
$stmt->execute($statsParams);
$stats = $stmt->fetch();

$pageTitle = 'Ödeme Takibi';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
</style>

<h2 class="mb-4">Ödeme Takibi</h2>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <h6 class="text-muted">Toplam Ödeme</h6>
            <h4><?= number_format($stats['total_amount'] ?? 0, 2) ?> ₺</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h6 class="text-muted">Onaylanan</h6>
            <h4 class="text-success"><?= number_format($stats['approved_amount'] ?? 0, 2) ?> ₺</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h6 class="text-muted">Bekleyen</h6>
            <h4 class="text-warning"><?= number_format($stats['pending_amount'] ?? 0, 2) ?> ₺</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h6 class="text-muted">Toplam İşlem</h6>
            <h4><?= $stats['total_count'] ?? 0 ?></h4>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-0">Ödeme Listesi</h5>
            </div>
            <div class="col-md-6">
                <div class="row g-2">
                    <div class="col-auto">
                        <select class="form-select form-select-sm" onchange="window.location.href='?status='+this.value+'&month=<?= $month ?>'">
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tüm Durumlar</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Onaylanan</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Reddedilen</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="month" class="form-control form-control-sm" value="<?= $month ?>"
                               onchange="window.location.href='?status=<?= $status ?>&month='+this.value">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($payments) === 0): ?>
            <div class="text-center py-4">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <p class="text-muted">Ödeme bulunamadı</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Danışan</th>
                            <th>Randevu</th>
                            <th>Tutar</th>
                            <th>Komisyon</th>
                            <th>Net</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($payment['payment_date'])) ?></td>
                                <td><?= clean($payment['client_name']) ?></td>
                                <td><?= date('d.m.Y', strtotime($payment['appointment_date'])) ?></td>
                                <td><?= number_format($payment['amount'], 2) ?> ₺</td>
                                <td><?= number_format($payment['commission_amount'], 2) ?> ₺</td>
                                <td><strong><?= number_format($payment['amount'] - $payment['commission_amount'], 2) ?> ₺</strong></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $labels = [
                                        'pending' => 'Bekliyor',
                                        'approved' => 'Onaylandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $badges[$payment['status']] ?? 'secondary' ?>">
                                        <?= $labels[$payment['status']] ?? $payment['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
