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

// Handle payment approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirect('/admin/payments.php');
    }

    // Input sanitization
    $paymentId = sanitizeInt($_POST['payment_id'] ?? 0);
    $newStatus = sanitizeString($_POST['status'] ?? '', 20);
    $adminNote = sanitizeString($_POST['admin_note'] ?? '', 500);

    // Validation
    if ($paymentId <= 0) {
        setFlash('error', 'Geçersiz ödeme ID.');
        redirect('/admin/payments.php');
    }

    if (!in_array($newStatus, ['approved', 'rejected'], true)) {
        setFlash('error', 'Geçersiz durum.');
        redirect('/admin/payments.php');
    }

    // Rate limiting
    $rateLimiter = new RateLimiter($db);
    $adminUserId = $auth->user()->getId();
    if ($rateLimiter->tooManyAttempts('admin_payment_action', 'user_' . $adminUserId, 30, 1)) {
        setFlash('error', 'Çok fazla işlem yaptınız. Lütfen bekleyin.');
        redirect('/admin/payments.php');
    }
    $rateLimiter->hit(hash('sha256', 'admin_payment_action|user_' . $adminUserId), 1);

    try {
        $stmt = $conn->prepare("
            UPDATE payments
            SET status = ?,
                admin_note = ?,
                approved_date = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$newStatus, $adminNote, $paymentId]);

        setFlash('success', 'Ödeme durumu güncellendi.');
        redirect('/admin/payments.php');

    } catch (Exception $e) {
        error_log('Payment update error: ' . $e->getMessage());
        setFlash('error', 'Bir hata oluştu. Lütfen tekrar deneyin.');
        redirect('/admin/payments.php');
    }
}

