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

// Kullanıcı durumu değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
    } else {
        $userId = (int)$_POST['user_id'];
        $action = $_POST['action'];

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
           END as is_approved
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id AND u.user_type = 'dietitian'
    {$whereClause}
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

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
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-card h3 { margin: 10px 0 5px; }
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link active" href="/admin/users.php">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </a>
                        <a class="nav-link" href="/admin/dietitians.php">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenler
                        </a>
                        <a class="nav-link" href="/admin/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevular
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
                    <!-- Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-2">
                            <div class="stat-card">
                                <i class="fas fa-users fa-2x text-primary"></i>
                                <h3><?= number_format($totalStats['total_users']) ?></h3>
                                <p class="text-muted mb-0">Toplam</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <i class="fas fa-user-shield fa-2x text-danger"></i>
                                <h3><?= number_format($totalStats['admin_count']) ?></h3>
                                <p class="text-muted mb-0">Admin</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <i class="fas fa-user-md fa-2x text-success"></i>
                                <h3><?= number_format($totalStats['dietitian_count']) ?></h3>
                                <p class="text-muted mb-0">Diyetisyen</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <i class="fas fa-user fa-2x text-info"></i>
                                <h3><?= number_format($totalStats['client_count']) ?></h3>
                                <p class="text-muted mb-0">Danışan</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                                <h3><?= number_format($totalStats['active_count']) ?></h3>
                                <p class="text-muted mb-0">Aktif</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="stat-card">
                                <i class="fas fa-times-circle fa-2x text-secondary"></i>
                                <h3><?= number_format($totalStats['inactive_count']) ?></h3>
                                <p class="text-muted mb-0">Pasif</p>
                            </div>
                        </div>
                    </div>

                    <!-- Page Header -->
                    <div class="page-header">
                        <h2 class="mb-3">Kullanıcı Yönetimi</h2>

                        <!-- Filters -->
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="btn-group w-100" role="group">
                                    <a href="?filter=all<?= $userType ? '&user_type=' . $userType : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                                       class="btn btn-sm btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                                        Tümü
                                    </a>
                                    <a href="?filter=active<?= $userType ? '&user_type=' . $userType : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                                       class="btn btn-sm btn-outline-success <?= $filter === 'active' ? 'active' : '' ?>">
                                        Aktif
                                    </a>
                                    <a href="?filter=inactive<?= $userType ? '&user_type=' . $userType : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                                       class="btn btn-sm btn-outline-secondary <?= $filter === 'inactive' ? 'active' : '' ?>">
                                        Pasif
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <form method="GET">
                                    <input type="hidden" name="filter" value="<?= $filter ?>">
                                    <?php if ($search): ?>
                                        <input type="hidden" name="search" value="<?= clean($search) ?>">
                                    <?php endif; ?>
                                    <select name="user_type" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">Tüm Tipler</option>
                                        <option value="admin" <?= $userType === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="dietitian" <?= $userType === 'dietitian' ? 'selected' : '' ?>>Diyetisyen</option>
                                        <option value="client" <?= $userType === 'client' ? 'selected' : '' ?>>Danışan</option>
                                    </select>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="hidden" name="filter" value="<?= $filter ?>">
                                    <?php if ($userType): ?>
                                        <input type="hidden" name="user_type" value="<?= $userType ?>">
                                    <?php endif; ?>
                                    <input type="text"
                                           name="search"
                                           class="form-control form-control-sm me-2"
                                           placeholder="İsim, email veya telefon ile ara..."
                                           value="<?= clean($search) ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="?filter=<?= $filter ?><?= $userType ? '&user_type=' . $userType : '' ?>"
                                           class="btn btn-sm btn-secondary ms-2">
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
                    <div class="table-card">
                        <?php if (count($users) === 0): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Kullanıcı bulunamadı.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Ad Soyad</th>
                                            <th>Email</th>
                                            <th>Telefon</th>
                                            <th>Tip</th>
                                            <th>Durum</th>
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
                                                    $badges = [
                                                        'admin' => 'danger',
                                                        'dietitian' => 'success',
                                                        'client' => 'info'
                                                    ];
                                                    $labels = [
                                                        'admin' => 'Admin',
                                                        'dietitian' => 'Diyetisyen',
                                                        'client' => 'Danışan'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?= $badges[$user['user_type']] ?>">
                                                        <?= $labels[$user['user_type']] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Aktif
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-times-circle"></i> Pasif
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?= number_format($user['appointment_count']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= date('d.m.Y', strtotime($user['created_at'])) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['user_type'] !== 'admin'): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($user['is_active']): ?>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı deaktif etmek istediğinize emin misiniz?')">
                                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="deactivate">
                                                                    <button type="submit" class="btn btn-warning btn-sm" title="Deaktif Et">
                                                                        <i class="fas fa-ban"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <button type="submit" class="btn btn-success btn-sm" title="Aktif Et">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')">
                                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-danger btn-sm" title="Sil">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
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
