<?php
/**
 * Dietitian Analytics Dashboard
 * Business analytics and client statistics
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    redirect('/login.php');
}

$user = $auth->user();

// Temel istatistikler
$stats = [];

// Toplam müşteri sayısı
$clientCountStmt = $db->prepare("
    SELECT COUNT(DISTINCT client_id) as total
    FROM appointments
    WHERE dietitian_id = ?
");
$clientCountStmt->execute([$user->getId()]);
$stats['total_clients'] = $clientCountStmt->fetch()['total'];

// Randevu istatistikleri
$appointmentStmt = $db->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments
    WHERE dietitian_id = ?
");
$appointmentStmt->execute([$user->getId()]);
$stats['appointments'] = $appointmentStmt->fetch();

// Toplam gelir
$revenueStmt = $db->prepare("
    SELECT COALESCE(SUM(p.amount), 0) as total
    FROM payments p
    INNER JOIN appointments a ON p.appointment_id = a.id
    WHERE a.dietitian_id = ? AND p.status = 'completed'
");
$revenueStmt->execute([$user->getId()]);
$stats['total_revenue'] = $revenueStmt->fetch()['total'];

// Aylık gelir (son 6 ay)
$monthlyRevenueStmt = $db->prepare("
    SELECT
        DATE_FORMAT(p.created_at, '%Y-%m') as month,
        SUM(p.amount) as revenue
    FROM payments p
    INNER JOIN appointments a ON p.appointment_id = a.id
    WHERE a.dietitian_id = ? AND p.status = 'completed'
    AND p.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlyRevenueStmt->execute([$user->getId()]);
$monthlyRevenue = $monthlyRevenueStmt->fetchAll();

// Aylık randevu sayısı (son 6 ay)
$monthlyApptsStmt = $db->prepare("
    SELECT
        DATE_FORMAT(appointment_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM appointments
    WHERE dietitian_id = ?
    AND appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyApptsStmt->execute([$user->getId()]);
$monthlyAppts = $monthlyApptsStmt->fetchAll();

// En aktif saatler
$hourlyStmt = $db->prepare("
    SELECT
        HOUR(appointment_time) as hour,
        COUNT(*) as count
    FROM appointments
    WHERE dietitian_id = ?
    GROUP BY HOUR(appointment_time)
    ORDER BY hour ASC
");
$hourlyStmt->execute([$user->getId()]);
$hourlyData = $hourlyStmt->fetchAll();

// Ortalama değerlendirme
$ratingStmt = $db->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
    FROM reviews
    WHERE dietitian_id = ?
");
$ratingStmt->execute([$user->getId()]);
$ratingData = $ratingStmt->fetch();

include __DIR__ . '/../../includes/dietitian_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">İş Analizi</h1>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Toplam Müşteri</h6>
                            <h2 class="mb-0"><?= $stats['total_clients'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Toplam Randevu</h6>
                            <h2 class="mb-0"><?= $stats['appointments']['total'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Toplam Gelir</h6>
                            <h2 class="mb-0"><?= number_format($stats['total_revenue'], 0) ?> ₺</h2>
                        </div>
                        <div>
                            <i class="fas fa-lira-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Ortalama Puan</h6>
                            <h2 class="mb-0">
                                <?= number_format($ratingData['avg_rating'] ?? 0, 1) ?>
                                <small class="fs-6">/5.0</small>
                            </h2>
                            <small><?= $ratingData['total_reviews'] ?> değerlendirme</small>
                        </div>
                        <div>
                            <i class="fas fa-star fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Aylık Gelir Trendi (Son 6 Ay)</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Randevu Durumları</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Aylık Randevu Sayısı</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyApptsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Saatlik Randevu Dağılımı</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

// Revenue Chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: [<?php
            foreach ($monthlyRevenue as $m) {
                list($year, $month) = explode('-', $m['month']);
                echo "'" . $months[(int)$month - 1] . " " . $year . "',";
            }
        ?>],
        datasets: [{
            label: 'Gelir (₺)',
            data: [<?php foreach ($monthlyRevenue as $m) echo $m['revenue'] . ','; ?>],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Gelir: ' + context.parsed.y.toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('tr-TR') + ' ₺';
                    }
                }
            }
        }
    }
});

// Status Pie Chart
new Chart(document.getElementById('statusPieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Tamamlanan', 'Onaylı', 'Bekleyen', 'İptal'],
        datasets: [{
            data: [
                <?= $stats['appointments']['completed'] ?>,
                <?= $stats['appointments']['confirmed'] ?>,
                <?= $stats['appointments']['pending'] ?>,
                <?= $stats['appointments']['cancelled'] ?>
            ],
            backgroundColor: ['#10b981', '#0ea5e9', '#f59e0b', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 15, font: { size: 11 } }
            }
        }
    }
});

// Monthly Appointments Chart
new Chart(document.getElementById('monthlyApptsChart'), {
    type: 'bar',
    data: {
        labels: [<?php
            foreach ($monthlyAppts as $m) {
                list($year, $month) = explode('-', $m['month']);
                echo "'" . $months[(int)$month - 1] . "',";
            }
        ?>],
        datasets: [{
            label: 'Randevu Sayısı',
            data: [<?php foreach ($monthlyAppts as $m) echo $m['count'] . ','; ?>],
            backgroundColor: '#0ea5e9',
            borderRadius: 8,
            barThickness: 40
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Hourly Distribution Chart
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: [<?php
            for ($h = 9; $h <= 18; $h++) {
                echo "'{$h}:00',";
            }
        ?>],
        datasets: [{
            label: 'Randevu Sayısı',
            data: [<?php
                for ($h = 9; $h <= 18; $h++) {
                    $count = 0;
                    foreach ($hourlyData as $hd) {
                        if ($hd['hour'] == $h) {
                            $count = $hd['count'];
                            break;
                        }
                    }
                    echo $count . ',';
                }
            ?>],
            backgroundColor: '#f59e0b',
            borderRadius: 8,
            barThickness: 30
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/dietitian_footer.php'; ?>
