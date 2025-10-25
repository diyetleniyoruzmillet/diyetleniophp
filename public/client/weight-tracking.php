<?php
/**
 * Diyetlenio - Kilo Takibi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Yeni kayıt ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_weight'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $weight = (float)$_POST['weight'];
        $measurementDate = $_POST['measurement_date'];
        $notes = trim($_POST['notes'] ?? '');

        if ($weight > 0) {
            $stmt = $conn->prepare("
                INSERT INTO weight_tracking (client_id, weight, measurement_date, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $weight, $measurementDate, $notes]);
            setFlash('success', 'Kilo kaydı başarıyla eklendi.');
            redirect('/client/weight-tracking.php');
        }
    }
}

// Kilo geçmişini çek
$stmt = $conn->prepare("
    SELECT * FROM weight_tracking
    WHERE client_id = ?
    ORDER BY measurement_date DESC
");
$stmt->execute([$userId]);
$weightHistory = $stmt->fetchAll();

// Hedef kilo
$stmt = $conn->prepare("SELECT target_weight FROM client_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
$targetWeight = $profile['target_weight'] ?? null;

$pageTitle = 'Kilo Takibi';
include __DIR__ . '/../../includes/client_header.php';
?>

<style>
    .card-custom {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
</style>

<h2 class="mb-4">Kilo Takibi</h2>

<?php if (hasFlash()): ?>
    <?php if ($msg = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= clean($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Add New Weight -->
    <div class="col-md-4">
        <div class="card-custom">
            <h5 class="mb-3">
                <i class="fas fa-plus-circle text-success me-2"></i>Yeni Kayıt
            </h5>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Kilo (kg) *</label>
                    <input type="number" name="weight" class="form-control"
                           step="0.1" min="0" max="500" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tarih *</label>
                    <input type="date" name="measurement_date" class="form-control"
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control" rows="2"
                              placeholder="Örn: Sabah aç karnına..."></textarea>
                </div>
                <button type="submit" name="add_weight" class="btn btn-success w-100">
                    <i class="fas fa-save me-2"></i>Kaydet
                </button>
            </form>
        </div>
    </div>

    <!-- Chart -->
    <div class="col-md-8">
        <div class="card-custom">
            <h5 class="mb-3">
                <i class="fas fa-chart-line text-primary me-2"></i>Kilo Grafiği
            </h5>
            <?php if (count($weightHistory) > 0): ?>
                <canvas id="weightChart" height="100"></canvas>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Henüz kilo kaydı bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Weight History -->
<div class="card-custom">
    <h5 class="mb-3">
        <i class="fas fa-history text-warning me-2"></i>Kilo Geçmişi
    </h5>
    <?php if (count($weightHistory) === 0): ?>
        <div class="text-center py-4">
            <p class="text-muted">Kilo kaydı bulunmuyor.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kilo (kg)</th>
                        <th>Değişim</th>
                        <th>Notlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $prevWeight = null;
                    foreach ($weightHistory as $record):
                        $diff = $prevWeight ? $record['weight'] - $prevWeight : 0;
                        $prevWeight = $record['weight'];
                    ?>
                        <tr>
                            <td><?= date('d.m.Y', strtotime($record['measurement_date'])) ?></td>
                            <td><strong><?= number_format($record['weight'], 1) ?> kg</strong></td>
                            <td>
                                <?php if ($diff != 0): ?>
                                    <span class="badge bg-<?= $diff < 0 ? 'success' : 'danger' ?>">
                                        <?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 1) ?> kg
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= clean($record['notes']) ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (count($weightHistory) > 0): ?>
    <script>
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
                    borderColor: '#56ab2f',
                    backgroundColor: 'rgba(86, 171, 47, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }<?php if ($targetWeight): ?>,
                {
                    label: 'Hedef',
                    data: Array(weightData.length).fill(<?= $targetWeight ?>),
                    borderColor: '#ffc107',
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0
                }
                <?php endif; ?>]
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
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
