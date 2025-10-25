<?php
/**
 * Diyetlenio - Diyetisyen Dashboard
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece diyetisyen erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$user = $auth->user();
$userId = $user->getId();

try {
    $conn = $db->getConnection();

    // Diyetisyen profil bilgilerini çek
    $stmt = $conn->prepare("
        SELECT * FROM dietitian_profiles WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    // Onaylanmamış ise bekleme sayfasına yönlendir
    if (!$profile || (int)$profile['is_approved'] !== 1) {
        redirect('/dietitian/pending-approval.php');
    }

    // Bugünkü randevular
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as client_name, u.phone as client_phone
        FROM appointments a
        INNER JOIN users u ON a.client_id = u.id
        WHERE a.dietitian_id = ? AND DATE(a.appointment_date) = CURDATE()
        ORDER BY a.start_time ASC
    ");
    $stmt->execute([$userId]);
    $todayAppointments = $stmt->fetchAll();

    // Genel istatistikler
    $stmt = $conn->prepare("
        SELECT
            COUNT(DISTINCT client_id) as total_clients,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as upcoming_appointments,
            SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today_count
        FROM appointments
        WHERE dietitian_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    // Bu ay gelir
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_income
        FROM payments
        WHERE dietitian_id = ?
        AND status = 'approved'
        AND MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$userId]);
    $income = $stmt->fetch();

    // Son danışanlar
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.full_name, u.email, u.created_at,
               (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ?) as appointment_count
        FROM users u
        INNER JOIN appointments a ON u.id = a.client_id
        WHERE a.dietitian_id = ?
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId, $userId]);
    $recentClients = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Dietitian dashboard error: ' . $e->getMessage());
    $profile = null;
    $todayAppointments = [];
    $stats = $income = [];
    $recentClients = [];
}

$pageTitle = 'Diyetisyen Dashboard';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
</style>

<h2 class="mb-4">Hoş geldiniz, <?= clean($user->getFullName()) ?>!</h2>

<!-- Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Toplam Danışan</p>
                    <h3 class="mb-0"><?= number_format($stats['total_clients'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon bg-primary text-white">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Tamamlanan</p>
                    <h3 class="mb-0"><?= number_format($stats['completed_appointments'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon bg-success text-white">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Yaklaşan</p>
                    <h3 class="mb-0"><?= number_format($stats['upcoming_appointments'] ?? 0) ?></h3>
                </div>
                <div class="stat-icon bg-warning text-white">
                    <i class="fas fa-calendar"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Bu Ay Gelir</p>
                    <h3 class="mb-0"><?= number_format($income['monthly_income'] ?? 0) ?> ₺</h3>
                </div>
                <div class="stat-icon bg-info text-white">
                    <i class="fas fa-lira-sign"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Appointments -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Bugünün Randevuları (<?= count($todayAppointments) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($todayAppointments) === 0): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Bugün randevu bulunmuyor.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Saat</th>
                                    <th>Danışan</th>
                                    <th>Telefon</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAppointments as $apt): ?>
                                    <tr>
                                        <td><strong><?= date('H:i', strtotime($apt['start_time'])) ?></strong></td>
                                        <td><?= clean($apt['client_name']) ?></td>
                                        <td><?= clean($apt['client_phone']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $apt['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($apt['status']) ?>
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

    <!-- Recent Clients -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Son Danışanlar</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentClients) === 0): ?>
                    <p class="text-muted text-center py-3">Henüz danışan yok.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentClients as $client): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= clean($client['full_name']) ?></h6>
                                        <small class="text-muted"><?= $client['appointment_count'] ?> randevu</small>
                                    </div>
                                    <a href="/dietitian/clients.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Görüntüle
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
