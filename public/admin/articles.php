<?php
/**
 * Admin Article Management
 * Blog yazı onaylama ve yönetimi
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
    $articleId = (int)$_POST['article_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE articles SET status = 'approved', published_at = NOW() WHERE id = ?");
        $stmt->execute([$articleId]);
        $success = "Makale başarıyla onaylandı ve yayımlandı!";
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $stmt = $conn->prepare("UPDATE articles SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $articleId]);
        $success = "Makale reddedildi.";
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $success = "Makale silindi.";
    }
}

// Filtreleme
$status = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

$where = ["1=1"];
$params = [];

if ($status !== 'all') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(a.title LIKE ? OR u.full_name LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Makaleleri çek
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as author_name, u.user_type as author_type
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE $whereClause
    ORDER BY a.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$articles = $stmt->fetchAll();

// İstatistikler
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM articles
")->fetch();

$pageTitle = 'Blog Yazı Yönetimi';
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

    .articles-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .article-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border-left: 4px solid;
    }

    .article-card.pending { border-left-color: #f59e0b; }
    .article-card.approved { border-left-color: #10b981; }
    .article-card.rejected { border-left-color: #ef4444; }
    .article-card.draft { border-left-color: #64748b; }

    .article-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .article-header {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .article-image {
        width: 200px;
        height: 150px;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,0.5);
        font-size: 2rem;
        flex-shrink: 0;
    }

    .article-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
    }

    .article-content {
        flex: 1;
    }

    .article-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .article-author {
        color: #64748b;
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .article-excerpt {
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .article-meta {
        display: flex;
        gap: 1.5rem;
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
        color: #3b82f6;
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

    .article-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .btn-action {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
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
        text-decoration: none;
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
            <i class="fas fa-newspaper me-2"></i>
            Blog Yazı Yönetimi
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
            <div class="stat-label">Toplam Makale</div>
        </div>
        <div class="stat-box pending">
            <div class="stat-value"><?= number_format($stats['pending']) ?></div>
            <div class="stat-label">Onay Bekleyen</div>
        </div>
        <div class="stat-box approved">
            <div class="stat-value"><?= number_format($stats['approved']) ?></div>
            <div class="stat-label">Yayında</div>
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
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Yayında</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Reddedilen</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Taslak</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ara</label>
                    <input type="text" name="search" class="form-control" placeholder="Başlık veya yazar..." value="<?= clean($search) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search me-1"></i>
                        Filtrele
                    </button>
                    <a href="/admin/articles.php" class="btn-reset ms-2">
                        <i class="fas fa-redo me-1"></i>
                        Sıfırla
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Articles List -->
    <?php if (count($articles) > 0): ?>
    <div class="articles-list">
        <?php foreach ($articles as $article): ?>
        <div class="article-card <?= $article['status'] ?>">
            <div class="article-header">
                <div class="article-image">
                    <?php if ($article['featured_image']): ?>
                        <img src="/assets/uploads/<?= ltrim($article['featured_image'], '/') ?>" alt="<?= clean($article['title']) ?>">
                    <?php else: ?>
                        <i class="fas fa-newspaper"></i>
                    <?php endif; ?>
                </div>

                <div class="article-content">
                    <h3 class="article-title"><?= clean($article['title']) ?></h3>

                    <div class="article-author">
                        <span>
                            <i class="fas fa-user"></i>
                            <?= clean($article['author_name'] ?? 'Bilinmeyen') ?>
                        </span>
                        <span class="badge badge-<?= $article['author_type'] === 'admin' ? 'primary' : 'success' ?>" style="font-size: 0.7rem; padding: 2px 8px;">
                            <?= $article['author_type'] === 'admin' ? 'Admin' : 'Diyetisyen' ?>
                        </span>
                        <span class="badge badge-<?= $article['status'] ?>">
                            <?php
                            $statusLabels = [
                                'draft' => 'Taslak',
                                'pending' => 'Onay Bekliyor',
                                'approved' => 'Yayında',
                                'rejected' => 'Reddedildi'
                            ];
                            echo $statusLabels[$article['status']] ?? $article['status'];
                            ?>
                        </span>
                    </div>

                    <?php if ($article['excerpt']): ?>
                    <p class="article-excerpt"><?= clean(substr($article['excerpt'], 0, 200)) ?>...</p>
                    <?php endif; ?>

                    <div class="article-meta">
                        <div class="meta-item">
                            <i class="fas fa-eye"></i>
                            <?= number_format($article['views_count']) ?> görüntüleme
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-heart"></i>
                            <?= number_format($article['likes_count']) ?> beğeni
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <?= date('d.m.Y', strtotime($article['created_at'])) ?>
                        </div>
                        <?php if ($article['published_at']): ?>
                        <div class="meta-item">
                            <i class="fas fa-check-circle"></i>
                            Yayın: <?= date('d.m.Y', strtotime($article['published_at'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="article-actions">
                        <?php if ($article['status'] === 'pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn-action btn-approve" onclick="return confirm('Bu makaleyi onaylayıp yayımlamak istediğinizden emin misiniz?')">
                                <i class="fas fa-check me-1"></i> Onayla ve Yayımla
                            </button>
                        </form>
                        <button class="btn-action btn-reject" onclick="showRejectModal(<?= $article['id'] ?>)">
                            <i class="fas fa-times me-1"></i> Reddet
                        </button>
                        <?php endif; ?>

                        <a href="/blog-detail.php?slug=<?= $article['slug'] ?>" class="btn-action btn-view" target="_blank">
                            <i class="fas fa-eye me-1"></i> Görüntüle
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($article['rejection_reason']): ?>
            <div style="background: #fee2e2; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                <strong style="color: #dc2626;">Red Nedeni:</strong>
                <p style="color: #991b1b; margin: 0.5rem 0 0;"><?= clean($article['rejection_reason']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-newspaper"></i>
        <h3>Makale Bulunamadı</h3>
        <p style="color: #64748b;">Arama kriterlerinize uygun makale bulunmamaktadır.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Makale Reddetme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="article_id" id="rejectArticleId">
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
function showRejectModal(articleId) {
    document.getElementById('rejectArticleId').value = articleId;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
