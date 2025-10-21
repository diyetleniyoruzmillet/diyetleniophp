<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    redirect('/login.php');
}

$user = $auth->user();

// Ödemeleri çek
$stmt = $db->prepare("
    SELECT p.*, a.appointment_date, a.appointment_time, u.first_name, u.last_name
    FROM payments p
    LEFT JOIN appointments a ON p.appointment_id = a.id
    LEFT JOIN users u ON a.dietitian_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user->getId()]);
$payments = $stmt->fetchAll();

include __DIR__ . '/../../includes/client_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Ödeme Geçmişim</h1>
    </div>

    <?php if (getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= clean(getFlash('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Henüz ödeme kaydınız bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Randevu</th>
                                <th>Diyetisyen</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></td>
                                    <td>
                                        <?php if ($payment['appointment_date']): ?>
                                            <?= date('d.m.Y', strtotime($payment['appointment_date'])) ?>
                                            <?= date('H:i', strtotime($payment['appointment_time'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= clean($payment['first_name'] . ' ' . $payment['last_name']) ?></td>
                                    <td><strong><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            'refunded' => 'info'
                                        ];
                                        $statusLabels = [
                                            'completed' => 'Tamamlandı',
                                            'pending' => 'Bekliyor',
                                            'failed' => 'Başarısız',
                                            'refunded' => 'İade Edildi'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $statusColors[$payment['status']] ?? 'secondary' ?>">
                                            <?= $statusLabels[$payment['status']] ?? $payment['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] === 'completed'): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="alert('Fatura indirme özelliği yakında eklenecek')">
                                                <i class="fas fa-file-invoice"></i> Fatura
                                            </button>
                                        <?php endif; ?>
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

<?php include __DIR__ . '/../../includes/client_footer.php'; ?>
