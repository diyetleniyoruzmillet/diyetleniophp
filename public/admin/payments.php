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
try {
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
} catch (PDOException $e) {
    // payments tablosu yoksa boş array
    $payments = [];
    $tableNotExists = true;
}

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
    <?php include __DIR__ . '/../../includes/admin-styles.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">Ödeme Yönetimi</h2>

                    <?php if (isset($tableNotExists)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Uyarı:</strong> Payments tablosu henüz oluşturulmamış. Bu özellik yakında aktif olacak.
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <?php if (count($payments) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-money-bill-wave fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Henüz ödeme yok</h4>
                                    <p class="text-muted">Ödemeler buradan yönetilecek</p>
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
