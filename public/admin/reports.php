<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    redirect('/login.php');
}

// İstatistikler
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_clients' => $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'client'")->fetchColumn(),
    'total_dietitians' => $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'dietitian'")->fetchColumn(),
    'total_appointments' => $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
    'total_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn(),
    'monthly_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW())")->fetchColumn(),
];

include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Raporlar ve İstatistikler</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Toplam Kullanıcı</h6>
                    <h2><?= number_format($stats['total_users']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Toplam Randevu</h6>
                    <h2><?= number_format($stats['total_appointments']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Toplam Gelir</h6>
                    <h2><?= number_format($stats['total_revenue'], 2) ?> ₺</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Aylık Gelir</h6>
                    <h2><?= number_format($stats['monthly_revenue'], 2) ?> ₺</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kullanıcı Dağılımı</h5>
                </div>
                <div class="card-body">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aylık Gelir Grafiği</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User Chart
new Chart(document.getElementById('userChart'), {
    type: 'pie',
    data: {
        labels: ['Danışanlar', 'Diyetisyenler', 'Adminler'],
        datasets: [{
            data: [<?= $stats['total_clients'] ?>, <?= $stats['total_dietitians'] ?>, 1],
            backgroundColor: ['#0ea5e9', '#10b981', '#f59e0b']
        }]
    }
});

// Revenue Chart (placeholder)
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz'],
        datasets: [{
            label: 'Gelir (₺)',
            data: [12000, 19000, 15000, 21000, 18000, <?= $stats['monthly_revenue'] ?>],
            borderColor: '#0ea5e9',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
