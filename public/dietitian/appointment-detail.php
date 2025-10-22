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
                        <a class="nav-link" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link active" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Randevu Detayı</h2>
                        <a href="/dietitian/appointments.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Geri
                        </a>
                    </div>

                    <?php if (hasFlash()): ?>
                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Randevu Bilgileri</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted">Danışan:</td>
                                            <td><strong><?= clean($appointment['client_name']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email:</td>
                                            <td><?= clean($appointment['client_email']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Telefon:</td>
                                            <td><?= clean($appointment['client_phone']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tarih:</td>
                                            <td><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Saat:</td>
                                            <td><?= date('H:i', strtotime($appointment['appointment_date'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tür:</td>
                                            <td>
                                                <?php if ($appointment['is_online']): ?>
                                                    <span class="badge bg-info">Online Görüşme</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Yüz Yüze</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Durum:</td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'scheduled' => 'warning',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $labels = [
                                                    'scheduled' => 'Planlandı',
                                                    'completed' => 'Tamamlandı',
                                                    'cancelled' => 'İptal'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $badges[$appointment['status']] ?>">
                                                    <?= $labels[$appointment['status']] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php if ($appointment['status'] === 'scheduled' && $appointment['is_online']): ?>
                                        <a href="/video-call.php?appointment=<?= $appointment['id'] ?>" class="btn btn-success w-100">
                                            <i class="fas fa-video me-2"></i>Görüşmeyi Başlat
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Randevu Notları</h5>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <textarea
                                            name="notes"
                                            class="form-control mb-3"
                                            rows="10"
                                            placeholder="Randevu ile ilgili notlarınızı buraya yazabilirsiniz..."
                                        ><?= clean($appointment['notes']) ?></textarea>
                                        <button type="submit" name="save_notes" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Notları Kaydet
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
