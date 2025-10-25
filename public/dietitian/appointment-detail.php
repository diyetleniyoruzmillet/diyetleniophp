<?php
/**
 * Diyetlenio - Randevu Detay
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$appointmentId = $_GET['id'] ?? null;

if (!$appointmentId) {
    redirect('/dietitian/appointments.php');
}

// Randevu bilgileri
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as client_name, u.email as client_email, u.phone as client_phone
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    WHERE a.id = ? AND a.dietitian_id = ?
");
$stmt->execute([$appointmentId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/dietitian/appointments.php');
}

// Not ekleme/güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $notes = trim($_POST['notes']);
        $stmt = $conn->prepare("UPDATE appointments SET notes = ? WHERE id = ?");
        $stmt->execute([$notes, $appointmentId]);
        setFlash('success', 'Notlar kaydedildi.');
        redirect('/dietitian/appointment-detail.php?id=' . $appointmentId);
    }
}

$pageTitle = 'Randevu Detayı';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<h2 class="mb-4">Randevu Detayı</h2>

<div class="card">
    <div class="card-body">
        <h5 class="card-title"><?= clean($appointment['client_name']) ?></h5>
        <p><strong>Email:</strong> <?= clean($appointment['client_email']) ?></p>
        <p><strong>Telefon:</strong> <?= clean($appointment['client_phone']) ?></p>
        <p><strong>Tarih:</strong> <?= date('d.m.Y H:i', strtotime($appointment['appointment_date'])) ?></p>
        <p><strong>Durum:</strong> <span class="badge bg-<?= $appointment['status'] === 'completed' ? 'success' : 'warning' ?>"><?= $appointment['status'] ?></span></p>

        <hr>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="mb-3">
                <label class="form-label">Randevu Notları</label>
                <textarea name="notes" class="form-control" rows="5"><?= clean($appointment['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="save_notes" class="btn btn-success">
                <i class="fas fa-save me-2"></i>Kaydet
            </button>
            <a href="/dietitian/appointments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </form>
    </div>
</div>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
