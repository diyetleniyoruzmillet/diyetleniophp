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
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Modern Sidebar with Glassmorphism */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            box-shadow: 4px 0 30px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: pulse 4s ease-in-out infinite;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .sidebar-subtitle {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 14px 20px;
            margin: 6px 0;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
            min-width: 20px;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.25);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            font-weight: 600;
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: white;
            border-radius: 0 4px 4px 0;
        }

        .sidebar .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Modern Content Area */
        .content-wrapper {
            padding: 35px;
            position: relative;
        }

        /* Page Header with Glassmorphism */
        .page-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease;
        }

        .page-header h2 {
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .page-header .text-muted {
            color: #718096 !important;
            font-weight: 400;
        }

        /* Ultra-Modern Stat Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
            animation: fadeInUp 0.6s ease both;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.08) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-card-1 { animation-delay: 0.1s; }
        .stat-card-2 { animation-delay: 0.2s; }
        .stat-card-3 { animation-delay: 0.3s; }
        .stat-card-4 { animation-delay: 0.4s; }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            transition: all 0.4s;
            position: relative;
            z-index: 1;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        /* Icon Gradient Backgrounds */
        .icon-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .icon-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
        }

        .icon-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
        }

        .icon-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
        }

        /* Modern Table Card */
        .table-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.5);
            animation: fadeInUp 0.6s ease 0.5s both;
        }

        .table-card h5 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border: none;
            color: #2d3748;
            font-weight: 600;
            padding: 15px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border: none;
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
        }

        .badge-pending {
            background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
            color: #fff;
        }

        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        /* Alerts */
        .alert {
            border-radius: 15px;
            border: none;
            backdrop-filter: blur(10px);
            animation: fadeInDown 0.5s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
            }

            .page-header {
                padding: 20px;
            }

            .stat-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="sidebar-brand">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <p class="sidebar-subtitle mb-4">Admin Panel</p>

                    <nav class="nav flex-column">
                        <a class="nav-link active" href="/admin/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link" href="/admin/users.php">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </a>
                        <a class="nav-link" href="/admin/dietitians.php">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenler
                            <?php if ($pendingDietitians > 0): ?>
                                <span class="badge bg-warning text-dark"><?= $pendingDietitians ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="/admin/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevular
                        </a>
                        <a class="nav-link" href="/admin/articles.php">
                            <i class="fas fa-newspaper me-2"></i>Blog Yazıları
                        </a>
                        <a class="nav-link" href="/admin/recipes.php">
                            <i class="fas fa-utensils me-2"></i>Tarifler
                        </a>
                        <a class="nav-link" href="/admin/settings.php">
                            <i class="fas fa-cog me-2"></i>Ayarlar
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
