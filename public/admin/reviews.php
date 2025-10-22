<?php
/**
 * Diyetlenio - Admin Değerlendirme Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Değerlendirmeleri çek
$stmt = $conn->query("
    SELECT r.*,
           c.full_name as client_name,
           d.full_name as dietitian_name
    FROM reviews r
    INNER JOIN users c ON r.client_id = c.id
    INNER JOIN users d ON r.dietitian_id = d.id
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll();

$pageTitle = 'Değerlendirme Yönetimi';
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
                    <h2 class="mb-4">Değerlendirme Yönetimi</h2>

                    <div class="card">
                        <div class="card-body">
                            <?php if (count($reviews) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-star fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Henüz değerlendirme yok</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tarih</th>
                                                <th>Danışan</th>
                                                <th>Diyetisyen</th>
                                                <th>Puan</th>
                                                <th>Değerlendirme</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reviews as $review): ?>
                                                <tr>
                                                    <td>#<?= $review['id'] ?></td>
                                                    <td><?= date('d.m.Y', strtotime($review['created_at'])) ?></td>
                                                    <td><?= clean($review['client_name']) ?></td>
                                                    <td><?= clean($review['dietitian_name']) ?></td>
                                                    <td>
                                                        <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php endfor; ?>
                                                        <?php for ($i = $review['rating']; $i < 5; $i++): ?>
                                                            <i class="far fa-star text-muted"></i>
                                                        <?php endfor; ?>
                                                    </td>
                                                    <td><?= clean(substr($review['review'], 0, 50)) ?>...</td>
                                                    <td>
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
