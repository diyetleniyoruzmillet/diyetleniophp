<?php
/**
 * Admin Users Management
 * Kullanıcı yönetim sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();

// Filtreleme
$userType = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Query builder
$where = ['1=1'];
$params = [];

if ($userType !== 'all') {
    $where[] = 'user_type = ?';
    $params[] = $userType;
}

if ($search) {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status === 'active') {
    $where[] = 'is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'is_active = 0';
}

$whereClause = implode(' AND ', $where);

// Toplam sayı
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE $whereClause");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

// Kullanıcıları çek
$stmt = $conn->prepare("
    SELECT u.*,
           dp.title as dietitian_title,
           dp.is_approved as dietitian_approved
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id AND u.user_type = 'dietitian'
    WHERE $whereClause
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// İstatistikler
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as clients,
        SUM(CASE WHEN user_type = 'dietitian' THEN 1 ELSE 0 END) as dietitians,
        SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM users
")->fetch();

$pageTitle = 'Kullanıcı Yönetimi';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    body { background: #f8fafc; }
    .container-fluid { max-width: 1600px; margin: 100px auto 50px; padding: 0 2rem; }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-box {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid;
    }

    .stat-box.total { border-left-color: #3b82f6; }
    .stat-box.clients { border-left-color: #10b981; }
    .stat-box.dietitians { border-left-color: #f59e0b; }
    .stat-box.admins { border-left-color: #ef4444; }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
    }

    .stat-label {
        color: #64748b;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .filters-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-control, .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
        outline: none;
        border-color: #10b981;
    }

    .btn-filter {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-reset {
        background: #f1f5f9;
        color: #64748b;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .users-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f8fafc;
    }

    th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 1rem;
        border-top: 1px solid #f1f5f9;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }

    .user-name {
        font-weight: 600;
        color: #0f172a;
    }

    .user-email {
        font-size: 0.875rem;
        color: #64748b;
    }

    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-admin { background: #fee2e2; color: #dc2626; }
    .badge-dietitian { background: #fef3c7; color: #d97706; }
    .badge-client { background: #dbeafe; color: #2563eb; }
    .badge-active { background: #d1fae5; color: #059669; }
    .badge-inactive { background: #f1f5f9; color: #64748b; }
    .badge-approved { background: #d1fae5; color: #059669; }
    .badge-pending { background: #fef3c7; color: #d97706; }

    .btn-action {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }

    .btn-view {
        background: #dbeafe;
        color: #2563eb;
    }

    .btn-view:hover {
        background: #3b82f6;
        color: white;
    }

    .btn-edit {
        background: #fef3c7;
        color: #d97706;
    }

    .btn-edit:hover {
        background: #f59e0b;
        color: white;
    }

    .btn-delete {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-delete:hover {
        background: #ef4444;
        color: white;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }

    .page-link {
        padding: 0.5rem 1rem;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        color: #0f172a;
        text-decoration: none;
        font-weight: 600;
    }

    .page-link:hover {
        background: #f8fafc;
        border-color: #10b981;
    }

    .page-link.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-color: #10b981;
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-users me-2"></i>
            Kullanıcı Yönetimi
        </h1>
        <a href="/admin/dashboard.php" class="btn-reset">
            <i class="fas fa-arrow-left me-1"></i>
            Dashboard'a Dön
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box total">
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Toplam Kullanıcı</div>
        </div>
        <div class="stat-box clients">
            <div class="stat-value"><?= number_format($stats['clients']) ?></div>
            <div class="stat-label">Danışanlar</div>
        </div>
        <div class="stat-box dietitians">
            <div class="stat-value"><?= number_format($stats['dietitians']) ?></div>
            <div class="stat-label">Diyetisyenler</div>
        </div>
        <div class="stat-box admins">
            <div class="stat-value"><?= number_format($stats['admins']) ?></div>
            <div class="stat-label">Adminler</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Kullanıcı Tipi</label>
                    <select name="type" class="form-select">
                        <option value="all" <?= $userType === 'all' ? 'selected' : '' ?>>Tümü</option>
                        <option value="client" <?= $userType === 'client' ? 'selected' : '' ?>>Danışanlar</option>
                        <option value="dietitian" <?= $userType === 'dietitian' ? 'selected' : '' ?>>Diyetisyenler</option>
                        <option value="admin" <?= $userType === 'admin' ? 'selected' : '' ?>>Adminler</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tümü</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ara</label>
                    <input type="text" name="search" class="form-control" placeholder="İsim, email, telefon..." value="<?= clean($search) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search me-1"></i>
                        Filtrele
                    </button>
                    <a href="/admin/users.php" class="btn-reset ms-2">
                        <i class="fas fa-redo me-1"></i>
                        Sıfırla
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="users-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı</th>
                    <th>Tip</th>
                    <th>Telefon</th>
                    <th>Durum</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $avatar = $user['profile_photo'] ? '/assets/uploads/' . ltrim($user['profile_photo'], '/') : '/images/default-avatar.png';
                ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td>
                        <div class="user-info">
                            <img src="<?= clean($avatar) ?>" alt="<?= clean($user['full_name']) ?>" class="user-avatar">
                            <div>
                                <div class="user-name"><?= clean($user['full_name']) ?></div>
                                <div class="user-email"><?= clean($user['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $user['user_type'] ?>">
                            <?= $user['user_type'] === 'client' ? 'Danışan' : ($user['user_type'] === 'dietitian' ? 'Diyetisyen' : 'Admin') ?>
                        </span>
                        <?php if ($user['user_type'] === 'dietitian'): ?>
                            <br>
                            <span class="badge badge-<?= $user['dietitian_approved'] ? 'approved' : 'pending' ?> mt-1">
                                <?= $user['dietitian_approved'] ? 'Onaylı' : 'Beklemede' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= clean($user['phone'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $user['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <a href="#" class="btn-action btn-view">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="#" class="btn-action btn-edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&type=<?= $userType ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?= $i ?>&type=<?= $userType ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
               class="page-link <?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&type=<?= $userType ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
