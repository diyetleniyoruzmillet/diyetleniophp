<?php
/**
 * Client Analytics Dashboard
 * Personal health and progress tracking
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    redirect('/login.php');
}

$user = $auth->user();

// Kilo takibi verileri (son 30 gün)
$weightStmt = $db->prepare("
    SELECT weight, measurement_date
    FROM weight_tracking
    WHERE client_id = ?
    AND measurement_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY measurement_date ASC
");
$weightStmt->execute([$user->getId()]);
$weightData = $weightStmt->fetchAll();

// Randevu istatistikleri
$appointmentStats = $db->prepare("
    SELECT
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments
    WHERE client_id = ?
");
$appointmentStats->execute([$user->getId()]);
$apptStats = $appointmentStats->fetch();

// Aylık randevu dağılımı (son 6 ay)
$monthlyAppointments = $db->prepare("
    SELECT
        DATE_FORMAT(appointment_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM appointments
    WHERE client_id = ?
    AND appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyAppointments->execute([$user->getId()]);
$monthlyData = $monthlyAppointments->fetchAll();

// Toplam harcama
$totalSpent = $db->prepare("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE user_id = ? AND status = 'completed'
");
$totalSpent->execute([$user->getId()]);
$spending = $totalSpent->fetch();

// Kilo değişimi
$weightChange = 0;
if (count($weightData) >= 2) {
    $firstWeight = $weightData[0]['weight'];
    $lastWeight = $weightData[count($weightData) - 1]['weight'];
    $weightChange = $lastWeight - $firstWeight;
}

include __DIR__ . '/../../includes/client_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">İlerleme Analizi</h1>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Toplam Randevu</h6>
                            <h2 class="mb-0"><?= $apptStats['total_appointments'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
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
                            <h6 class="mb-1">Tamamlanan</h6>
                            <h2 class="mb-0"><?= $apptStats['completed'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
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
                            <h6 class="mb-1">Kilo Değişimi</h6>
                            <h2 class="mb-0">
                                <?= $weightChange > 0 ? '+' : '' ?><?= number_format($weightChange, 1) ?> kg
                            </h2>
                        </div>
                        <div>
                            <i class="fas fa-weight fa-3x opacity-50"></i>
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
                            <h6 class="mb-1">Toplam Harcama</h6>
                            <h2 class="mb-0"><?= number_format($spending['total'], 0) ?> ₺</h2>
                        </div>
                        <div>
                            <i class="fas fa-lira-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <!-- Weight Progress Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Kilo Takibi (Son 30 Gün)</h5>
                </div>
                <div class="card-body">
                    <canvas id="weightChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Appointment Distribution -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Randevu Dağılımı</h5>
                </div>
                <div class="card-body">
                    <canvas id="appointmentPieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Appointments Chart -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Aylık Randevu Sayısı (Son 6 Ay)</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="60"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Weight Chart
const weightCtx = document.getElementById('weightChart').getContext('2d');
new Chart(weightCtx, {
    type: 'line',
    data: {
        labels: [<?php foreach ($weightData as $w) echo "'" . date('d.m', strtotime($w['measurement_date'])) . "',"; ?>],
        datasets: [{
            label: 'Kilo (kg)',
            data: [<?php foreach ($weightData as $w) echo $w['weight'] . ','; ?>],
            borderColor: '#0ea5e9',
            backgroundColor: 'rgba(14, 165, 233, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#0ea5e9',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                callbacks: {
                    label: function(context) {
                        return 'Kilo: ' + context.parsed.y + ' kg';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return value + ' kg';
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Appointment Pie Chart
const pieCtx = document.getElementById('appointmentPieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: ['Tamamlanan', 'İptal'],
        datasets: [{
            data: [<?= $apptStats['completed'] ?>, <?= $apptStats['cancelled'] ?>],
            backgroundColor: ['#10b981', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            }
        }
    }
});

// Monthly Appointments Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: [<?php
            $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
            foreach ($monthlyData as $m) {
                list($year, $month) = explode('-', $m['month']);
                echo "'" . $months[(int)$month - 1] . " " . $year . "',";
            }
        ?>],
        datasets: [{
            label: 'Randevu Sayısı',
            data: [<?php foreach ($monthlyData as $m) echo $m['count'] . ','; ?>],
            backgroundColor: '#0ea5e9',
            borderRadius: 8,
            barThickness: 40
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/client_footer.php'; ?>
