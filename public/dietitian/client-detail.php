<?php
/**
 * Diyetlenio - Danışan Detay Sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$clientId = $_GET['id'] ?? null;

if (!$clientId) {
    setFlash('error', 'Danışan bulunamadı.');
    redirect('/dietitian/clients.php');
}

// Danışan bilgileri
$stmt = $conn->prepare("
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    WHERE u.id = ? AND u.user_type = 'client'
");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    setFlash('error', 'Danışan bulunamadı.');
    redirect('/dietitian/clients.php');
}

// Randevu istatistikleri
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM appointments
    WHERE client_id = ? AND dietitian_id = ?
");
$stmt->execute([$clientId, $userId]);
$appointmentStats = $stmt->fetch();

// Son randevular
$stmt = $conn->prepare("
    SELECT * FROM appointments
    WHERE client_id = ? AND dietitian_id = ?
    ORDER BY appointment_date DESC
    LIMIT 5
");
$stmt->execute([$clientId, $userId]);
$recentAppointments = $stmt->fetchAll();

// Kilo geçmişi
$stmt = $conn->prepare("
    SELECT * FROM weight_tracking
    WHERE client_id = ?
    ORDER BY measurement_date DESC
    LIMIT 10
");
$stmt->execute([$clientId]);
$weightHistory = $stmt->fetchAll();

// Aktif diyet planı
$stmt = $conn->prepare("
    SELECT * FROM diet_plans
    WHERE client_id = ? AND dietitian_id = ? AND is_active = 1
    LIMIT 1
");
$stmt->execute([$clientId, $userId]);
$activePlan = $stmt->fetch();

$pageTitle = 'Danışan Detayı';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .info-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
</style>

<h2 class="mb-4">Danışan Detayı</h2>

<div class="row">
    <div class="col-md-4">
        <div class="info-card">
            <h5><?= clean($client['full_name']) ?></h5>
            <p class="mb-1"><strong>Email:</strong> <?= clean($client['email']) ?></p>
            <p class="mb-1"><strong>Telefon:</strong> <?= clean($client['phone']) ?></p>
            <?php if ($client['date_of_birth']): ?>
                <p class="mb-1"><strong>Doğum Tarihi:</strong> <?= date('d.m.Y', strtotime($client['date_of_birth'])) ?></p>
            <?php endif; ?>
            <?php if ($client['gender']): ?>
                <p class="mb-1"><strong>Cinsiyet:</strong> <?= $client['gender'] === 'male' ? 'Erkek' : ($client['gender'] === 'female' ? 'Kadın' : 'Diğer') ?></p>
            <?php endif; ?>
        </div>

        <div class="info-card">
            <h6>Randevu İstatistikleri</h6>
            <p class="mb-1"><strong>Toplam:</strong> <?= $appointmentStats['total'] ?></p>
            <p class="mb-0"><strong>Tamamlanan:</strong> <?= $appointmentStats['completed'] ?></p>
        </div>
    </div>

    <div class="col-md-8">
        <div class="info-card">
            <h6>Son Randevular</h6>
            <?php if (count($recentAppointments) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAppointments as $apt): ?>
                            <tr>
                                <td><?= date('d.m.Y H:i', strtotime($apt['appointment_date'])) ?></td>
                                <td><span class="badge bg-<?= $apt['status'] === 'completed' ? 'success' : 'warning' ?>"><?= $apt['status'] ?></span></td>
                                <td><a href="/dietitian/appointment-detail.php?id=<?= $apt['id'] ?>" class="btn btn-sm btn-outline-primary">Detay</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">Henüz randevu bulunmuyor.</p>
            <?php endif; ?>
        </div>

        <?php if ($activePlan): ?>
            <div class="info-card">
                <h6>Aktif Diyet Planı</h6>
                <p><strong><?= clean($activePlan['plan_name']) ?></strong></p>
                <p class="mb-0 small">
                    <?= date('d.m.Y', strtotime($activePlan['start_date'])) ?> -
                    <?= date('d.m.Y', strtotime($activePlan['end_date'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <h6>Kilo Geçmişi</h6>
            <?php if (count($weightHistory) > 0): ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kilo (kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weightHistory as $weight): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($weight['measurement_date'])) ?></td>
                                <td><?= number_format($weight['weight'], 1) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">Henüz kilo kaydı bulunmuyor.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<a href="/dietitian/clients.php" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-2"></i>Geri Dön
</a>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
