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
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount
    FROM payments
    WHERE dietitian_id = ?
    " . ($month ? "AND DATE_FORMAT(payment_date, '%Y-%m') = ?" : "")
);
$statsParams = [$userId];
if ($month) $statsParams[] = $month;
$stmt->execute($statsParams);
$stats = $stmt->fetch();

$pageTitle = 'Ödeme Takibi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link" href="/dietitian/availability.php">
                            <i class="fas fa-clock me-2"></i>Müsaitlik
                        </a>
                        <a class="nav-link" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
                        </a>
                        <a class="nav-link" href="/dietitian/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link active" href="/dietitian/payments.php">
                            <i class="fas fa-money-bill-wave me-2"></i>Ödemeler
                        </a>
                        <a class="nav-link" href="/dietitian/profile.php">
                            <i class="fas fa-user me-2"></i>Profilim
                        </a>
                        <hr class="text-white-50 my-3">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">Ödeme Takibi</h2>

                    <!-- Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                <h3><?= number_format($stats['completed_amount'] ?? 0, 2) ?> ₺</h3>
                                <p class="text-muted mb-0">Tamamlanan</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h3><?= number_format($stats['pending_amount'] ?? 0, 2) ?> ₺</h3>
                                <p class="text-muted mb-0">Bekleyen</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h3><?= number_format($stats['failed_amount'] ?? 0, 2) ?> ₺</h3>
                                <p class="text-muted mb-0">Başarısız</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-file-invoice-dollar fa-2x text-primary mb-2"></i>
                                <h3><?= number_format($stats['total_count'] ?? 0) ?></h3>
                                <p class="text-muted mb-0">Toplam İşlem</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Durum</label>
                                    <select name="status" class="form-select">
                                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tümü</option>
                                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Başarısız</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ay</label>
                                    <input type="month" name="month" class="form-control" value="<?= $month ?>">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Filtrele
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (count($payments) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Ödeme bulunamadı</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tarih</th>
                                                <th>Danışan</th>
                                                <th>Randevu</th>
                                                <th>Tutar</th>
                                                <th>Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td>#<?= $payment['id'] ?></td>
                                                    <td><?= date('d.m.Y H:i', strtotime($payment['payment_date'])) ?></td>
                                                    <td><?= clean($payment['client_name']) ?></td>
                                                    <td><?= date('d.m.Y', strtotime($payment['appointment_date'])) ?></td>
                                                    <td><strong><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                                    <td>
                                                        <?php
                                                        $badges = [
                                                            'completed' => 'success',
                                                            'pending' => 'warning',
                                                            'failed' => 'danger'
                                                        ];
                                                        $labels = [
                                                            'completed' => 'Tamamlandı',
                                                            'pending' => 'Bekliyor',
                                                            'failed' => 'Başarısız'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $badges[$payment['status']] ?>">
                                                            <?= $labels[$payment['status']] ?>
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
