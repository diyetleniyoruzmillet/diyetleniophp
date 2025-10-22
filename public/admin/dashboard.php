<?php
/**
 * Diyetlenio - Admin Dashboard
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$user = $auth->user();

// İstatistikleri çek
try {
    $conn = $db->getConnection();

    // Toplam kullanıcı sayıları
    $stmt = $conn->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admin_count,
            SUM(CASE WHEN user_type = 'dietitian' THEN 1 ELSE 0 END) as dietitian_count,
            SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as client_count,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
        FROM users
    ");
    $userStats = $stmt->fetch();

    // Bekleyen diyetisyen onayları
    $stmt = $conn->query("
        SELECT COUNT(*) as pending_count
        FROM dietitian_profiles
        WHERE is_approved = 0
    ");
    $pendingDietitians = $stmt->fetch()['pending_count'];

    // Randevu istatistikleri
    $stmt = $conn->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today_appointments
        FROM appointments
    ");
    $appointmentStats = $stmt->fetch();

    // Blog ve tarif istatistikleri
    $stmt = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM articles WHERE status = 'approved') as approved_articles,
            (SELECT COUNT(*) FROM articles WHERE status = 'pending') as pending_articles,
            (SELECT COUNT(*) FROM recipes WHERE status = 'approved') as approved_recipes,
            (SELECT COUNT(*) FROM recipes WHERE status = 'pending') as pending_recipes
    ");
    $contentStats = $stmt->fetch();

    // Son kayıt olan kullanıcılar
    $stmt = $conn->query("
        SELECT id, full_name, email, user_type, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll();

    // Bekleyen diyetisyen listesi
    $stmt = $conn->query("
        SELECT u.id, u.full_name, u.email, u.created_at,
               dp.title, dp.specialization, dp.experience_years
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE dp.is_approved = 0
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
    $pendingDietitiansList = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Admin Dashboard Error: ' . $e->getMessage());
    $userStats = $appointmentStats = $contentStats = [];
    $recentUsers = $pendingDietitiansList = [];
}

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <?php include __DIR__ . '/../../includes/admin-styles.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-1">Anasayfa</h2>
                                <p class="text-muted mb-0">Sistem genel görünümü</p>
                            </div>
                            <div>
                                <span class="text-muted">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?= clean($user->getFullName()) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (hasFlash()): ?>
                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="row g-4 mb-4">
                        <!-- Toplam Kullanıcılar -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card stat-card-1">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon icon-primary text-white">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <p class="text-muted mb-1">Toplam Kullanıcı</p>
                                            <h3 class="mb-0"><?= number_format($userStats['total'] ?? 0) ?></h3>
                                            <small class="text-success">
                                                <i class="fas fa-arrow-up"></i>
                                                +<?= $userStats['today_count'] ?? 0 ?> bugün
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Diyetisyenler -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card stat-card-2">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon icon-success text-white">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <p class="text-muted mb-1">Diyetisyenler</p>
                                            <h3 class="mb-0"><?= number_format($userStats['dietitian_count'] ?? 0) ?></h3>
                                            <?php if ($pendingDietitians > 0): ?>
                                                <small class="text-warning">
                                                    <i class="fas fa-clock"></i>
                                                    <?= $pendingDietitians ?> bekliyor
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Danışanlar -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card stat-card-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon icon-info text-white">
                                            <i class="fas fa-user-friends"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <p class="text-muted mb-1">Danışanlar</p>
                                            <h3 class="mb-0"><?= number_format($userStats['client_count'] ?? 0) ?></h3>
                                            <small class="text-muted">
                                                <?= number_format($userStats['active_count'] ?? 0) ?> aktif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Randevular -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card stat-card-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon icon-warning text-white">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <p class="text-muted mb-1">Randevular</p>
                                            <h3 class="mb-0"><?= number_format($appointmentStats['total'] ?? 0) ?></h3>
                                            <small class="text-info">
                                                <i class="fas fa-calendar-day"></i>
                                                <?= $appointmentStats['today_appointments'] ?? 0 ?> bugün
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İçerik İstatistikleri -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                                    <h4><?= $contentStats['approved_articles'] ?? 0 ?></h4>
                                    <p class="text-muted mb-0">Onaylı Makale</p>
                                    <?php if (($contentStats['pending_articles'] ?? 0) > 0): ?>
                                        <small class="badge badge-pending"><?= $contentStats['pending_articles'] ?> bekliyor</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-utensils fa-2x text-success mb-2"></i>
                                    <h4><?= $contentStats['approved_recipes'] ?? 0 ?></h4>
                                    <p class="text-muted mb-0">Onaylı Tarif</p>
                                    <?php if (($contentStats['pending_recipes'] ?? 0) > 0): ?>
                                        <small class="badge badge-pending"><?= $contentStats['pending_recipes'] ?> bekliyor</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <h4><?= $appointmentStats['completed'] ?? 0 ?></h4>
                                    <p class="text-muted mb-0">Tamamlanan Randevu</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <h4><?= $appointmentStats['scheduled'] ?? 0 ?></h4>
                                    <p class="text-muted mb-0">Planlanmış Randevu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Bekleyen Diyetisyen Onayları -->
                        <?php if (count($pendingDietitiansList) > 0): ?>
                        <div class="col-lg-6">
                            <div class="table-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-clock me-2 text-warning"></i>
                                        Bekleyen Diyetisyen Başvuruları
                                    </h5>
                                    <a href="/admin/dietitians.php" class="btn btn-sm btn-outline-primary">
                                        Tümünü Gör
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>İsim</th>
                                                <th>Unvan</th>
                                                <th>Tecrübe</th>
                                                <th>Tarih</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingDietitiansList as $dietitian): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= clean($dietitian['full_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= clean($dietitian['email']) ?></small>
                                                </td>
                                                <td><?= clean($dietitian['title']) ?></td>
                                                <td><?= $dietitian['experience_years'] ?> yıl</td>
                                                <td>
                                                    <small><?= date('d.m.Y', strtotime($dietitian['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <a href="/admin/dietitians.php?view=<?= $dietitian['id'] ?>"
                                                       class="btn btn-sm btn-primary">
                                                        İncele
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Son Kayıt Olan Kullanıcılar -->
                        <div class="col-lg-6">
                            <div class="table-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-plus me-2 text-info"></i>
                                        Son Kayıt Olanlar
                                    </h5>
                                    <a href="/admin/users.php" class="btn btn-sm btn-outline-primary">
                                        Tümünü Gör
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kullanıcı</th>
                                                <th>Tip</th>
                                                <th>Kayıt Tarihi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $recent): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= clean($recent['full_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= clean($recent['email']) ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badges = [
                                                        'admin' => 'danger',
                                                        'dietitian' => 'success',
                                                        'client' => 'primary'
                                                    ];
                                                    $labels = [
                                                        'admin' => 'Admin',
                                                        'dietitian' => 'Diyetisyen',
                                                        'client' => 'Danışan'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?= $badges[$recent['user_type']] ?>">
                                                        <?= $labels[$recent['user_type']] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= date('d.m.Y H:i', strtotime($recent['created_at'])) ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
