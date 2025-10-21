<?php
/**
 * Diyetlenio - Danışan Diyet Planları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'active';
$planId = $_GET['id'] ?? null;

// Diyet planlarını çek
$whereClause = "WHERE dp.client_id = ?";
$params = [$userId];

if ($status === 'active') {
    $whereClause .= " AND dp.is_active = 1";
} elseif ($status === 'past') {
    $whereClause .= " AND dp.is_active = 0";
}

$stmt = $conn->prepare("
    SELECT dp.*, u.full_name as dietitian_name, dpr.title as dietitian_title
    FROM diet_plans dp
    INNER JOIN users u ON dp.dietitian_id = u.id
    INNER JOIN dietitian_profiles dpr ON u.id = dpr.user_id
    {$whereClause}
    ORDER BY dp.created_at DESC
");
$stmt->execute($params);
$plans = $stmt->fetchAll();

// Seçili planın detaylarını çek
$selectedPlan = null;
$planMeals = [];
if ($planId) {
    $stmt = $conn->prepare("
        SELECT dp.*, u.full_name as dietitian_name, dpr.title as dietitian_title,
               dpr.phone as dietitian_phone
        FROM diet_plans dp
        INNER JOIN users u ON dp.dietitian_id = u.id
        INNER JOIN dietitian_profiles dpr ON u.id = dpr.user_id
        WHERE dp.id = ? AND dp.client_id = ?
    ");
    $stmt->execute([$planId, $userId]);
    $selectedPlan = $stmt->fetch();

    if ($selectedPlan) {
        // Plan öğünlerini çek
        $stmt = $conn->prepare("
            SELECT * FROM plan_meals
            WHERE plan_id = ?
            ORDER BY day_of_week, meal_time
        ");
        $stmt->execute([$planId]);
        $planMeals = $stmt->fetchAll();
    }
}

$pageTitle = 'Diyet Planlarım';
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
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #28a745;
        }
        .plan-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .plan-card.active {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .meal-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .day-section {
            margin-bottom: 30px;
        }
        .day-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .meal-time-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .breakfast { background: #fff3cd; color: #856404; }
        .lunch { background: #d1ecf1; color: #0c5460; }
        .dinner { background: #f8d7da; color: #721c24; }
        .snack { background: #d4edda; color: #155724; }
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
                        <a class="nav-link" href="/client/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/client/dietitians.php">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenler
                        </a>
                        <a class="nav-link" href="/client/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link active" href="/client/diet-plans.php">
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

            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Diyet Planlarım</h2>
                        <div class="btn-group">
                            <a href="?status=active" class="btn <?= $status === 'active' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="fas fa-check-circle me-2"></i>Aktif Planlar
                            </a>
                            <a href="?status=past" class="btn <?= $status === 'past' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="fas fa-history me-2"></i>Geçmiş Planlar
                            </a>
                            <a href="?status=all" class="btn <?= $status === 'all' ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="fas fa-list me-2"></i>Tümü
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Plans List -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3">
                                        <i class="fas fa-list text-success me-2"></i>Planlarım
                                    </h5>
                                    <?php if (count($plans) === 0): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Henüz diyet planınız yok</p>
                                            <a href="/client/dietitians.php" class="btn btn-success btn-sm">
                                                <i class="fas fa-search me-2"></i>Diyetisyen Bul
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($plans as $plan): ?>
                                            <a href="?id=<?= $plan['id'] ?>&status=<?= $status ?>" class="text-decoration-none">
                                                <div class="plan-card <?= $plan['is_active'] ? 'active' : '' ?>">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="mb-0"><?= clean($plan['plan_name']) ?></h6>
                                                        <?php if ($plan['is_active']): ?>
                                                            <span class="badge bg-warning">Aktif</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-muted small mb-2">
                                                        <i class="fas fa-user-md me-1"></i>
                                                        <?= clean($plan['dietitian_name']) ?>
                                                    </p>
                                                    <div class="small">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d.m.Y', strtotime($plan['start_date'])) ?>
                                                        -
                                                        <?= date('d.m.Y', strtotime($plan['end_date'])) ?>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Plan Details -->
                        <div class="col-md-8">
                            <?php if ($selectedPlan): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h4><?= clean($selectedPlan['plan_name']) ?></h4>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-user-md me-2"></i>
                                                    <?= clean($selectedPlan['dietitian_name']) ?> - <?= clean($selectedPlan['dietitian_title']) ?>
                                                </p>
                                            </div>
                                            <?php if ($selectedPlan['is_active']): ?>
                                                <span class="badge bg-warning text-dark">Aktif Plan</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Başlangıç Tarihi</small>
                                                <strong><?= date('d.m.Y', strtotime($selectedPlan['start_date'])) ?></strong>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Bitiş Tarihi</small>
                                                <strong><?= date('d.m.Y', strtotime($selectedPlan['end_date'])) ?></strong>
                                            </div>
                                        </div>

                                        <?php if ($selectedPlan['description']): ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Plan Açıklaması:</strong><br>
                                                <?= nl2br(clean($selectedPlan['description'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($selectedPlan['daily_calories']): ?>
                                            <div class="row text-center mb-3">
                                                <div class="col-md-3">
                                                    <div class="border rounded p-3">
                                                        <h4 class="text-success mb-0"><?= $selectedPlan['daily_calories'] ?></h4>
                                                        <small class="text-muted">Kalori/Gün</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="border rounded p-3">
                                                        <h4 class="text-primary mb-0"><?= $selectedPlan['daily_protein'] ?>g</h4>
                                                        <small class="text-muted">Protein</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="border rounded p-3">
                                                        <h4 class="text-warning mb-0"><?= $selectedPlan['daily_carbs'] ?>g</h4>
                                                        <small class="text-muted">Karbonhidrat</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="border rounded p-3">
                                                        <h4 class="text-danger mb-0"><?= $selectedPlan['daily_fat'] ?>g</h4>
                                                        <small class="text-muted">Yağ</small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Meals by Day -->
                                <?php
                                $days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                                $mealsByDay = [];
                                foreach ($planMeals as $meal) {
                                    $mealsByDay[$meal['day_of_week']][] = $meal;
                                }
                                ?>

                                <?php if (count($planMeals) > 0): ?>
                                    <?php foreach ($days as $dayIndex => $dayName): ?>
                                        <?php if (isset($mealsByDay[$dayIndex + 1])): ?>
                                            <div class="day-section">
                                                <div class="day-header">
                                                    <h5 class="mb-0">
                                                        <i class="fas fa-calendar-day me-2"></i><?= $dayName ?>
                                                    </h5>
                                                </div>

                                                <?php foreach ($mealsByDay[$dayIndex + 1] as $meal): ?>
                                                    <div class="meal-card">
                                                        <?php
                                                        $mealTypes = [
                                                            'breakfast' => ['Kahvaltı', 'breakfast'],
                                                            'lunch' => ['Öğle Yemeği', 'lunch'],
                                                            'dinner' => ['Akşam Yemeği', 'dinner'],
                                                            'snack' => ['Ara Öğün', 'snack']
                                                        ];
                                                        $mealType = $mealTypes[$meal['meal_time']] ?? ['Öğün', 'snack'];
                                                        ?>
                                                        <span class="meal-time-badge <?= $mealType[1] ?>">
                                                            <?= $mealType[0] ?>
                                                        </span>
                                                        <h6><?= clean($meal['meal_name']) ?></h6>
                                                        <p class="mb-2"><?= nl2br(clean($meal['description'])) ?></p>
                                                        <?php if ($meal['calories']): ?>
                                                            <div class="small text-muted">
                                                                <span class="me-3">
                                                                    <i class="fas fa-fire text-danger me-1"></i>
                                                                    <?= $meal['calories'] ?> kcal
                                                                </span>
                                                                <?php if ($meal['protein']): ?>
                                                                    <span class="me-3">P: <?= $meal['protein'] ?>g</span>
                                                                <?php endif; ?>
                                                                <?php if ($meal['carbs']): ?>
                                                                    <span class="me-3">K: <?= $meal['carbs'] ?>g</span>
                                                                <?php endif; ?>
                                                                <?php if ($meal['fat']): ?>
                                                                    <span>Y: <?= $meal['fat'] ?>g</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">Bu plan için henüz öğün eklenmemiş</h5>
                                            <p class="text-muted">Diyetisyeniniz yakında öğün detaylarını ekleyecektir.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-hand-pointer fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">Detayları görmek için bir plan seçin</h5>
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
</body>
</html>
