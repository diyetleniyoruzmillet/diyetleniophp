<?php
/**
 * Diyetlenio - Danışan Dashboard
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece client erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// İstatistikleri çek
$stmt = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM appointments WHERE client_id = ? AND status = 'completed') as completed_appointments,
        (SELECT COUNT(*) FROM appointments WHERE client_id = ? AND status = 'scheduled' AND appointment_date >= NOW()) as upcoming_appointments,
        (SELECT COUNT(*) FROM diet_plans WHERE client_id = ? AND status = 'active') as active_plans,
        (SELECT COUNT(DISTINCT dietitian_id) FROM appointments WHERE client_id = ?) as dietitians_worked_with
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$stats = $stmt->fetch();

// Aktif diyetisyeni çek (son randevusu olan)
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, dp.title, dp.specialization, dp.rating_avg
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE a.client_id = ? AND a.status IN ('scheduled', 'completed')
    ORDER BY a.appointment_date DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$currentDietitian = $stmt->fetch();

// Yaklaşan randevular
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as dietitian_name, dp.title
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE a.client_id = ? AND a.status = 'scheduled' AND a.appointment_date >= NOW()
    ORDER BY a.appointment_date ASC
    LIMIT 5
");
$stmt->execute([$userId]);
$upcomingAppointments = $stmt->fetchAll();

// Aktif diyet planı
$stmt = $conn->prepare("
    SELECT dp.*, u.full_name as dietitian_name
    FROM diet_plans dp
    INNER JOIN users u ON dp.dietitian_id = u.id
    WHERE dp.client_id = ? AND dp.status = 'active'
    ORDER BY dp.start_date DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$activePlan = $stmt->fetch();

// Son kilo takibi
$stmt = $conn->prepare("
    SELECT * FROM weight_tracking
    WHERE client_id = ?
    ORDER BY measurement_date DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$weightHistory = $stmt->fetchAll();

// Bugünün öğünleri (aktif plandan)
$todayMeals = [];
if ($activePlan) {
    $stmt = $conn->prepare("
        SELECT * FROM diet_plan_meals
        WHERE plan_id = ? AND day_of_week = DAYOFWEEK(NOW())
        ORDER BY meal_time ASC
    ");
    $stmt->execute([$activePlan['id']]);
    $todayMeals = $stmt->fetchAll();
}

$pageTitle = 'Danışan Paneli';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .action-card {
            border: 2px dashed #dee2e6;
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .action-card:hover {
            border-color: #28a745;
            background: #f8fff9;
        }
        .meal-item {
            padding: 15px;
            border-left: 4px solid #28a745;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="/client/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/client/dietitians.php">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenler
                        </a>
                        <a class="nav-link" href="/client/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link" href="/client/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planlarım
                        </a>
                        <a class="nav-link" href="/client/weight-tracking.php">
                            <i class="fas fa-weight me-2"></i>Kilo Takibi
                        </a>
                        <a class="nav-link" href="/client/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link" href="/client/profile.php">
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

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
                    <!-- Welcome Header -->
                    <div class="mb-4">
                        <h2>Hoş Geldiniz, <?= clean($auth->user()->getFullName()) ?>!</h2>
                        <p class="text-muted">Sağlıklı yaşam yolculuğunuza devam edin</p>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1">Tamamlanan Seanslar</p>
                                        <h3 class="mb-0"><?= number_format($stats['completed_appointments']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1">Yaklaşan Randevular</p>
                                        <h3 class="mb-0"><?= number_format($stats['upcoming_appointments']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1">Aktif Diyet Planı</p>
                                        <h3 class="mb-0"><?= number_format($stats['active_plans']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1">Çalışılan Diyetisyen</p>
                                        <h3 class="mb-0"><?= number_format($stats['dietitians_worked_with']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <!-- Current Dietitian -->
                            <?php if ($currentDietitian): ?>
                                <div class="card-custom mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">
                                            <i class="fas fa-user-md text-success me-2"></i>Diyetisyenim
                                        </h5>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= clean($currentDietitian['full_name']) ?></h6>
                                                <p class="text-muted mb-2"><?= clean($currentDietitian['title']) ?></p>
                                                <span class="badge bg-light text-dark">
                                                    <?= clean($currentDietitian['specialization']) ?>
                                                </span>
                                                <span class="ms-2">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <?= number_format($currentDietitian['rating_avg'], 1) ?>
                                                </span>
                                            </div>
                                            <div>
                                                <a href="/client/messages.php?dietitian_id=<?= $currentDietitian['id'] ?>"
                                                   class="btn btn-outline-success btn-sm me-2">
                                                    <i class="fas fa-envelope me-1"></i>Mesaj Gönder
                                                </a>
                                                <a href="/client/appointments.php?new=1" class="btn btn-success btn-sm">
                                                    <i class="fas fa-calendar-plus me-1"></i>Randevu Al
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Upcoming Appointments -->
                            <div class="card-custom mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>Yaklaşan Randevular
                                    </h5>
                                    <?php if (count($upcomingAppointments) === 0): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Yaklaşan randevunuz bulunmuyor.</p>
                                            <a href="/client/appointments.php?new=1" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Randevu Oluştur
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Tarih & Saat</th>
                                                        <th>Diyetisyen</th>
                                                        <th>Tür</th>
                                                        <th>Durum</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($upcomingAppointments as $apt): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?= date('d.m.Y', strtotime($apt['appointment_date'])) ?></strong><br>
                                                                <small class="text-muted"><?= date('H:i', strtotime($apt['appointment_date'])) ?></small>
                                                            </td>
                                                            <td>
                                                                <?= clean($apt['dietitian_name']) ?><br>
                                                                <small class="text-muted"><?= clean($apt['title']) ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($apt['is_online']): ?>
                                                                    <span class="badge bg-info">
                                                                        <i class="fas fa-video me-1"></i>Online
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">
                                                                        <i class="fas fa-clinic-medical me-1"></i>Yüz Yüze
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-warning">Planlandı</span>
                                                            </td>
                                                            <td>
                                                                <a href="/client/appointments.php?id=<?= $apt['id'] ?>"
                                                                   class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Today's Meals -->
                            <?php if ($activePlan && count($todayMeals) > 0): ?>
                                <div class="card-custom mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">
                                            <i class="fas fa-utensils text-warning me-2"></i>Bugünün Öğünleri
                                        </h5>
                                        <?php foreach ($todayMeals as $meal): ?>
                                            <div class="meal-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?= clean($meal['meal_type']) ?></strong>
                                                        <small class="text-muted ms-2">
                                                            <i class="far fa-clock me-1"></i><?= $meal['meal_time'] ?>
                                                        </small>
                                                        <p class="mb-0 mt-1"><?= nl2br(clean($meal['description'])) ?></p>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">
                                                            <i class="fas fa-fire me-1"></i><?= $meal['calories'] ?> kcal
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-4">
                            <!-- Weight Tracking Chart -->
                            <?php if (count($weightHistory) > 0): ?>
                                <div class="card-custom mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">
                                            <i class="fas fa-chart-line text-success me-2"></i>Kilo Takibi
                                        </h5>
                                        <canvas id="weightChart" height="200"></canvas>
                                        <div class="text-center mt-3">
                                            <a href="/client/weight-tracking.php" class="btn btn-sm btn-outline-success">
                                                Detaylı Görüntüle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Quick Actions -->
                            <div class="card-custom mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-bolt text-warning me-2"></i>Hızlı İşlemler
                                    </h5>
                                    <div class="d-grid gap-2">
                                        <a href="/client/dietitians.php" class="action-card">
                                            <i class="fas fa-search fa-2x text-success mb-2"></i>
                                            <p class="mb-0">Diyetisyen Bul</p>
                                        </a>
                                        <a href="/client/weight-tracking.php?new=1" class="action-card">
                                            <i class="fas fa-weight fa-2x text-primary mb-2"></i>
                                            <p class="mb-0">Kilo Kaydı Ekle</p>
                                        </a>
                                        <a href="/client/diet-plans.php" class="action-card">
                                            <i class="fas fa-clipboard-list fa-2x text-warning mb-2"></i>
                                            <p class="mb-0">Diyet Planlarım</p>
                                        </a>
                                        <a href="/articles" class="action-card">
                                            <i class="fas fa-book fa-2x text-info mb-2"></i>
                                            <p class="mb-0">Sağlık Makaleleri</p>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Plan Info -->
                            <?php if ($activePlan): ?>
                                <div class="card-custom">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">
                                            <i class="fas fa-clipboard-check text-success me-2"></i>Aktif Plan
                                        </h5>
                                        <h6><?= clean($activePlan['plan_name']) ?></h6>
                                        <p class="text-muted small mb-2">
                                            Diyetisyen: <?= clean($activePlan['dietitian_name']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <small class="text-muted">
                                                <i class="far fa-calendar me-1"></i>
                                                <?= date('d.m.Y', strtotime($activePlan['start_date'])) ?> -
                                                <?= date('d.m.Y', strtotime($activePlan['end_date'])) ?>
                                            </small>
                                        </p>
                                        <p class="mb-0 small"><?= nl2br(clean(substr($activePlan['description'], 0, 100))) ?>...</p>
                                        <a href="/client/diet-plans.php?id=<?= $activePlan['id'] ?>"
                                           class="btn btn-sm btn-success w-100 mt-3">
                                            Detayları Görüntüle
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (count($weightHistory) > 0): ?>
    <script>
        // Weight tracking chart
        const weightData = <?= json_encode(array_reverse(array_map(function($w) {
            return [
                'date' => date('d.m', strtotime($w['measurement_date'])),
                'weight' => (float)$w['weight']
            ];
        }, $weightHistory))) ?>;

        const ctx = document.getElementById('weightChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: weightData.map(d => d.date),
                datasets: [{
                    label: 'Kilo (kg)',
                    data: weightData.map(d => d.weight),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
