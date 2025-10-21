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
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
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
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
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
                        <h2><?= clean($client['full_name']) ?></h2>
                        <a href="/dietitian/clients.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Geri
                        </a>
                    </div>

                    <div class="row">
                        <!-- Profile Info -->
                        <div class="col-md-4">
                            <div class="info-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-user text-primary me-2"></i>Kişisel Bilgiler
                                </h5>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted">Email:</td>
                                        <td><?= clean($client['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Telefon:</td>
                                        <td><?= clean($client['phone']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Doğum Tarihi:</td>
                                        <td><?= $client['birth_date'] ? date('d.m.Y', strtotime($client['birth_date'])) : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Cinsiyet:</td>
                                        <td><?= $client['gender'] ?? '-' ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="info-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-heartbeat text-danger me-2"></i>Sağlık Bilgileri
                                </h5>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td class="text-muted">Boy:</td>
                                        <td><?= $client['height'] ? $client['height'] . ' cm' : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Hedef Kilo:</td>
                                        <td><?= $client['target_weight'] ? $client['target_weight'] . ' kg' : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Aktivite:</td>
                                        <td><?= $client['activity_level'] ?? '-' ?></td>
                                    </tr>
                                </table>
                                <?php if ($client['health_conditions']): ?>
                                    <div class="mt-3">
                                        <strong>Sağlık Durumu:</strong>
                                        <p class="text-muted small mb-0"><?= clean($client['health_conditions']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stats & History -->
                        <div class="col-md-8">
                            <!-- Stats -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="info-card text-center">
                                        <h3 class="text-primary"><?= $appointmentStats['total'] ?></h3>
                                        <p class="text-muted mb-0">Toplam Randevu</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-card text-center">
                                        <h3 class="text-success"><?= $appointmentStats['completed'] ?></h3>
                                        <p class="text-muted mb-0">Tamamlanan</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-card text-center">
                                        <h3 class="text-warning"><?= $activePlan ? '1' : '0' ?></h3>
                                        <p class="text-muted mb-0">Aktif Plan</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Appointments -->
                            <div class="info-card mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-calendar-alt text-success me-2"></i>Son Randevular
                                </h5>
                                <?php if (count($recentAppointments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Durum</th>
                                                    <th>Tür</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentAppointments as $apt): ?>
                                                    <tr>
                                                        <td><?= date('d.m.Y H:i', strtotime($apt['appointment_date'])) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $apt['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                                <?= $apt['status'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= $apt['is_online'] ? 'Online' : 'Yüz Yüze' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Henüz randevu yok.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Weight History -->
                            <div class="info-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-weight text-info me-2"></i>Kilo Geçmişi
                                </h5>
                                <?php if (count($weightHistory) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Kilo</th>
                                                    <th>Notlar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($weightHistory as $weight): ?>
                                                    <tr>
                                                        <td><?= date('d.m.Y', strtotime($weight['measurement_date'])) ?></td>
                                                        <td><strong><?= $weight['weight'] ?> kg</strong></td>
                                                        <td><?= clean($weight['notes']) ?: '-' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Henüz kilo kaydı yok.</p>
                                <?php endif; ?>
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
