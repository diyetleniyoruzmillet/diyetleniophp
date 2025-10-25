<?php
/**
 * Diyetlenio - Diyetisyen Diyet Planları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Plan oluşturma veya güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_plan'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $clientId = (int)$_POST['client_id'];
        $planName = trim($_POST['plan_name']);
        $description = trim($_POST['description']);
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $dailyCalories = (int)$_POST['daily_calories'];
        $dailyProtein = (int)$_POST['daily_protein'];
        $dailyCarbs = (int)$_POST['daily_carbs'];
        $dailyFat = (int)$_POST['daily_fat'];

        try {
            // Danışanın başka aktif planı varsa pasif yap
            $stmt = $conn->prepare("
                UPDATE diet_plans SET is_active = 0
                WHERE client_id = ? AND is_active = 1
            ");
            $stmt->execute([$clientId]);

            // Yeni plan oluştur
            $stmt = $conn->prepare("
                INSERT INTO diet_plans (
                    client_id, dietitian_id, plan_name, description,
                    start_date, end_date, daily_calories, daily_protein,
                    daily_carbs, daily_fat, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $clientId, $userId, $planName, $description,
                $startDate, $endDate, $dailyCalories, $dailyProtein,
                $dailyCarbs, $dailyFat
            ]);

            setFlash('success', 'Diyet planı başarıyla oluşturuldu.');
            redirect('/dietitian/diet-plans.php');
        } catch (Exception $e) {
            setFlash('error', 'Plan oluşturulurken bir hata oluştu.');
        }
    }
}

// Planları listele
$stmt = $conn->prepare("
    SELECT dp.*, u.full_name as client_name
    FROM diet_plans dp
    INNER JOIN users u ON dp.client_id = u.id
    WHERE dp.dietitian_id = ?
    ORDER BY dp.is_active DESC, dp.created_at DESC
");
$stmt->execute([$userId]);
$plans = $stmt->fetchAll();

// Danışanları listele (plan oluşturmak için)
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT client_id FROM appointments
        WHERE dietitian_id = ? AND status = 'completed'
    )
    ORDER BY u.full_name
");
$stmt->execute([$userId]);
$clients = $stmt->fetchAll();

$pageTitle = 'Diyet Planları';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .plan-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #f093fb;
    }
    .plan-card.active {
        border-left-color: #ffc107;
        background: #fffbf0;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Diyet Planları</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPlanModal">
        <i class="fas fa-plus me-2"></i>Yeni Plan Oluştur
    </button>
</div>

<?php if (count($plans) === 0): ?>
    <div class="text-center py-5">
        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">Henüz diyet planı oluşturmadınız</h4>
        <p class="text-muted">Danışanlarınız için diyet planı oluşturarak başlayın.</p>
    </div>
<?php else: ?>
    <?php foreach ($plans as $plan): ?>
        <div class="plan-card <?= $plan['is_active'] ? 'active' : '' ?>">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <?= clean($plan['plan_name']) ?>
                        <?php if ($plan['is_active']): ?>
                            <span class="badge bg-warning text-dark ms-2">Aktif</span>
                        <?php endif; ?>
                    </h5>
                    <p class="text-muted mb-2">
                        <i class="fas fa-user me-2"></i><?= clean($plan['client_name']) ?>
                    </p>
                    <p class="mb-2 small"><?= clean($plan['description']) ?></p>
                    <p class="mb-0 small text-muted">
                        <i class="far fa-calendar me-2"></i>
                        <?= date('d.m.Y', strtotime($plan['start_date'])) ?> - <?= date('d.m.Y', strtotime($plan['end_date'])) ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Kalori</small>
                            <strong><?= $plan['daily_calories'] ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Protein</small>
                            <strong><?= $plan['daily_protein'] ?>g</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Karbonhidrat</small>
                            <strong><?= $plan['daily_carbs'] ?>g</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Yağ</small>
                            <strong><?= $plan['daily_fat'] ?>g</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Create Plan Modal -->
<div class="modal fade" id="createPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Diyet Planı Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label">Danışan *</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= clean($client['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Plan Adı *</label>
                        <input type="text" name="plan_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarihi *</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bitiş Tarihi *</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Günlük Kalori *</label>
                            <input type="number" name="daily_calories" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Protein (g) *</label>
                            <input type="number" name="daily_protein" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Karbonhidrat (g) *</label>
                            <input type="number" name="daily_carbs" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Yağ (g) *</label>
                            <input type="number" name="daily_fat" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="create_plan" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Oluştur
                    </button>
                </div>
            </form>
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
