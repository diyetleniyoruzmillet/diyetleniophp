<?php
/**
 * Diyetlenio - Diyetisyen Danışanlarım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece diyetisyen erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$search = trim($_GET['search'] ?? '');

// Danışanları çek (randevusu olan)
$whereClause = "WHERE a.dietitian_id = ?";
$params = [$userId];

if (!empty($search)) {
    $whereClause .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email, u.phone, u.created_at,
           cp.date_of_birth, cp.gender, cp.height, cp.target_weight,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ? AND status = 'completed') as completed_sessions,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ? AND status = 'scheduled') as upcoming_sessions,
           (SELECT COUNT(*) FROM diet_plans WHERE client_id = u.id AND dietitian_id = ? AND status = 'active') as active_plans,
           (SELECT weight FROM weight_tracking WHERE client_id = u.id ORDER BY measurement_date DESC LIMIT 1) as current_weight
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    {$whereClause}
    ORDER BY u.full_name ASC
");
$stmt->execute(array_merge([$userId, $userId, $userId], $params));
$clients = $stmt->fetchAll();

$pageTitle = 'Danışanlarım';
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
        .client-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .client-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .client-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-badge {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link active" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevular
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

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Danışanlarım (<?= count($clients) ?>)</h2>
                        <form method="GET" class="d-flex" style="width: 400px;">
                            <input type="text" name="search" class="form-control me-2"
                                   placeholder="İsim veya email ile ara..." value="<?= clean($search) ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>

                    <?php if (count($clients) === 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Henüz danışanınız bulunmuyor</h4>
                            <p class="text-muted">Randevu alan danışanlarınız burada görünecektir.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($clients as $client): ?>
                            <div class="client-card">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <div class="client-avatar mx-auto">
                                            <?= strtoupper(mb_substr($client['full_name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?= clean($client['full_name']) ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-envelope me-2"></i><?= clean($client['email']) ?>
                                        </p>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-phone me-2"></i><?= clean($client['phone']) ?>
                                        </p>
                                        <?php if ($client['gender']): ?>
                                            <span class="badge bg-light text-dark">
                                                <?= $client['gender'] === 'male' ? 'Erkek' : ($client['gender'] === 'female' ? 'Kadın' : 'Diğer') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="stat-badge">
                                                    <div class="text-success fw-bold h4 mb-0"><?= $client['completed_sessions'] ?></div>
                                                    <small class="text-muted">Tamamlanan</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-badge">
                                                    <div class="text-primary fw-bold h4 mb-0"><?= $client['upcoming_sessions'] ?></div>
                                                    <small class="text-muted">Yaklaşan</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-badge">
                                                    <div class="text-warning fw-bold h4 mb-0"><?= $client['active_plans'] ?></div>
                                                    <small class="text-muted">Aktif Plan</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-badge">
                                                    <div class="text-info fw-bold h4 mb-0">
                                                        <?= $client['current_weight'] ? number_format($client['current_weight'], 0) : '-' ?>
                                                    </div>
                                                    <small class="text-muted">kg</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="/dietitian/client-detail.php?id=<?= $client['id'] ?>" class="btn btn-outline-success btn-sm mb-2 w-100">
                                            <i class="fas fa-eye me-2"></i>Detay
                                        </a>
                                        <a href="/dietitian/appointments.php?client_id=<?= $client['id'] ?>" class="btn btn-outline-primary btn-sm mb-2 w-100">
                                            <i class="fas fa-calendar me-2"></i>Randevular
                                        </a>
                                        <a href="/dietitian/messages.php?client_id=<?= $client['id'] ?>" class="btn btn-outline-info btn-sm w-100">
                                            <i class="fas fa-envelope me-2"></i>Mesaj Gönder
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
