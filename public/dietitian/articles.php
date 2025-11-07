<?php
/**
 * Dietitian Articles Management
 * Diyetisyen tarafından blog yazısı oluşturma ve yönetme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Auth kontrolü - Diyetisyen olmalı
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();
$dietitianId = $auth->id();
$pageTitle = "Blog Yazılarım";

// Form gönderimi - Yeni yazı veya güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $articleId = (int) ($_POST['article_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $submitAction = $_POST['submit_action'] ?? 'save_draft'; // save_draft or submit_for_approval

        $errors = [];

        if (empty($title)) $errors[] = 'Başlık gereklidir.';
        if (empty($excerpt)) $errors[] = 'Özet gereklidir.';
        if (empty($content)) $errors[] = 'İçerik gereklidir.';

        if (empty($errors)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $status = ($submitAction === 'submit_for_approval') ? 'pending' : 'draft';

            try {
                if ($action === 'update' && $articleId > 0) {
                    // Yazının sahibi olduğunu kontrol et
                    $stmt = $conn->prepare("SELECT id FROM articles WHERE id = ? AND author_id = ?");
                    $stmt->execute([$articleId, $dietitianId]);

                    if ($stmt->fetch()) {
                        $stmt = $conn->prepare("
                            UPDATE articles SET
                                title = ?,
                                slug = ?,
                                excerpt = ?,
                                content = ?,
                                status = ?,
                                updated_at = NOW()
                            WHERE id = ? AND author_id = ?
                        ");

                        $stmt->execute([
                            $title, $slug, $excerpt, $content, $status,
                            $articleId, $dietitianId
                        ]);

                        $successMessage = ($submitAction === 'submit_for_approval')
                            ? 'Yazı onay için gönderildi!'
                            : 'Yazı güncellendi!';
                    }
                } else {
                    // Yeni yazı oluştur
                    $stmt = $conn->prepare("
                        INSERT INTO articles (
                            author_id, title, slug, excerpt, content,
                            status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");

                    $stmt->execute([
                        $dietitianId, $title, $slug, $excerpt, $content, $status
                    ]);

                    $successMessage = ($submitAction === 'submit_for_approval')
                        ? 'Yazı onay için gönderildi!'
                        : 'Yazı taslak olarak kaydedildi!';
                }
            } catch (Exception $e) {
                error_log('Article save error: ' . $e->getMessage());
                $errorMessage = 'Yazı kaydedilirken bir hata oluştu.';
            }
        }
    } elseif ($action === 'delete') {
        $articleId = (int) ($_POST['article_id'] ?? 0);

        try {
            // Sadece draft ve rejected yazıları silebilir
            $stmt = $conn->prepare("
                DELETE FROM articles
                WHERE id = ? AND author_id = ? AND status IN ('draft', 'rejected')
            ");
            $stmt->execute([$articleId, $dietitianId]);

            $successMessage = 'Yazı silindi!';
        } catch (Exception $e) {
            error_log('Article delete error: ' . $e->getMessage());
            $errorMessage = 'Yazı silinirken bir hata oluştu.';
        }
    }
}

// Yazıları getir
$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "author_id = ?";
$params = [$dietitianId];

if ($statusFilter !== 'all') {
    $whereClause .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmt = $conn->prepare("
    SELECT a.*,
           (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) as like_count,
           (SELECT COUNT(*) FROM article_comments WHERE article_id = a.id) as comment_count
    FROM articles a
    WHERE $whereClause
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$articles = $stmt->fetchAll();

// İstatistikler
$stats = $conn->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM articles
    WHERE author_id = ?
");
$stats->execute([$dietitianId]);
$stats = $stats->fetch();

require_once __DIR__ . '/../../includes/partials/header.php';
?>

<div class="container my-5">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="bi bi-newspaper"></i> Blog Yazılarım
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#articleModal">
            <i class="bi bi-plus-lg"></i> Yeni Yazı Ekle
        </button>
    </div>

    <!-- Başarı/Hata Mesajları -->
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                    <small>Toplam Yazı</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['draft_count'] ?></h3>
                    <small>Taslak</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['pending_count'] ?></h3>
                    <small>Onay Bekliyor</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $stats['approved_count'] ?></h3>
                    <small>Yayınlandı</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?status=all" class="btn btn-outline-primary <?= $statusFilter === 'all' ? 'active' : '' ?>">
                    Tümü (<?= $stats['total'] ?>)
                </a>
                <a href="?status=draft" class="btn btn-outline-secondary <?= $statusFilter === 'draft' ? 'active' : '' ?>">
                    Taslak (<?= $stats['draft_count'] ?>)
                </a>
                <a href="?status=pending" class="btn btn-outline-warning <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                    Onay Bekliyor (<?= $stats['pending_count'] ?>)
                </a>
                <a href="?status=approved" class="btn btn-outline-success <?= $statusFilter === 'approved' ? 'active' : '' ?>">
                    Yayınlandı (<?= $stats['approved_count'] ?>)
                </a>
                <a href="?status=rejected" class="btn btn-outline-danger <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
                    Reddedildi (<?= $stats['rejected_count'] ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Yazılar Listesi -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($articles)): ?>
                <div class="alert alert-info text-center mb-0">
                    <i class="bi bi-info-circle"></i> Henüz blog yazısı eklemediniz.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Başlık</th>
                                <th>Durum</th>
                                <th>İstatistikler</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($article['title']) ?></strong>
                                            <p class="text-muted small mb-0">
                                                <?= htmlspecialchars(mb_substr($article['excerpt'], 0, 80)) ?>...
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'draft' => '<span class="badge bg-secondary">Taslak</span>',
                                            'pending' => '<span class="badge bg-warning">Bekliyor</span>',
                                            'approved' => '<span class="badge bg-success">Yayınlandı</span>',
                                            'rejected' => '<span class="badge bg-danger">Reddedildi</span>'
                                        ];
                                        echo $statusBadge[$article['status']] ?? '';
                                        ?>
                                    </td>
                                    <td>
                                        <small class="d-flex gap-2">
                                            <span><i class="bi bi-eye"></i> <?= $article['view_count'] ?? 0 ?></span>
                                            <span><i class="bi bi-heart"></i> <?= $article['like_count'] ?></span>
                                            <span><i class="bi bi-chat"></i> <?= $article['comment_count'] ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= date('d.m.Y', strtotime($article['created_at'])) ?><br>
                                            <?php if ($article['published_at']): ?>
                                                <span class="text-success">Yayın: <?= date('d.m.Y', strtotime($article['published_at'])) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info"
                                                    onclick="viewArticle(<?= htmlspecialchars(json_encode($article)) ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if (in_array($article['status'], ['draft', 'rejected'])): ?>
                                                <button type="button" class="btn btn-outline-primary"
                                                        onclick="editArticle(<?= htmlspecialchars(json_encode($article)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Yazıyı silmek istediğinizden emin misiniz?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ($article['status'] === 'rejected' && $article['rejection_reason']): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="alert alert-danger small mb-0">
                                                <strong>Red Nedeni:</strong> <?= htmlspecialchars($article['rejection_reason']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Yazı Modal -->
<div class="modal fade" id="articleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="articleModalTitle">Yeni Yazı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="modalAction" value="create">
                    <input type="hidden" name="article_id" id="articleId">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Başlık *</label>
                            <input type="text" name="title" id="articleTitle" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Özet * (Kısa açıklama)</label>
                            <textarea name="excerpt" id="articleExcerpt" class="form-control" rows="2" required></textarea>
                            <small class="text-muted">Bu metin yazının önizlemesinde görünecektir.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">İçerik *</label>
                            <textarea name="content" id="articleContent" class="form-control" rows="15" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="submit_action" value="save_draft" class="btn btn-outline-primary">
                        <i class="bi bi-save"></i> Taslak Olarak Kaydet
                    </button>
                    <button type="submit" name="submit_action" value="submit_for_approval" class="btn btn-primary">
                        <i class="bi bi-send"></i> Onay İçin Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Görüntüleme Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
function editArticle(article) {
    document.getElementById('articleModalTitle').textContent = 'Yazıyı Düzenle';
    document.getElementById('modalAction').value = 'update';
    document.getElementById('articleId').value = article.id;
    document.getElementById('articleTitle').value = article.title;
    document.getElementById('articleExcerpt').value = article.excerpt;
    document.getElementById('articleContent').value = article.content;

    new bootstrap.Modal(document.getElementById('articleModal')).show();
}

function viewArticle(article) {
    document.getElementById('viewModalTitle').textContent = article.title;

    let statusBadge = {
        'draft': '<span class="badge bg-secondary">Taslak</span>',
        'pending': '<span class="badge bg-warning">Onay Bekliyor</span>',
        'approved': '<span class="badge bg-success">Yayınlandı</span>',
        'rejected': '<span class="badge bg-danger">Reddedildi</span>'
    }[article.status] || '';

    document.getElementById('viewModalBody').innerHTML = `
        <div class="mb-3">
            ${statusBadge}
        </div>

        <div class="mb-3">
            <strong>Özet:</strong><br>
            <em>${article.excerpt}</em>
        </div>

        <hr>

        <div style="white-space: pre-wrap;">${article.content}</div>

        <hr>

        <div class="d-flex gap-3 text-muted small">
            <span><i class="bi bi-eye"></i> ${article.views_count || 0} görüntülenme</span>
            <span><i class="bi bi-heart"></i> ${article.like_count} beğeni</span>
            <span><i class="bi bi-chat"></i> ${article.comment_count} yorum</span>
        </div>

        <div class="mt-3 text-muted small">
            Oluşturulma: ${new Date(article.created_at).toLocaleDateString('tr-TR')}
            ${article.published_at ? `<br>Yayınlanma: ${new Date(article.published_at).toLocaleDateString('tr-TR')}` : ''}
        </div>

        ${article.rejection_reason ? `
            <div class="alert alert-danger mt-3">
                <strong>Red Nedeni:</strong><br>${article.rejection_reason}
            </div>
        ` : ''}
    `;

    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

// Modal temizleme
document.getElementById('articleModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('articleModalTitle').textContent = 'Yeni Yazı Ekle';
    document.getElementById('modalAction').value = 'create';
    document.getElementById('articleId').value = '';
    this.querySelector('form').reset();
});
</script>

<?php require_once __DIR__ . '/../../includes/partials/footer.php'; ?>
