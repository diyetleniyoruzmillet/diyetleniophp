<?php
/**
 * Diyetlenio - Admin Ödeme Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Ödemeleri çek
$stmt = $conn->query("
    SELECT p.*,
           c.full_name as client_name,
           d.full_name as dietitian_name
    FROM payments p
    INNER JOIN users c ON p.client_id = c.id
    INNER JOIN users d ON p.dietitian_id = d.id
    ORDER BY p.payment_date DESC
    LIMIT 100
");
$payments = $stmt->fetchAll();

$pageTitle = 'Ödeme Yönetimi';
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
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link" href="/admin/users.php">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </a>
                        <a class="nav-link active" href="/admin/payments.php">
                            <i class="fas fa-money-bill-wave me-2"></i>Ödemeler
                        </a>
                        <a class="nav-link" href="/admin/reviews.php">
                            <i class="fas fa-star me-2"></i>Değerlendirmeler
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
                    <h2 class="mb-4">Ödeme Yönetimi</h2>

                    <div class="card">
                        <div class="card-body">
                            <?php if (count($payments) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-money-bill-wave fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Henüz ödeme yok</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tarih</th>
                                                <th>Danışan</th>
                                                <th>Diyetisyen</th>
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
                                                    <td><?= clean($payment['dietitian_name']) ?></td>
                                                    <td><strong><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                                    <td>
                                                        <?php
                                                        $badges = [
                                                            'completed' => 'success',
                                                            'pending' => 'warning',
                                                            'failed' => 'danger'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $badges[$payment['status']] ?>">
                                                            <?= ucfirst($payment['status']) ?>
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
