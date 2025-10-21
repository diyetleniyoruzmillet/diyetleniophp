<?php
/**
 * Diyetlenio - Advanced Analytics
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
$auth->requireAdmin();

$conn = $db->getConnection();

// Tarih filtreleri
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Ayın ilk günü
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// İstatistikler
$stats = [];

// Toplam kullanıcılar
$stmt = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
$stats['users'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Randevu istatistikleri
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM appointments 
    WHERE appointment_date BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$startDate, $endDate]);
$stats['appointments'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Ödeme istatistikleri
$stmt = $conn->prepare("
    SELECT 
        SUM(amount) as total_revenue,
        AVG(amount) as avg_revenue,
        COUNT(*) as total_payments
    FROM payments
    WHERE created_at BETWEEN ? AND ?
    AND status = 'approved'
");
$stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$stats['revenue'] = $stmt->fetch();

// Günlük randevu grafiği için veri
$stmt = $conn->prepare("
    SELECT DATE(appointment_date) as date, COUNT(*) as count
    FROM appointments
    WHERE appointment_date BETWEEN ? AND ?
    GROUP BY DATE(appointment_date)
    ORDER BY date ASC
");
$stmt->execute([$startDate, $endDate]);
$dailyAppointments = $stmt->fetchAll();

// En aktif diyetisyenler
$stmt = $conn->prepare("
    SELECT u.full_name, COUNT(a.id) as appointment_count
    FROM users u
    INNER JOIN appointments a ON u.id = a.dietitian_id
    WHERE a.appointment_date BETWEEN ? AND ?
    GROUP BY u.id, u.full_name
    ORDER BY appointment_count DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$topDietitians = $stmt->fetchAll();

// Export işlemleri
if (isset($_GET['export'])) {
    if ($_GET['export'] === 'appointments') {
        $stmt = $conn->prepare("
            SELECT a.*, c.full_name as client_name, d.full_name as dietitian_name
            FROM appointments a
            INNER JOIN users c ON a.client_id = c.id
            INNER JOIN users d ON a.dietitian_id = d.id
            WHERE a.appointment_date BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        ExcelExport::exportAppointments($stmt->fetchAll());
    } elseif ($_GET['export'] === 'revenue') {
        $stmt = $conn->prepare("
            SELECT p.*, c.full_name as client_name, d.full_name as dietitian_name
            FROM payments p
            INNER JOIN users c ON p.client_id = c.id
            INNER JOIN users d ON p.dietitian_id = d.id
            WHERE p.created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        ExcelExport::exportPayments($stmt->fetchAll());
    }
}

$pageTitle = 'Analytics';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Diyetlenio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f8f9fa; }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .chart-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-dark text-white p-3">
                <h4>Admin Panel</h4>
                <hr>
                <a href="/admin/dashboard.php" class="btn btn-outline-light w-100 mb-2">Dashboard</a>
                <a href="/admin/analytics.php" class="btn btn-light w-100 mb-2">Analytics</a>
                <a href="/admin/users.php" class="btn btn-outline-light w-100 mb-2">Kullanıcılar</a>
                <a href="/" class="btn btn-outline-light w-100 mt-4">Ana Sayfa</a>
            </div>

            <main class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Analytics & Raporlar</h1>
                    <div class="btn-group">
                        <a href="?export=appointments&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Randevuları İndir
                        </a>
                        <a href="?export=revenue&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-primary">
                            <i class="fas fa-file-excel me-2"></i>Ödemeleri İndir
                        </a>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label>Başlangıç Tarihi</label>
                                <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
                            </div>
                            <div class="col-md-4">
                                <label>Bitiş Tarihi</label>
                                <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-gradient text-white me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Toplam Danışan</h6>
                                    <h3 class="mb-0"><?= $stats['users']['client'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-gradient text-white me-3">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Toplam Diyetisyen</h6>
                                    <h3 class="mb-0"><?= $stats['users']['dietitian'] ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-gradient text-white me-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Toplam Randevu</h6>
                                    <h3 class="mb-0"><?= array_sum($stats['appointments']) ?? 0 ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-gradient text-white me-3">
                                    <i class="fas fa-lira-sign"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Toplam Gelir</h6>
                                    <h3 class="mb-0"><?= number_format($stats['revenue']['total_revenue'] ?? 0, 0) ?> ₺</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5 class="mb-3">Günlük Randevu Grafiği</h5>
                            <canvas id="appointmentChart" height="80"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5 class="mb-3">Randevu Durumları</h5>
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Dietitians -->
                <div class="chart-container">
                    <h5 class="mb-3">En Aktif Diyetisyenler</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Diyetisyen</th>
                                <th>Randevu Sayısı</th>
                                <th>Grafik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topDietitians as $dt): ?>
                                <tr>
                                    <td><?= clean($dt['full_name']) ?></td>
                                    <td><span class="badge bg-primary"><?= $dt['appointment_count'] ?></span></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" style="width: <?= ($dt['appointment_count'] / max(array_column($topDietitians, 'appointment_count'))) * 100 ?>%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Appointments Chart
        const dailyData = <?= json_encode($dailyAppointments) ?>;
        new Chart(document.getElementById('appointmentChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(d => new Date(d.date).toLocaleDateString('tr-TR')),
                datasets: [{
                    label: 'Randevular',
                    data: dailyData.map(d => d.count),
                    borderColor: '#11998e',
                    backgroundColor: 'rgba(17, 153, 142, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });

        // Status Pie Chart
        const statusData = <?= json_encode($stats['appointments']) ?>;
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#11998e', '#fbbf24', '#ef4444', '#8b5cf6']
                }]
            }
        });
    </script>
</body>
</html>
