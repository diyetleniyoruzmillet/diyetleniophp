<?php
/**
 * Diyetlenio - Admin Kullanıcı Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Kullanıcı durumu değiştirme ve diyetisyen atama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
    } else {
        // Input sanitization
        $userId = sanitizeInt($_POST['user_id'] ?? 0);
        $action = sanitizeString($_POST['action'] ?? '', 50);

        // User ID validation
        if ($userId <= 0) {
            setFlash('error', 'Geçersiz kullanıcı ID.');
            redirect('/admin/users.php');
        }

        // Rate limiting - silme ve kritik işlemler için
        $rateLimiter = new RateLimiter($db);
        if (in_array($action, ['delete', 'deactivate'])) {
            $adminUserId = $auth->user()->getId();
            if ($rateLimiter->tooManyAttempts('admin_critical_action', 'user_' . $adminUserId, 20, 1)) {
                setFlash('error', 'Çok fazla işlem yaptınız. Lütfen bekleyin.');
                redirect('/admin/users.php');
            }
            $rateLimiter->hit(hash('sha256', 'admin_critical_action|user_' . $adminUserId), 1);
        }

        try {
            if ($action === 'activate') {
                $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                setFlash('success', 'Kullanıcı başarıyla aktifleştirildi.');
            } elseif ($action === 'deactivate') {
                $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                setFlash('success', 'Kullanıcı başarıyla deaktif edildi.');
            } elseif ($action === 'delete') {
                // Soft delete - is_active = 0 ve email başına "deleted_" ekle
                $stmt = $conn->prepare("
                    UPDATE users
                    SET is_active = 0,
                        email = CONCAT('deleted_', UNIX_TIMESTAMP(), '_', email)
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                setFlash('success', 'Kullanıcı başarıyla silindi.');
            } elseif ($action === 'assign_dietitian') {
                $dietitianId = sanitizeInt($_POST['dietitian_id'] ?? 0);
                $notes = sanitizeString($_POST['notes'] ?? '', 500);
                $adminId = $auth->user()->getId();

                // Validation
                if ($dietitianId <= 0) {
                    setFlash('error', 'Geçersiz diyetisyen ID.');
                    redirect('/admin/users.php');
                }

                // Önceki atamaları pasif yap
                $stmt = $conn->prepare("
                    UPDATE client_dietitian_assignments
                    SET is_active = 0
                    WHERE client_id = ?
                ");
                $stmt->execute([$userId]);

                // Yeni atama yap
                $stmt = $conn->prepare("
                    INSERT INTO client_dietitian_assignments
                    (client_id, dietitian_id, assigned_by, notes, is_active)
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([$userId, $dietitianId, $adminId, $notes]);

                setFlash('success', 'Diyetisyen başarıyla atandı.');
            } elseif ($action === 'remove_dietitian') {
                // Atamayı pasif yap
                $stmt = $conn->prepare("
                    UPDATE client_dietitian_assignments
                    SET is_active = 0
                    WHERE client_id = ? AND is_active = 1
                ");
                $stmt->execute([$userId]);

                setFlash('success', 'Diyetisyen ataması kaldırıldı.');
            }
        } catch (Exception $e) {
            error_log('User management error: ' . $e->getMessage());
            setFlash('error', 'İşlem sırasında bir hata oluştu.');
        }

        redirect('/admin/users.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    }
}

// Filtreleme ve arama
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$userType = $_GET['user_type'] ?? '';

// Kullanıcıları çek
$whereClause = "WHERE 1=1";
$params = [];

if ($filter === 'active') {
    $whereClause .= " AND u.is_active = 1";
} elseif ($filter === 'inactive') {
    $whereClause .= " AND u.is_active = 0";
}

if (!empty($userType)) {
    $whereClause .= " AND u.user_type = ?";
    $params[] = $userType;
}

if (!empty($search)) {
    $whereClause .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT u.*,
           CASE
               WHEN u.user_type = 'dietitian' THEN (SELECT COUNT(*) FROM appointments WHERE dietitian_id = u.id)
               WHEN u.user_type = 'client' THEN (SELECT COUNT(*) FROM appointments WHERE client_id = u.id)
               ELSE 0
           END as appointment_count,
           CASE
               WHEN u.user_type = 'dietitian' THEN dp.is_approved
               ELSE NULL
           END as is_approved,
           cda.dietitian_id as assigned_dietitian_id,
           ad.full_name as assigned_dietitian_name
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id AND u.user_type = 'dietitian'
    LEFT JOIN client_dietitian_assignments cda ON u.id = cda.client_id AND cda.is_active = 1 AND u.user_type = 'client'
    LEFT JOIN users ad ON cda.dietitian_id = ad.id
    {$whereClause}
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Onaylı diyetisyenleri çek (atama için)
$stmt = $conn->query("
    SELECT u.id, u.full_name, dp.title, dp.specialization
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.user_type = 'dietitian'
    AND u.is_active = 1
    AND dp.is_approved = 1
    ORDER BY u.full_name
");
$availableDietitians = $stmt->fetchAll();

// İstatistikler
$stmt = $conn->query("
    SELECT
        COUNT(*) as total_users,
        SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN user_type = 'dietitian' THEN 1 ELSE 0 END) as dietitian_count,
        SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as client_count,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count
    FROM users
");
$totalStats = $stmt->fetch();

$pageTitle = 'Kullanıcı Yönetimi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../includes/admin-styles.php'; ?>
    <style>
        /* Modern Stats Cards */
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transition: transform 0.5s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }

        .stats-card:hover::before {
            transform: translate(-25%, -25%);
        }

        .stats-card.admin-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
        }

        .stats-card.dietitian-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
        }

        .stats-card.client-card {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 8px 20px rgba(67, 233, 123, 0.3);
        }

        .stats-card.active-card {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            box-shadow: 0 8px 20px rgba(250, 112, 154, 0.3);
        }

        .stats-card.inactive-card {
            background: linear-gradient(135deg, #a8a8a8 0%, #757575 100%);
            box-shadow: 0 8px 20px rgba(168, 168, 168, 0.3);
        }

        .stats-card i {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stats-card p {
            font-size: 0.9rem;
            opacity: 0.95;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Modern Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .page-header h2 {
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 2rem;
        }

        /* Modern Filters */
        .filter-group {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .btn-filter {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn-filter.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Modern Table */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .modern-table {
            margin: 0;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modern-table thead th {
            padding: 18px 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .modern-table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
        }

        /* Modern Badges */
        .badge-modern {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        .badge-admin {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 3px 10px rgba(245, 87, 108, 0.3);
        }

        .badge-dietitian {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 3px 10px rgba(79, 172, 254, 0.3);
        }

        .badge-client {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 3px 10px rgba(67, 233, 123, 0.3);
        }

        /* Modern Buttons */
        .btn-modern {
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-modern-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-modern-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .btn-modern-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .btn-modern-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        /* Modern Modal */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 25px;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 20px 30px;
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box input {
            border-radius: 25px;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .search-box .btn-search {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease;
        }

        /* Empty State */
        .empty-state {
            padding: 80px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 5rem;
            color: #d0d0d0;
            margin-bottom: 20px;
        }

        .empty-state p {
            color: #999;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
                    <!-- Stats -->
                    <div class="row g-3 mb-4 fade-in-up">
                        <div class="col-md-2">
                            <div class="stats-card">
                                <i class="fas fa-users"></i>
                                <h3><?= number_format($totalStats['total_users']) ?></h3>
                                <p class="mb-0">Toplam Kullanıcı</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card admin-card">
                                <i class="fas fa-user-shield"></i>
                                <h3><?= number_format($totalStats['admin_count']) ?></h3>
                                <p class="mb-0">Admin</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card dietitian-card">
                                <i class="fas fa-user-md"></i>
                                <h3><?= number_format($totalStats['dietitian_count']) ?></h3>
                                <p class="mb-0">Diyetisyen</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card client-card">
                                <i class="fas fa-user"></i>
                                <h3><?= number_format($totalStats['client_count']) ?></h3>
                                <p class="mb-0">Danışan</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card active-card">
                                <i class="fas fa-check-circle"></i>
                                <h3><?= number_format($totalStats['active_count']) ?></h3>
                                <p class="mb-0">Aktif</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stats-card inactive-card">
                                <i class="fas fa-times-circle"></i>
                                <h3><?= number_format($totalStats['inactive_count']) ?></h3>
                                <p class="mb-0">Pasif</p>
                            </div>
                        </div>
                    </div>

                    <!-- Page Header -->
                    <div class="page-header fade-in-up">
                        <h2 class="mb-4">
                            <i class="fas fa-users-cog me-3"></i>Kullanıcı Yönetimi
                        </h2>

                        <!-- Filters -->
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="btn-group w-100" role="group">
                                    <a href="?filter=all<?= $userType ? '&user_type=' . $userType : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                                       class="btn btn-filter <?= $filter === 'all' ? 'active' : 'btn-outline-light' ?>">
                                        <i class="fas fa-list me-2"></i>Tümü
                                    </a>
                                    <a href="?filter=active<?= $userType ? '&user_type=' . $userType : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                                       class="btn btn-filter <?= $filter === 'active' ? 'active' : 'btn-outline-light' ?>">
                                        <i class="fas fa-check me-2"></i>Aktif
                                    </a>
                                    <a href="?filter=inactive<?= $userType ? '&user_type=' . $userType : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                                       class="btn btn-filter <?= $filter === 'inactive' ? 'active' : 'btn-outline-light' ?>">
                                        <i class="fas fa-ban me-2"></i>Pasif
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <form method="GET">
                                    <input type="hidden" name="filter" value="<?= $filter ?>">
                                    <?php if ($search): ?>
                                        <input type="hidden" name="search" value="<?= clean($search) ?>">
                                    <?php endif; ?>
                                    <select name="user_type" class="form-select" onchange="this.form.submit()" style="border-radius: 10px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.2); color: white; font-weight: 600;">
                                        <option value="">Tüm Tipler</option>
                                        <option value="admin" <?= $userType === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="dietitian" <?= $userType === 'dietitian' ? 'selected' : '' ?>>Diyetisyen</option>
                                        <option value="client" <?= $userType === 'client' ? 'selected' : '' ?>>Danışan</option>
                                    </select>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="search-box">
                                    <input type="hidden" name="filter" value="<?= $filter ?>">
                                    <?php if ($userType): ?>
                                        <input type="hidden" name="user_type" value="<?= $userType ?>">
                                    <?php endif; ?>
                                    <input type="text"
                                           name="search"
                                           class="form-control"
                                           style="border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.2); color: white; font-weight: 600;"
                                           placeholder="🔍 İsim, email veya telefon ile ara..."
                                           value="<?= clean($search) ?>">
                                    <button type="submit" class="btn btn-search btn-modern-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="?filter=<?= $filter ?><?= $userType ? '&user_type=' . $userType : '' ?>"
                                           class="btn btn-modern-danger"
                                           style="position: absolute; right: 50px; top: 50%; transform: translateY(-50%); border-radius: 50%; width: 40px; height: 40px; padding: 0;">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </form>
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
                        <?php if ($msg = getFlash('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Users Table -->
                    <div class="table-container fade-in-up">
                        <?php if (count($users) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>Kullanıcı bulunamadı</p>
                                <small class="text-muted">Arama kriterlerinizi değiştirmeyi deneyin</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table modern-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Ad Soyad</th>
                                            <th>Email</th>
                                            <th>Telefon</th>
                                            <th>Tip</th>
                                            <th>Durum</th>
                                            <th>Atanmış Diyetisyen</th>
                                            <th>Randevu</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td>
                                                    <strong><?= clean($user['full_name']) ?></strong>
                                                    <?php if ($user['user_type'] === 'dietitian' && $user['is_approved'] === 0): ?>
                                                        <br><small class="badge bg-warning text-dark">Onay Bekliyor</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= clean($user['email']) ?></td>
                                                <td><?= clean($user['phone']) ?></td>
                                                <td>
                                                    <?php
                                                    $badgeClasses = [
                                                        'admin' => 'badge-admin',
                                                        'dietitian' => 'badge-dietitian',
                                                        'client' => 'badge-client'
                                                    ];
                                                    $labels = [
                                                        'admin' => 'Admin',
                                                        'dietitian' => 'Diyetisyen',
                                                        'client' => 'Danışan'
                                                    ];
                                                    $icons = [
                                                        'admin' => 'fa-user-shield',
                                                        'dietitian' => 'fa-user-md',
                                                        'client' => 'fa-user'
                                                    ];
                                                    ?>
                                                    <span class="badge badge-modern <?= $badgeClasses[$user['user_type']] ?>">
                                                        <i class="fas <?= $icons[$user['user_type']] ?> me-1"></i>
                                                        <?= $labels[$user['user_type']] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge badge-modern" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); box-shadow: 0 3px 10px rgba(67, 233, 123, 0.3);">
                                                            <i class="fas fa-check-circle me-1"></i> Aktif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-modern" style="background: linear-gradient(135deg, #a8a8a8 0%, #757575 100%); box-shadow: 0 3px 10px rgba(168, 168, 168, 0.3);">
                                                            <i class="fas fa-times-circle me-1"></i> Pasif
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['user_type'] === 'client'): ?>
                                                        <?php if ($user['assigned_dietitian_id']): ?>
                                                            <span class="badge badge-modern badge-dietitian">
                                                                <i class="fas fa-user-md me-1"></i> <?= clean($user['assigned_dietitian_name']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted" style="font-size: 0.9rem;">Atanmadı</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);">
                                                        <i class="fas fa-calendar-check me-1"></i>
                                                        <?= number_format($user['appointment_count']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= date('d.m.Y', strtotime($user['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['user_type'] === 'client'): ?>
                                                        <div class="btn-group btn-group-sm mb-2">
                                                            <button type="button"
                                                                    class="btn btn-modern btn-modern-primary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#assignDietitianModal<?= $user['id'] ?>"
                                                                    title="<?= $user['assigned_dietitian_id'] ? 'Diyetisyen Değiştir' : 'Diyetisyen Ata' ?>">
                                                                <i class="fas fa-user-md"></i>
                                                            </button>
                                                            <?php if ($user['assigned_dietitian_id']): ?>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Diyetisyen atamasını kaldırmak istediğinize emin misiniz?')">
                                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="remove_dietitian">
                                                                    <button type="submit" class="btn btn-modern" style="background: linear-gradient(135deg, #a8a8a8 0%, #757575 100%); color: white;" title="Atamayi Kaldır">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                        <br>
                                                    <?php endif; ?>
                                                    <?php if ($user['user_type'] !== 'admin'): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($user['is_active']): ?>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı deaktif etmek istediğinize emin misiniz?')">
                                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="deactivate">
                                                                    <button type="submit" class="btn btn-modern btn-modern-warning" title="Deaktif Et">
                                                                        <i class="fas fa-ban"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <button type="submit" class="btn btn-modern btn-modern-success" title="Aktif Et">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')">
                                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-modern btn-modern-danger" title="Sil">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- Diyetisyen Atama Modal -->
                                            <?php if ($user['user_type'] === 'client'): ?>
                                                <div class="modal fade" id="assignDietitianModal<?= $user['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">
                                                                        <i class="fas fa-user-md me-2"></i>
                                                                        <?= $user['assigned_dietitian_id'] ? 'Diyetisyen Değiştir' : 'Diyetisyen Ata' ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="assign_dietitian">

                                                                    <div class="mb-3">
                                                                        <label class="form-label"><strong>Danışan:</strong></label>
                                                                        <p class="text-muted"><?= clean($user['full_name']) ?> (<?= clean($user['email']) ?>)</p>
                                                                    </div>

                                                                    <?php if ($user['assigned_dietitian_name']): ?>
                                                                        <div class="alert alert-info">
                                                                            <strong>Mevcut Diyetisyen:</strong> <?= clean($user['assigned_dietitian_name']) ?>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Diyetisyen Seçin <span class="text-danger">*</span></label>
                                                                        <select name="dietitian_id" class="form-select" required>
                                                                            <option value="">-- Diyetisyen Seçin --</option>
                                                                            <?php foreach ($availableDietitians as $dietitian): ?>
                                                                                <option value="<?= $dietitian['id'] ?>"
                                                                                        <?= ($dietitian['id'] == $user['assigned_dietitian_id']) ? 'selected' : '' ?>>
                                                                                    <?= clean($dietitian['full_name']) ?>
                                                                                    <?php if ($dietitian['title']): ?>
                                                                                        - <?= clean($dietitian['title']) ?>
                                                                                    <?php endif; ?>
                                                                                    <?php if ($dietitian['specialization']): ?>
                                                                                        (<?= clean($dietitian['specialization']) ?>)
                                                                                    <?php endif; ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Not (Opsiyonel)</label>
                                                                        <textarea name="notes"
                                                                                  class="form-control"
                                                                                  rows="3"
                                                                                  placeholder="Atama ile ilgili not..."></textarea>
                                                                        <small class="text-muted">Bu not sadece admin panelinde görünür.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-modern" style="background: linear-gradient(135deg, #a8a8a8 0%, #757575 100%); color: white;" data-bs-dismiss="modal">
                                                                        <i class="fas fa-times me-2"></i>İptal
                                                                    </button>
                                                                    <button type="submit" class="btn btn-modern btn-modern-primary">
                                                                        <i class="fas fa-check me-2"></i>Diyetisyen Ata
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