// Ödemeleri çek
try {
    $stmt = $conn->query("
        SELECT p.*,
               c.full_name as client_name,
               d.full_name as dietitian_name,
               a.appointment_date
        FROM payments p
        INNER JOIN users c ON p.client_id = c.id
        INNER JOIN users d ON p.dietitian_id = d.id
        LEFT JOIN appointments a ON p.appointment_id = a.id
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
                    <h2 class="mb-4">
                        <i class="fas fa-money-bill-wave text-success me-2"></i>
                        Ödeme Yönetimi
                    </h2>

                    <?php if (hasFlash()): ?>
                        <div class="alert alert-<?= getFlash('type') ?> alert-dismissible fade show">
                            <?= getFlash('message') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

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
                                                <th>Randevu</th>
                                                <th>Tutar</th>
                                                <th>Komisyon (%10)</th>
                                                <th>Dekont</th>
                                                <th>Durum</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td>#<?= $payment['id'] ?></td>
                                                    <td><?= date('d.m.Y H:i', strtotime($payment['payment_date'])) ?></td>
                                                    <td><?= clean($payment['client_name']) ?></td>
                                                    <td><?= clean($payment['dietitian_name']) ?></td>
                                                    <td>
                                                        <?= $payment['appointment_date']
                                                            ? date('d.m.Y', strtotime($payment['appointment_date']))
                                                            : '-'
                                                        ?>
                                                    </td>
                                                    <td><strong class="text-success"><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                                    <td><strong class="text-primary"><?= number_format($payment['commission_amount'], 2) ?> ₺</strong></td>
                                                    <td>
                                                        <?php if ($payment['receipt_path']): ?>
                                                            <a href="/<?= $payment['receipt_path'] ?>" target="_blank"
                                                               class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye"></i> Görüntüle
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badges = [
                                                            'approved' => 'success',
                                                            'pending' => 'warning',
                                                            'rejected' => 'danger'
                                                        ];
                                                        $statusText = [
                                                            'approved' => 'Onaylandı',
                                                            'pending' => 'Bekliyor',
                                                            'rejected' => 'Reddedildi'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $badges[$payment['status']] ?>">
                                                            <?= $statusText[$payment['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($payment['status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                                    data-bs-target="#reviewModal<?= $payment['id'] ?>" title="İncele">
                                                                <i class="fas fa-check-circle"></i> İncele
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                                                    data-bs-target="#detailModal<?= $payment['id'] ?>" title="Detay">
                                                                <i class="fas fa-info-circle"></i> Detay
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- Review Modal (for pending payments) -->
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                <div class="modal fade" id="reviewModal<?= $payment['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-file-invoice me-2"></i>
                                                                    Ödeme İnceleme #<?= $payment['id'] ?>
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-primary mb-3">Ödeme Bilgileri</h6>
                                                                        <table class="table table-sm">
                                                                            <tr>
                                                                                <th width="40%">Danışan:</th>
                                                                                <td><?= clean($payment['client_name']) ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Diyetisyen:</th>
                                                                                <td><?= clean($payment['dietitian_name']) ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Randevu:</th>
                                                                                <td>
                                                                                    <?= $payment['appointment_date']
                                                                                        ? date('d.m.Y H:i', strtotime($payment['appointment_date']))
                                                                                        : '-'
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Ödeme Tarihi:</th>
                                                                                <td><?= date('d.m.Y H:i', strtotime($payment['payment_date'])) ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Tutar:</th>
                                                                                <td><strong class="text-success"><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Komisyon (%10):</th>
                                                                                <td><strong class="text-primary"><?= number_format($payment['commission_amount'], 2) ?> ₺</strong></td>
                                                                            </tr>
                                                                        </table>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-primary mb-3">Ödeme Dekontu</h6>
                                                                        <?php if ($payment['receipt_path']): ?>
                                                                            <?php
                                                                            $ext = pathinfo($payment['receipt_path'], PATHINFO_EXTENSION);
                                                                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])):
                                                                            ?>
                                                                                <img src="/<?= $payment['receipt_path'] ?>"
                                                                                     class="img-fluid border rounded" alt="Dekont">
                                                                            <?php else: ?>
                                                                                <div class="text-center p-4 border rounded">
                                                                                    <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                                                                                    <p>PDF Dosyası</p>
                                                                                    <a href="/<?= $payment['receipt_path'] ?>" target="_blank"
                                                                                       class="btn btn-sm btn-primary">
                                                                                        <i class="fas fa-download me-1"></i>İndir
                                                                                    </a>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <p class="text-muted">Dekont yüklenmemiş</p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <hr class="my-4">

                                                                <form method="POST">
                                                                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Karar</label>
                                                                        <select name="status" class="form-select" required>
                                                                            <option value="">Seçiniz...</option>
                                                                            <option value="approved">Onayla</option>
                                                                            <option value="rejected">Reddet</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Not (Opsiyonel)</label>
                                                                        <textarea name="admin_note" class="form-control" rows="3"
                                                                                  placeholder="İnceleme notunuz..."></textarea>
                                                                    </div>

                                                                    <div class="d-flex justify-content-between">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                                        <button type="submit" name="update_status" class="btn btn-success">
                                                                            <i class="fas fa-save me-2"></i>Kaydet
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <!-- Detail Modal (for approved/rejected payments) -->
                                                <div class="modal fade" id="detailModal<?= $payment['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-info text-white">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-info-circle me-2"></i>
                                                                    Ödeme Detayı #<?= $payment['id'] ?>
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-primary mb-3">Ödeme Bilgileri</h6>
                                                                        <table class="table table-sm">
                                                                            <tr>
                                                                                <th width="40%">Danışan:</th>
                                                                                <td><?= clean($payment['client_name']) ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Diyetisyen:</th>
                                                                                <td><?= clean($payment['dietitian_name']) ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Randevu:</th>
                                                                                <td>
                                                                                    <?= $payment['appointment_date']
                                                                                        ? date('d.m.Y H:i', strtotime($payment['appointment_date']))
                                                                                        : '-'
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Ödeme Tarihi:</th>
                                                                                <td><?= date('d.m.Y H:i', strtotime($payment['payment_date'])) ?></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Tutar:</th>
                                                                                <td><strong class="text-success"><?= number_format($payment['amount'], 2) ?> ₺</strong></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Komisyon (%10):</th>
                                                                                <td><strong class="text-primary"><?= number_format($payment['commission_amount'], 2) ?> ₺</strong></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Durum:</th>
                                                                                <td>
                                                                                    <span class="badge bg-<?= $badges[$payment['status']] ?>">
                                                                                        <?= $statusText[$payment['status']] ?>
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                            <?php if ($payment['approved_date']): ?>
                                                                            <tr>
                                                                                <th>Karar Tarihi:</th>
                                                                                <td><?= date('d.m.Y H:i', strtotime($payment['approved_date'])) ?></td>
                                                                            </tr>
                                                                            <?php endif; ?>
                                                                            <?php if ($payment['admin_note']): ?>
                                                                            <tr>
                                                                                <th>Admin Notu:</th>
                                                                                <td><?= clean($payment['admin_note']) ?></td>
                                                                            </tr>
                                                                            <?php endif; ?>
                                                                        </table>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6 class="text-primary mb-3">Ödeme Dekontu</h6>
                                                                        <?php if ($payment['receipt_path']): ?>
                                                                            <?php
                                                                            $ext = pathinfo($payment['receipt_path'], PATHINFO_EXTENSION);
                                                                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])):
                                                                            ?>
                                                                                <img src="/<?= $payment['receipt_path'] ?>"
                                                                                     class="img-fluid border rounded" alt="Dekont">
                                                                            <?php else: ?>
                                                                                <div class="text-center p-4 border rounded">
                                                                                    <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                                                                                    <p>PDF Dosyası</p>
                                                                                    <a href="/<?= $payment['receipt_path'] ?>" target="_blank"
                                                                                       class="btn btn-sm btn-primary">
                                                                                        <i class="fas fa-download me-1"></i>İndir
                                                                                    </a>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <p class="text-muted">Dekont yüklenmemiş</p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
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
