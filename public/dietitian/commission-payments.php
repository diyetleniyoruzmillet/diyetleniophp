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
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .sidebar { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; color: white; }
        .content-wrapper { background: white; border-radius: 15px; padding: 30px; margin: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin-bottom: 5px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-link i { width: 20px; }
        .sidebar-brand { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .sidebar-subtitle { font-size: 14px; color: rgba(255,255,255,0.6); }
        .stats-card { border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .stats-card.danger { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .stats-card.success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .commission-card { border-left: 4px solid #f5576c; padding: 20px; margin-bottom: 20px; background: #f8f9fa; border-radius: 8px; }
        .commission-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/dietitian-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">
                        <i class="fas fa-percentage text-danger me-2"></i>
                        Komisyon Ödemeleri
                    </h2>

                    <?php if (hasFlash()): ?>
                        <div class="alert alert-<?= getFlash('type') ?> alert-dismissible fade show">
                            <?= getFlash('message') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="stats-card danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Ödenmesi Gereken Toplam Komisyon</h6>
                                        <h3 class="mb-0"><?= number_format($totalUnpaid, 2) ?> ₺</h3>
                                        <small><?= count($unpaidCommissions) ?> ödeme bekliyor</small>
                                    </div>
                                    <i class="fas fa-exclamation-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stats-card success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Ödenen Toplam Komisyon</h6>
                                        <h3 class="mb-0"><?= number_format($totalPaid, 2) ?> ₺</h3>
                                        <small><?= count($paidCommissions) ?> ödeme tamamlandı</small>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Bilgi:</strong> İlk ücretsiz görüşme haricindeki tüm randevular için Diyetlenio platformuna %10 komisyon ödemesi yapmanız gerekmektedir. 
                        Danışanlarınızın ödemeleri admin tarafından onaylandıktan sonra buradan komisyon ödemenizi yapabilirsiniz.
                    </div>

                    <!-- Unpaid Commissions -->
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Ödenmesi Gereken Komisyonlar
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($unpaidCommissions) === 0): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-muted">Ödenmesi gereken komisyon yok</h5>
                                    <p class="text-muted">Tüm komisyon ödemeleriniz güncel.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($unpaidCommissions as $payment): ?>
                                    <div class="commission-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h5 class="mb-2 text-danger">
                                                    <i class="fas fa-exclamation-circle me-2"></i>
                                                    Komisyon #<?= $payment['id'] ?>
                                                </h5>
                                                <p class="mb-1">
                                                    <i class="fas fa-user text-muted me-2"></i>
                                                    <strong>Danışan:</strong> <?= clean($payment['client_name']) ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-calendar text-muted me-2"></i>
                                                    <strong>Randevu Tarihi:</strong>
                                                    <?= $payment['appointment_date']
                                                        ? date('d.m.Y H:i', strtotime($payment['appointment_date']))
                                                        : '-'
                                                    ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-money-bill text-muted me-2"></i>
                                                    <strong>Randevu Ücreti:</strong> <?= number_format($payment['amount'], 2) ?> ₺
                                                </p>
                                                <p class="mb-0">
                                                    <i class="fas fa-percentage text-danger me-2"></i>
                                                    <strong>Komisyon (%10):</strong>
                                                    <span class="text-danger fw-bold"><?= number_format($payment['commission_amount'], 2) ?> ₺</span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <button class="btn btn-danger" data-bs-toggle="modal"
                                                        data-bs-target="#uploadModal<?= $payment['id'] ?>">
                                                    <i class="fas fa-upload me-2"></i>
                                                    Komisyon Öde
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Upload Modal -->
                                    <div class="modal fade" id="uploadModal<?= $payment['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
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
                                                            <strong>Randevu:</strong> <?= clean($payment['client_name']) ?><br>
                                                            <strong>Tarih:</strong> 
                                                            <?= $payment['appointment_date']
                                                                ? date('d.m.Y', strtotime($payment['appointment_date']))
                                                                : '-'
                                                            ?><br>
                                                            <strong>Ödenecek Komisyon:</strong> <span class="text-danger fw-bold"><?= number_format($payment['commission_amount'], 2) ?> ₺</span>
                                                        </div>

                                                        <div class="alert alert-warning">
                                                            <strong>IBAN Bilgisi:</strong><br>
                                                            <code>TR00 0000 0000 0000 0000 0000 00</code><br>
                                                            <small>Diyetlenio Platform - Garanti BBVA</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Komisyon Ödeme Dekontu (JPG, PNG veya PDF)</label>
                                                            <input type="file" class="form-control" name="commission_receipt"
                                                                   accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                                                            <small class="text-muted">Maksimum dosya boyutu: 5MB</small>
                                                        </div>

                                                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                        <button type="submit" name="upload_commission" class="btn btn-danger">
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

                    <!-- Paid Commissions History -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                Ödenen Komisyonlar
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($paidCommissions) === 0): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Henüz ödenen komisyon yok</h5>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Ödeme Tarihi</th>
                                                <th>Danışan</th>
                                                <th>Randevu</th>
                                                <th>Randevu Ücreti</th>
                                                <th>Komisyon</th>
                                                <th>Dekont</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paidCommissions as $payment): ?>
                                                <tr>
                                                    <td>#<?= $payment['id'] ?></td>
                                                    <td><?= date('d.m.Y H:i', strtotime($payment['payment_date'])) ?></td>
                                                    <td><?= clean($payment['client_name']) ?></td>
                                                    <td>
                                                        <?= $payment['appointment_date']
                                                            ? date('d.m.Y', strtotime($payment['appointment_date']))
                                                            : '-'
                                                        ?>
                                                    </td>
                                                    <td><?= number_format($payment['amount'], 2) ?> ₺</td>
                                                    <td><strong class="text-danger"><?= number_format($payment['commission_amount'], 2) ?> ₺</strong></td>
                                                    <td>
                                                        <?php if ($payment['commission_receipt_path']): ?>
                                                            <a href="/<?= $payment['commission_receipt_path'] ?>" target="_blank"
                                                               class="btn btn-sm btn-outline-success">
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
