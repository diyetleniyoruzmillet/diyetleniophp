<?php
/**
 * Diyetlenio - Diyetisyen Raporlar ve İstatistikler
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Genel İstatistikler
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT client_id) as total_clients,
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
    FROM appointments
    WHERE dietitian_id = ?
");
$stmt->execute([$userId]);
$appointmentStats = $stmt->fetch();

// Aylık Gelir
$stmt = $conn->prepare("
    SELECT SUM(amount) as monthly_income
    FROM payments
    WHERE dietitian_id = ? AND status = 'completed'
    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
    AND YEAR(payment_date) = YEAR(CURRENT_DATE())
");
$stmt->execute([$userId]);
$monthlyIncome = $stmt->fetch()['monthly_income'] ?? 0;

// Aktif Diyet Planları
$stmt = $conn->prepare("
    SELECT COUNT(*) as active_plans
    FROM diet_plans
    WHERE dietitian_id = ? AND is_active = 1
");
$stmt->execute([$userId]);
$activePlans = $stmt->fetch()['active_plans'];

// Ortalama Puan
$stmt = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
    FROM reviews
    WHERE dietitian_id = ?
");
$stmt->execute([$userId]);
$reviewStats = $stmt->fetch();

// Aylık Randevu Grafiği (Son 6 ay)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM appointments
        WHERE dietitian_id = ?
        AND DATE_FORMAT(appointment_date, '%Y-%m') = ?
    ");
    $stmt->execute([$userId, $date]);
    $monthlyData[] = [
        'month' => date('F', strtotime($date . '-01')),
        'count' => $stmt->fetch()['count']
    ];
}

// En Çok Randevu Alan Danışanlar
$stmt = $conn->prepare("
    SELECT u.full_name, COUNT(*) as appointment_count
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    WHERE a.dietitian_id = ?
    GROUP BY a.client_id
    ORDER BY appointment_count DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$topClients = $stmt->fetchAll();

$pageTitle = 'Raporlar ve İstatistikler';
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
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
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
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link" href="/dietitian/availability.php">
                            <i class="fas fa-clock me-2"></i>Müsaitlik
                        </a>
                        <a class="nav-link" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
                        </a>
                        <a class="nav-link" href="/dietitian/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link active" href="/dietitian/reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Raporlar
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
                    <h2 class="mb-4">Raporlar ve İstatistikler</h2>

                    <!-- Stats Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-users stat-icon text-primary"></i>
                                <h2><?= number_format($appointmentStats['total_clients']) ?></h2>
                                <p class="text-muted mb-0">Toplam Danışan</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-calendar-check stat-icon text-success"></i>
                                <h2><?= number_format($appointmentStats['completed_appointments']) ?></h2>
                                <p class="text-muted mb-0">Tamamlanan Randevu</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-clipboard-list stat-icon text-warning"></i>
                                <h2><?= number_format($activePlans) ?></h2>
                                <p class="text-muted mb-0">Aktif Diyet Planı</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-lira-sign stat-icon text-info"></i>
                                <h2><?= number_format($monthlyIncome, 2) ?> ₺</h2>
                                <p class="text-muted mb-0">Bu Ay Gelir</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Monthly Chart -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">
                                        <i class="fas fa-chart-line text-success me-2"></i>
                                        Son 6 Ay Randevu Grafiği
                                    </h5>
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Rating & Top Clients -->
                        <div class="col-md-4">
                            <!-- Rating -->
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Ortalama Puan</h5>
                                    <div class="display-3 text-warning my-3">
                                        <?= number_format($reviewStats['avg_rating'] ?? 0, 1) ?>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="text-muted mb-0"><?= $reviewStats['review_count'] ?> değerlendirme</p>
                                </div>
                            </div>

                            <!-- Top Clients -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="fas fa-trophy text-warning me-2"></i>
                                        En Aktif Danışanlar
                                    </h5>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($topClients as $index => $client): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong><?= $index + 1 ?>.</strong>
                                                    <?= clean($client['full_name']) ?>
                                                </span>
                                                <span class="badge bg-success rounded-pill">
                                                    <?= $client['appointment_count'] ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Appointments Chart
        const monthlyData = <?= json_encode($monthlyData) ?>;
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Randevu Sayısı',
                    data: monthlyData.map(d => d.count),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
