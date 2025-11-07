<?php
/**
 * Admin Dietitians Management
 * Diyetisyen yönetimi ve onay sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();

// Onay işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $dietitianId = (int)$_POST['dietitian_id'];

    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE dietitian_profiles SET is_approved = 1 WHERE user_id = ?");
        $stmt->execute([$dietitianId]);
        $success = "Diyetisyen başarıyla onaylandı!";
    } elseif ($_POST['action'] === 'reject') {
        $stmt = $conn->prepare("UPDATE dietitian_profiles SET is_approved = 0 WHERE user_id = ?");
        $stmt->execute([$dietitianId]);
        $success = "Diyetisyen onayı geri alındı!";
    }
}

// Filtreleme
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Query builder
$where = ["u.user_type = 'dietitian'"];
$params = [];

if ($status === 'pending') {
    $where[] = 'dp.is_approved = 0';
} elseif ($status === 'approved') {
    $where[] = 'dp.is_approved = 1';
}

if ($search) {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR dp.title LIKE ? OR dp.specialization LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Diyetisyenleri çek
$stmt = $conn->prepare("
    SELECT u.*,
           dp.*,
           dp.id as profile_id,
           u.id as user_id,
           (SELECT COUNT(*) FROM appointments WHERE dietitian_id = u.id) as total_appointments,
           (SELECT COUNT(*) FROM appointments WHERE dietitian_id = u.id AND status = 'completed') as completed_appointments
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE $whereClause
    ORDER BY dp.is_approved ASC, u.created_at DESC
");
$stmt->execute($params);
$dietitians = $stmt->fetchAll();

// İstatistikler
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending,
        AVG(rating_avg) as avg_rating,
        SUM(total_clients) as total_clients
    FROM dietitian_profiles
")->fetch();

$pageTitle = 'Diyetisyen Yönetimi';
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

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        font-weight: 600;
    }

    .alert-success {
        background: #d1fae5;
        color: #059669;
        border-left: 4px solid #10b981;
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
    .stat-box.approved { border-left-color: #10b981; }
    .stat-box.pending { border-left-color: #f59e0b; }
    .stat-box.rating { border-left-color: #fbbf24; }

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
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

    .dietitians-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .dietitian-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .dietitian-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        border-color: #10b981;
    }

    .dietitian-card.pending {
        border-left: 4px solid #f59e0b;
    }

    .dietitian-card.approved {
        border-left: 4px solid #10b981;
    }

    .dietitian-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .dietitian-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e2e8f0;
    }

    .dietitian-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .dietitian-title {
        color: #64748b;
        font-size: 0.875rem;
    }

    .dietitian-info {
        margin-bottom: 1rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        font-size: 0.875rem;
    }

    .info-label {
        color: #64748b;
        font-weight: 600;
    }

    .info-value {
        color: #0f172a;
        font-weight: 600;
    }

    .rating {
        color: #fbbf24;
    }

    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-approved { background: #d1fae5; color: #059669; }
    .badge-pending { background: #fef3c7; color: #d97706; }

    .dietitian-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .btn-action {
        flex: 1;
        padding: 0.75rem;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .btn-approve {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-approve:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-reject {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .btn-reject:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-view {
        background: #dbeafe;
        color: #2563eb;
    }

    .btn-view:hover {
        background: #3b82f6;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user-md me-2"></i>
            Diyetisyen Yönetimi
        </h1>
        <a href="/admin/dashboard.php" class="btn-reset">
            <i class="fas fa-arrow-left me-1"></i>
            Dashboard'a Dön
        </a>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <?= $success ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box total">
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Toplam Diyetisyen</div>
        </div>
        <div class="stat-box approved">
            <div class="stat-value"><?= number_format($stats['approved']) ?></div>
            <div class="stat-label">Onaylı</div>
        </div>
        <div class="stat-box pending">
            <div class="stat-value"><?= number_format($stats['pending']) ?></div>
            <div class="stat-label">Onay Bekleyen</div>
        </div>
        <div class="stat-box rating">
            <div class="stat-value"><?= number_format($stats['avg_rating'], 1) ?> ⭐</div>
            <div class="stat-label">Ortalama Puan</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Durum</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tümü</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Onay Bekleyenler</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Onaylılar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ara</label>
                    <input type="text" name="search" class="form-control" placeholder="İsim, email, uzmanlık..." value="<?= clean($search) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search me-1"></i>
                        Filtrele
                    </button>
                    <a href="/admin/dietitians.php" class="btn-reset ms-2">
                        <i class="fas fa-redo me-1"></i>
                        Sıfırla
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Dietitians Grid -->
    <?php if (count($dietitians) > 0): ?>
    <div class="dietitians-grid">
        <?php foreach ($dietitians as $dietitian):
            $avatar = $dietitian['profile_photo'] ? '/assets/uploads/' . ltrim($dietitian['profile_photo'], '/') : '/images/default-avatar.png';
            $isPending = !$dietitian['is_approved'];
        ?>
        <div class="dietitian-card <?= $isPending ? 'pending' : 'approved' ?>">
            <div class="dietitian-header">
                <img src="<?= clean($avatar) ?>" alt="<?= clean($dietitian['full_name']) ?>" class="dietitian-avatar">
                <div style="flex: 1;">
                    <div class="dietitian-name"><?= clean($dietitian['full_name']) ?></div>
                    <div class="dietitian-title"><?= clean($dietitian['title'] ?? 'Diyetisyen') ?></div>
                    <span class="badge badge-<?= $isPending ? 'pending' : 'approved' ?> mt-2">
                        <?= $isPending ? 'Onay Bekliyor' : 'Onaylı' ?>
                    </span>
                </div>
            </div>

            <div class="dietitian-info">
                <div class="info-row">
                    <span class="info-label">Uzmanlık</span>
                    <span class="info-value"><?= clean($dietitian['specialization'] ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Deneyim</span>
                    <span class="info-value"><?= $dietitian['experience_years'] ?> yıl</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ücret</span>
                    <span class="info-value"><?= number_format($dietitian['consultation_fee']) ?> ₺</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Puan</span>
                    <span class="info-value rating">
                        <?= number_format($dietitian['rating_avg'], 1) ?> ⭐ (<?= $dietitian['total_reviews'] ?> değerlendirme)
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Randevular</span>
                    <span class="info-value"><?= $dietitian['completed_appointments'] ?> / <?= $dietitian['total_appointments'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kayıt</span>
                    <span class="info-value"><?= date('d.m.Y', strtotime($dietitian['created_at'])) ?></span>
                </div>
            </div>

            <div class="dietitian-actions">
                <?php if ($isPending): ?>
                <form method="POST" style="flex: 1; display: inline;">
                    <input type="hidden" name="dietitian_id" value="<?= $dietitian['user_id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn-action btn-approve" onclick="return confirm('Bu diyetisyeni onaylamak istediğinizden emin misiniz?')">
                        <i class="fas fa-check me-1"></i>
                        Onayla
                    </button>
                </form>
                <?php else: ?>
                <form method="POST" style="flex: 1; display: inline;">
                    <input type="hidden" name="dietitian_id" value="<?= $dietitian['user_id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-action btn-reject" onclick="return confirm('Bu diyetisyenin onayını geri almak istediğinizden emin misiniz?')">
                        <i class="fas fa-times me-1"></i>
                        Onayı Kaldır
                    </button>
                </form>
                <?php endif; ?>
                <a href="/dietitian/profile.php?id=<?= $dietitian['user_id'] ?>" class="btn-action btn-view">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-user-md"></i>
        <h3>Diyetisyen Bulunamadı</h3>
        <p style="color: #64748b;">Arama kriterlerinize uygun diyetisyen bulunmamaktadır.</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
