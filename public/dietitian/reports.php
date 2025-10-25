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
    SELECT COALESCE(SUM(amount), 0) as monthly_income
    FROM payments
    WHERE dietitian_id = ? AND status = 'approved'
    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
    AND YEAR(payment_date) = YEAR(CURRENT_DATE())
");
$stmt->execute([$userId]);
$monthlyIncome = $stmt->fetch()['monthly_income'];

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
        'month' => date('M Y', strtotime($date . '-01')),
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
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<h2 class="mb-4">Raporlar ve İstatistikler</h2>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h3><?= $appointmentStats['total_clients'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Toplam Danışan</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                <h3><?= $appointmentStats['completed_appointments'] ?? 0 ?></h3>
                <p class="text-muted mb-0">Tamamlanan Randevu</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-2x text-info mb-2"></i>
                <h3><?= $activePlans ?></h3>
                <p class="text-muted mb-0">Aktif Plan</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-lira-sign fa-2x text-warning mb-2"></i>
                <h3><?= number_format($monthlyIncome, 2) ?> ₺</h3>
                <p class="text-muted mb-0">Bu Ay Gelir</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Son 6 Ay Randevu Grafiği</h5>
            </div>
            <div class="card-body">
                <canvas id="appointmentChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">En Aktif Danışanlar</h5>
            </div>
            <div class="card-body">
                <?php if (count($topClients) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topClients as $client): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= clean($client['full_name']) ?></span>
                                <span class="badge bg-primary"><?= $client['appointment_count'] ?> randevu</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted text-center">Henüz veri yok</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Değerlendirme Puanı</h5>
            </div>
            <div class="card-body text-center">
                <?php if ($reviewStats['review_count'] > 0): ?>
                    <h2 class="text-warning">
                        <i class="fas fa-star"></i>
                        <?= number_format($reviewStats['avg_rating'], 1) ?>
                    </h2>
                    <p class="text-muted"><?= $reviewStats['review_count'] ?> değerlendirme</p>
                <?php else: ?>
                    <p class="text-muted">Henüz değerlendirme yok</p>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('appointmentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyData, 'month')) ?>,
                datasets: [{
                    label: 'Randevu Sayısı',
                    data: <?= json_encode(array_column($monthlyData, 'count')) ?>,
                    borderColor: '#f093fb',
                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
