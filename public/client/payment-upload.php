<?php
/**
 * Diyetlenio - Client Payment Upload
 * Danışanların randevu ödemelerini yükledikleri sayfa
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$clientId = $auth->user()->getId();

// Handle payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_payment'])) {
    // CSRF protection
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirect('/client/payment-upload.php');
    }

    try {
        $appointmentId = (int)$_POST['appointment_id'];
        $amount = (float)$_POST['amount'];
        $dietitianId = (int)$_POST['dietitian_id'];

        // Validate file upload
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lütfen dekont dosyasını yükleyin.');
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $fileType = $_FILES['receipt']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Sadece JPG, PNG veya PDF dosyası yükleyebilirsiniz.');
        }

        // Validate file size (max 5MB)
        if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Dosya boyutu en fazla 5MB olabilir.');
        }

        // Create upload directory if not exists
        $uploadDir = __DIR__ . '/../../storage/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $fileName = 'receipt_' . $clientId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath)) {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }

        $receiptPath = 'storage/receipts/' . $fileName;

        // Calculate commission (10%)
        $commissionAmount = $amount * 0.10;

        // Insert payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (
                client_id, dietitian_id, appointment_id,
                amount, commission_amount, payment_type,
                receipt_path, status, payment_date
            ) VALUES (?, ?, ?, ?, ?, 'client_payment', ?, 'pending', NOW())
        ");

        $stmt->execute([
            $clientId,
            $dietitianId,
            $appointmentId,
            $amount,
            $commissionAmount,
            $receiptPath
        ]);

        setFlash('success', 'Ödeme dekontunuz başarıyla yüklendi. Admin onayı bekleniyor.');
        redirect('/client/payment-upload.php');

    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

// Get unpaid appointments (excluding first free consultation)
$stmt = $conn->prepare("
    SELECT
        a.id,
        a.appointment_date,
        a.appointment_type,
        u.full_name as dietitian_name,
        dp.consultation_fee,
        dp.online_consultation_fee,
        a.dietitian_id,
        (
            SELECT COUNT(*)
            FROM appointments a2
            WHERE a2.client_id = a.client_id
                AND a2.dietitian_id = a.dietitian_id
                AND a2.id <= a.id
                AND a2.status != 'cancelled'
        ) as appointment_number
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE a.client_id = ?
        AND a.status = 'completed'
        AND a.id NOT IN (
            SELECT appointment_id
            FROM payments
            WHERE appointment_id IS NOT NULL
        )
    ORDER BY a.appointment_date DESC
");

$stmt->execute([$clientId]);
$unpaidAppointments = $stmt->fetchAll();

// Filter out first appointments (free consultation)
$unpaidAppointments = array_filter($unpaidAppointments, function($apt) {
    return $apt['appointment_number'] > 1;
});

// Get payment history
$stmt = $conn->prepare("
    SELECT
        p.*,
        u.full_name as dietitian_name,
        a.appointment_date
    FROM payments p
    INNER JOIN users u ON p.dietitian_id = u.id
    LEFT JOIN appointments a ON p.appointment_id = a.id
    WHERE p.client_id = ?
        AND p.payment_type = 'client_payment'
    ORDER BY p.payment_date DESC
");

$stmt->execute([$clientId]);
$paymentHistory = $stmt->fetchAll();

$pageTitle = 'Ödeme Yükleme';
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
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1e7dd; color: #0f5132; }
        .status-rejected { background: #f8d7da; color: #842029; }
        .appointment-card { border-left: 4px solid #667eea; padding: 20px; margin-bottom: 20px; background: #f8f9fa; border-radius: 8px; }
        .appointment-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/client-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">
                        <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                        Ödeme Yükleme
                    </h2>

                    <?php if (hasFlash()): ?>
                        <div class="alert alert-<?= clean(getFlash('type')) ?> alert-dismissible fade show">
                            <?= clean(getFlash('message')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Unpaid Appointments -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Ödeme Beklenen Randevular
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($unpaidAppointments) === 0): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h5 class="text-muted">Ödeme beklenen randevunuz yok</h5>
                                    <p class="text-muted">Tüm ödemeleriniz tamamlanmış görünüyor.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($unpaidAppointments as $apt): ?>
                                    <div class="appointment-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h5 class="mb-2">
                                                    <i class="fas fa-user-doctor text-primary me-2"></i>
                                                    <?= clean($apt['dietitian_name']) ?>
                                                </h5>
                                                <p class="mb-1">
                                                    <i class="fas fa-calendar text-muted me-2"></i>
                                                    <strong>Randevu Tarihi:</strong>
                                                    <?= date('d.m.Y H:i', strtotime($apt['appointment_date'])) ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-video text-muted me-2"></i>
                                                    <strong>Görüşme Tipi:</strong>
                                                    <?= $apt['appointment_type'] === 'online' ? 'Online' : 'Yüz Yüze' ?>
                                                </p>
                                                <p class="mb-0">
                                                    <i class="fas fa-lira-sign text-success me-2"></i>
                                                    <strong>Tutar:</strong>
                                                    <?php
                                                    $fee = $apt['appointment_type'] === 'online'
                                                        ? $apt['online_consultation_fee']
                                                        : $apt['consultation_fee'];
                                                    ?>
                                                    <span class="text-success fw-bold"><?= number_format($fee, 2) ?> ₺</span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <button class="btn btn-success" data-bs-toggle="modal"
                                                        data-bs-target="#uploadModal<?= $apt['id'] ?>">
                                                    <i class="fas fa-upload me-2"></i>
                                                    Dekont Yükle
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Upload Modal -->
                                    <div class="modal fade" id="uploadModal<?= $apt['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-upload me-2"></i>
                                                        Ödeme Dekontu Yükle
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" enctype="multipart/form-data">
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            <strong>Diyetisyen:</strong> <?= clean($apt['dietitian_name']) ?><br>
                                                            <strong>Randevu:</strong> <?= date('d.m.Y H:i', strtotime($apt['appointment_date'])) ?><br>
                                                            <strong>Ödenecek Tutar:</strong> <span class="text-success fw-bold"><?= number_format($fee, 2) ?> ₺</span>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Ödeme Dekontu (JPG, PNG veya PDF)</label>
                                                            <input type="file" class="form-control" name="receipt"
                                                                   accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                                                            <small class="text-muted">Maksimum dosya boyutu: 5MB</small>
                                                        </div>

                                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                                        <input type="hidden" name="amount" value="<?= $fee ?>">
                                                        <input type="hidden" name="dietitian_id" value="<?= $apt['dietitian_id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                        <button type="submit" name="upload_payment" class="btn btn-success">
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
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Ödeme Geçmişi
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($paymentHistory) === 0): ?>
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
                                                <th>Diyetisyen</th>
                                                <th>Randevu</th>
                                                <th>Tutar</th>
                                                <th>Durum</th>
                                                <th>Dekont</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paymentHistory as $payment): ?>
                                                <tr>
                                                    <td><?= date('d.m.Y H:i', strtotime($payment['payment_date'])) ?></td>
                                                    <td><?= clean($payment['dietitian_name']) ?></td>
                                                    <td>
                                                        <?= $payment['appointment_date']
                                                            ? date('d.m.Y', strtotime($payment['appointment_date']))
                                                            : '-'
                                                        ?>
                                                    </td>
                                                    <td><strong><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = [
                                                            'pending' => 'status-pending',
                                                            'approved' => 'status-approved',
                                                            'rejected' => 'status-rejected'
                                                        ];
                                                        $statusText = [
                                                            'pending' => 'Onay Bekliyor',
                                                            'approved' => 'Onaylandı',
                                                            'rejected' => 'Reddedildi'
                                                        ];
                                                        ?>
                                                        <span class="status-badge <?= $statusClass[$payment['status']] ?>">
                                                            <?= $statusText[$payment['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($payment['receipt_path']): ?>
                                                            <a href="/<?= $payment['receipt_path'] ?>" target="_blank"
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
