<?php
/**
 * Admin Recipe Management
 * Tarif onaylama ve yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();

// Onaylama/Reddetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $recipeId = (int)$_POST['recipe_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE recipes SET status = 'approved' WHERE id = ?");
        $stmt->execute([$recipeId]);
        $success = "Tarif başarıyla onaylandı!";
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $stmt = $conn->prepare("UPDATE recipes SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $recipeId]);
        $success = "Tarif reddedildi.";
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM recipes WHERE id = ?");
        $stmt->execute([$recipeId]);
        $success = "Tarif silindi.";
    }
}

// Filtreleme
$status = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

$where = ["1=1"];
$params = [];

if ($status !== 'all') {
    $where[] = 'r.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(r.title LIKE ? OR u.full_name LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Tarifleri çek
$stmt = $conn->prepare("
    SELECT r.*, u.full_name as author_name, u.user_type as author_type
    FROM recipes r
    LEFT JOIN users u ON r.author_id = u.id
    WHERE $whereClause
    ORDER BY r.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// İstatistikler
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM recipes
")->fetch();

$pageTitle = 'Tarif Yönetimi';
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

    .alert-success {
        background: #d1fae5;
        color: #059669;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
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
    .stat-box.pending { border-left-color: #f59e0b; }
    .stat-box.approved { border-left-color: #10b981; }
    .stat-box.rejected { border-left-color: #ef4444; }

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
        text-decoration: none;
        display: inline-block;
    }

    .recipes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .recipe-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .recipe-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        border-color: #10b981;
    }

    .recipe-card.pending { border-left: 4px solid #f59e0b; }
    .recipe-card.approved { border-left: 4px solid #10b981; }
    .recipe-card.rejected { border-left: 4px solid #ef4444; }

    .recipe-image {
        height: 200px;
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,0.5);
        font-size: 3rem;
    }

    .recipe-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .recipe-body {
        padding: 1.5rem;
    }

    .recipe-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .recipe-author {
        color: #64748b;
        font-size: 0.875rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .recipe-meta {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    .meta-item i {
        color: #10b981;
    }

    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-approved { background: #d1fae5; color: #059669; }
    .badge-rejected { background: #fee2e2; color: #dc2626; }
    .badge-draft { background: #f1f5f9; color: #64748b; }

    .recipe-actions {
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

    .btn-reject {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .btn-view {
        background: #dbeafe;
        color: #2563eb;
    }

    .btn-delete {
        background: #fee2e2;
        color: #dc2626;
    }

    .modal-content {
        border-radius: 16px;
        border: none;
    }

    .modal-header {
        border-bottom: 2px solid #f1f5f9;
        padding: 1.5rem;
    }

    .modal-body {
        padding: 1.5rem;
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
</style>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-utensils me-2"></i>
            Tarif Yönetimi
        </h1>
        <a href="/admin/dashboard.php" class="btn-reset">
            <i class="fas fa-arrow-left me-1"></i>
            Dashboard
        </a>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <?= $success ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box total">
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Toplam Tarif</div>
        </div>
        <div class="stat-box pending">
            <div class="stat-value"><?= number_format($stats['pending']) ?></div>
            <div class="stat-label">Onay Bekleyen</div>
        </div>
        <div class="stat-box approved">
            <div class="stat-value"><?= number_format($stats['approved']) ?></div>
            <div class="stat-label">Onaylı</div>
        </div>
        <div class="stat-box rejected">
            <div class="stat-value"><?= number_format($stats['rejected']) ?></div>
            <div class="stat-label">Reddedilen</div>
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
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Onay Bekleyen</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Onaylı</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Reddedilen</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Taslak</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ara</label>
                    <input type="text" name="search" class="form-control" placeholder="Tarif veya yazar adı..." value="<?= clean($search) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search me-1"></i>
                        Filtrele
                    </button>
                    <a href="/admin/recipes.php" class="btn-reset ms-2">
                        <i class="fas fa-redo me-1"></i>
                        Sıfırla
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Recipes Grid -->
    <?php if (count($recipes) > 0): ?>
    <div class="recipes-grid">
        <?php foreach ($recipes as $recipe): ?>
        <div class="recipe-card <?= $recipe['status'] ?>">
            <div class="recipe-image">
                <?php if ($recipe['featured_image']): ?>
                    <img src="/assets/uploads/<?= ltrim($recipe['featured_image'], '/') ?>" alt="<?= clean($recipe['title']) ?>">
                <?php else: ?>
                    <i class="fas fa-utensils"></i>
                <?php endif; ?>
            </div>

            <div class="recipe-body">
                <h3 class="recipe-title"><?= clean($recipe['title']) ?></h3>
                <div class="recipe-author">
                    <i class="fas fa-user"></i>
                    <?= clean($recipe['author_name'] ?? 'Bilinmeyen') ?>
                    <span class="badge badge-<?= $recipe['author_type'] === 'admin' ? 'primary' : 'success' ?>" style="font-size: 0.7rem; padding: 2px 8px;">
                        <?= $recipe['author_type'] === 'admin' ? 'Admin' : 'Diyetisyen' ?>
                    </span>
                </div>

                <div class="recipe-meta">
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <?= $recipe['total_time'] ?> dk
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <?= $recipe['servings'] ?> kişilik
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-fire"></i>
                        <?= $recipe['calories_per_serving'] ?> kal
                    </div>
                </div>

                <span class="badge badge-<?= $recipe['status'] ?>">
                    <?php
                    $statusLabels = [
                        'draft' => 'Taslak',
                        'pending' => 'Onay Bekliyor',
                        'approved' => 'Onaylı',
                        'rejected' => 'Reddedildi'
                    ];
                    echo $statusLabels[$recipe['status']] ?? $recipe['status'];
                    ?>
                </span>

                <div class="recipe-actions">
                    <?php if ($recipe['status'] === 'pending'): ?>
                    <form method="POST" style="flex: 1; display: inline;">
                        <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-action btn-approve" onclick="return confirm('Bu tarifi onaylamak istediğinizden emin misiniz?')">
                            <i class="fas fa-check"></i> Onayla
                        </button>
                    </form>
                    <button class="btn-action btn-reject" onclick="showRejectModal(<?= $recipe['id'] ?>)">
                        <i class="fas fa-times"></i> Reddet
                    </button>
                    <?php endif; ?>

                    <a href="/recipe-detail.php?id=<?= $recipe['id'] ?>" class="btn-action btn-view" target="_blank">
                        <i class="fas fa-eye"></i> Görüntüle
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-utensils"></i>
        <h3>Tarif Bulunamadı</h3>
        <p style="color: #64748b;">Arama kriterlerinize uygun tarif bulunmamaktadır.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tarif Reddetme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="recipe_id" id="rejectRecipeId">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label>Red Nedeni</label>
                        <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Red nedenini açıklayın..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Reddet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showRejectModal(recipeId) {
    document.getElementById('rejectRecipeId').value = recipeId;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
