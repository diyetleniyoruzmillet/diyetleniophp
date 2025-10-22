<?php
/**
 * Diyetlenio - Admin Makale Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Makaleleri çek
$stmt = $conn->query("
    SELECT a.*, u.full_name as author_name
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    ORDER BY a.created_at DESC
");
$articles = $stmt->fetchAll();

$pageTitle = 'Makale Yönetimi';
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
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Makale Yönetimi</h2>
                        <button class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Yeni Makale
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (count($articles) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Henüz makale yok</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Başlık</th>
                                                <th>Yazar</th>
                                                <th>Kategori</th>
                                                <th>Durum</th>
                                                <th>Tarih</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($articles as $article): ?>
                                                <tr>
                                                    <td>#<?= $article['id'] ?></td>
                                                    <td><?= clean($article['title']) ?></td>
                                                    <td><?= clean($article['author_name'] ?? 'Admin') ?></td>
                                                    <td><?= clean($article['category']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $article['is_published'] ? 'success' : 'warning' ?>">
                                                            <?= $article['is_published'] ? 'Yayında' : 'Taslak' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d.m.Y', strtotime($article['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
