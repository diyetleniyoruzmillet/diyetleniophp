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
            $error = 'Plan oluşturulurken bir hata oluştu.';
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
        .plan-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #28a745;
        }
        .plan-card.active {
            border-left-color: #ffc107;
            background: #fffbf0;
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
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link" href="/dietitian/availability.php">
                            <i class="fas fa-clock me-2"></i>Müsaitlik
                        </a>
                        <a class="nav-link active" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
                        </a>
                        <a class="nav-link" href="/dietitian/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
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
                        <h2>Diyet Planları</h2>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newPlanModal">
                            <i class="fas fa-plus me-2"></i>Yeni Plan Oluştur
                        </button>
                    </div>

                    <?php if (hasFlash()): ?>
                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (count($plans) === 0): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Henüz diyet planı oluşturmadınız</h4>
                                <p class="text-muted">Danışanlarınız için özel diyet planları oluşturun.</p>
                                <button type="button" class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#newPlanModal">
                                    <i class="fas fa-plus me-2"></i>İlk Planı Oluştur
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($plans as $plan): ?>
                                <div class="col-md-6">
                                    <div class="plan-card <?= $plan['is_active'] ? 'active' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1"><?= clean($plan['plan_name']) ?></h5>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?= clean($plan['client_name']) ?>
                                                </p>
                                            </div>
                                            <?php if ($plan['is_active']): ?>
                                                <span class="badge bg-warning text-dark">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($plan['description']): ?>
                                            <p class="text-muted small mb-3">
                                                <?= clean(substr($plan['description'], 0, 100)) ?>
                                                <?= strlen($plan['description']) > 100 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Başlangıç</small>
                                                <strong><?= date('d.m.Y', strtotime($plan['start_date'])) ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Bitiş</small>
                                                <strong><?= date('d.m.Y', strtotime($plan['end_date'])) ?></strong>
                                            </div>
                                        </div>

                                        <?php if ($plan['daily_calories']): ?>
                                            <div class="row text-center small mb-3">
                                                <div class="col-3">
                                                    <strong class="text-success"><?= $plan['daily_calories'] ?></strong>
                                                    <div class="text-muted">kcal</div>
                                                </div>
                                                <div class="col-3">
                                                    <strong class="text-primary"><?= $plan['daily_protein'] ?>g</strong>
                                                    <div class="text-muted">Protein</div>
                                                </div>
                                                <div class="col-3">
                                                    <strong class="text-warning"><?= $plan['daily_carbs'] ?>g</strong>
                                                    <div class="text-muted">Karb.</div>
                                                </div>
                                                <div class="col-3">
                                                    <strong class="text-danger"><?= $plan['daily_fat'] ?>g</strong>
                                                    <div class="text-muted">Yağ</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-grid gap-2">
                                            <a href="/dietitian/diet-plan-edit.php?id=<?= $plan['id'] ?>" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-edit me-2"></i>Düzenle / Öğün Ekle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Plan Modal -->
    <div class="modal fade" id="newPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle text-success me-2"></i>Yeni Diyet Planı Oluştur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Danışan <span class="text-danger">*</span></label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>">
                                        <?= clean($client['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Plan Adı <span class="text-danger">*</span></label>
                            <input type="text" name="plan_name" class="form-control"
                                   placeholder="Örn: Kilo Verme Programı - Mart 2025" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Plan hakkında notlar..."></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Başlangıç Tarihi <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bitiş Tarihi <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">Günlük Besin Hedefleri</h6>

                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Kalori (kcal)</label>
                                <input type="number" name="daily_calories" class="form-control"
                                       placeholder="1500" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Protein (g)</label>
                                <input type="number" name="daily_protein" class="form-control"
                                       placeholder="100" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Karbonhidrat (g)</label>
                                <input type="number" name="daily_carbs" class="form-control"
                                       placeholder="150" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Yağ (g)</label>
                                <input type="number" name="daily_fat" class="form-control"
                                       placeholder="50" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="create_plan" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Planı Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
