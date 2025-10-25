<?php
/**
 * Diyetlenio - Dietitian Commission Payments
 * Diyetisyenlerin komisyon ödemelerini yükledikleri sayfa
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$dietitianId = $auth->user()->getId();

// Handle commission payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_commission'])) {
    // CSRF protection
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirect('/dietitian/commission-payments.php');
    }

    try {
        $paymentId = (int)$_POST['payment_id'];

        // Validate file upload
        if (!isset($_FILES['commission_receipt']) || $_FILES['commission_receipt']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lütfen komisyon dekontunu yükleyin.');
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $fileType = $_FILES['commission_receipt']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Sadece JPG, PNG veya PDF dosyası yükleyebilirsiniz.');
        }

        // Validate file size (max 5MB)
        if ($_FILES['commission_receipt']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Dosya boyutu en fazla 5MB olabilir.');
        }

        // Create upload directory if not exists
        $uploadDir = __DIR__ . '/../../storage/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['commission_receipt']['name'], PATHINFO_EXTENSION);
        $fileName = 'commission_' . $dietitianId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['commission_receipt']['tmp_name'], $uploadPath)) {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }

        $receiptPath = 'storage/receipts/' . $fileName;

        // Update payment record
        $stmt = $conn->prepare("
            UPDATE payments
            SET commission_paid = TRUE,
                commission_receipt_path = ?
            WHERE id = ? AND dietitian_id = ?
        ");

        $stmt->execute([$receiptPath, $paymentId, $dietitianId]);

        setFlash('success', 'Komisyon dekontunuz başarıyla yüklendi.');
        redirect('/dietitian/commission-payments.php');

    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

// Get approved payments where commission is unpaid
$stmt = $conn->prepare("
    SELECT
        p.*,
        c.full_name as client_name,
        a.appointment_date
    FROM payments p
    INNER JOIN users c ON p.client_id = c.id
    LEFT JOIN appointments a ON p.appointment_id = a.id
    WHERE p.dietitian_id = ?
        AND p.status = 'approved'
        AND p.payment_type = 'client_payment'
        AND p.commission_paid = FALSE
    ORDER BY p.approved_date DESC
");

$stmt->execute([$dietitianId]);
$unpaidCommissions = $stmt->fetchAll();

// Get commission payment history
$stmt = $conn->prepare("
    SELECT
        p.*,
        c.full_name as client_name,
        a.appointment_date
    FROM payments p
    INNER JOIN users c ON p.client_id = c.id
    LEFT JOIN appointments a ON p.appointment_id = a.id
    WHERE p.dietitian_id = ?
        AND p.payment_type = 'client_payment'
        AND p.commission_paid = TRUE
    ORDER BY p.payment_date DESC
");

$stmt->execute([$dietitianId]);
$paidCommissions = $stmt->fetchAll();

// Calculate totals
$totalUnpaid = array_reduce($unpaidCommissions, function($carry, $item) {
    return $carry + $item['commission_amount'];
}, 0);

$totalPaid = array_reduce($paidCommissions, function($carry, $item) {
    return $carry + $item['commission_amount'];
}, 0);

$pageTitle = 'Komisyon Ödemeleri';
$pageTitle = 'Komisyon Ödemeleri';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .payment-card { border-left: 4px solid #f093fb; padding: 20px; margin-bottom: 20px; background: #f8f9fa; border-radius: 8px; }
    .payment-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-box { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
</style>

<h2 class="mb-4">
    <i class="fas fa-hand-holding-usd text-primary me-2"></i>
    Komisyon Ödemeleri
</h2>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-box">
            <h6 class="text-muted">Ödenmemiş Komisyon</h6>
            <h3 class="text-danger"><?= number_format($totalUnpaid, 2) ?> ₺</h3>
            <p class="text-muted mb-0"><?= count($unpaidCommissions) ?> ödeme</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-box">
            <h6 class="text-muted">Ödenmiş Komisyon</h6>
            <h3 class="text-success"><?= number_format($totalPaid, 2) ?> ₺</h3>
            <p class="text-muted mb-0"><?= count($paidCommissions) ?> ödeme</p>
        </div>
    </div>
</div>

<!-- Unpaid Commissions -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Ödenmemiş Komisyonlar
        </h5>
    </div>
    <div class="card-body">
        <?php if (count($unpaidCommissions) === 0): ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="text-muted">Ödenmemiş komisyonunuz yok</h5>
                <p class="text-muted">Tüm komisyon ödemeleriniz tamamlanmış.</p>
            </div>
        <?php else: ?>
            <?php foreach ($unpaidCommissions as $payment): ?>
                <div class="payment-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-2">
                                <i class="fas fa-user text-primary me-2"></i>
                                <?= clean($payment['client_name']) ?>
                            </h5>
                            <p class="mb-1">
                                <i class="fas fa-calendar text-muted me-2"></i>
                                <strong>Randevu Tarihi:</strong>
                                <?= date('d.m.Y', strtotime($payment['appointment_date'])) ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-lira-sign text-muted me-2"></i>
                                <strong>Toplam Ödeme:</strong> <?= number_format($payment['amount'], 2) ?> ₺
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-percentage text-danger me-2"></i>
                                <strong>Komisyon (10%):</strong>
                                <span class="text-danger fw-bold"><?= number_format($payment['commission_amount'], 2) ?> ₺</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#uploadModal<?= $payment['id'] ?>">
                                <i class="fas fa-upload me-2"></i>
                                Dekont Yükle
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Modal -->
                <div class="modal fade" id="uploadModal<?= $payment['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-upload me-2"></i>
                                    Komisyon Dekontu Yükle
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Danışan:</strong> <?= clean($payment['client_name']) ?><br>
                                        <strong>Randevu:</strong> <?= date('d.m.Y', strtotime($payment['appointment_date'])) ?><br>
                                        <strong>Komisyon Tutarı:</strong> <span class="text-danger fw-bold"><?= number_format($payment['commission_amount'], 2) ?> ₺</span>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Komisyon Dekontu (JPG, PNG veya PDF)</label>
                                        <input type="file" class="form-control" name="commission_receipt"
                                               accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                                        <small class="text-muted">Maksimum dosya boyutu: 5MB</small>
                                    </div>

                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                    <button type="submit" name="upload_commission" class="btn btn-primary">
                                        <i class="fas fa-check me-2"></i>Yükle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Payment History -->
<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>
            Ödeme Geçmişi
        </h5>
    </div>
    <div class="card-body">
        <?php if (count($paidCommissions) === 0): ?>
            <div class="text-center py-4">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Henüz ödeme geçmişiniz yok</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Danışan</th>
                            <th>Randevu</th>
                            <th>Toplam</th>
                            <th>Komisyon</th>
                            <th>Dekont</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paidCommissions as $payment): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($payment['payment_date'])) ?></td>
                                <td><?= clean($payment['client_name']) ?></td>
                                <td><?= date('d.m.Y', strtotime($payment['appointment_date'])) ?></td>
                                <td><?= number_format($payment['amount'], 2) ?> ₺</td>
                                <td><strong class="text-danger"><?= number_format($payment['commission_amount'], 2) ?> ₺</strong></td>
                                <td>
                                    <?php if ($payment['commission_receipt_path']): ?>
                                        <a href="/<?= $payment['commission_receipt_path'] ?>" target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Görüntüle
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
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

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
