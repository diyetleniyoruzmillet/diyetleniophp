<?php
/**
 * Diyetlenio - Admin Tarif Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Tarifleri çek
$stmt = $conn->query("
    SELECT r.*, u.full_name as author_name
    FROM recipes r
    LEFT JOIN users u ON r.author_id = u.id
    ORDER BY r.created_at DESC
");
$recipes = $stmt->fetchAll();

$pageTitle = 'Tarif Yönetimi';
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
                        <h2>Tarif Yönetimi</h2>
                        <button class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Yeni Tarif
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (count($recipes) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Henüz tarif yok</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Başlık</th>
                                                <th>Kategori</th>
                                                <th>Kalori</th>
                                                <th>Durum</th>
                                                <th>Tarih</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recipes as $recipe): ?>
                                                <tr>
                                                    <td>#<?= $recipe['id'] ?></td>
                                                    <td><?= clean($recipe['title']) ?></td>
                                                    <td><?= clean($recipe['category']) ?></td>
                                                    <td><?= $recipe['calories'] ?> kcal</td>
                                                    <td>
                                                        <span class="badge bg-<?= $recipe['is_published'] ? 'success' : 'warning' ?>">
                                                            <?= $recipe['is_published'] ? 'Yayında' : 'Taslak' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d.m.Y', strtotime($recipe['created_at'])) ?></td>
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
