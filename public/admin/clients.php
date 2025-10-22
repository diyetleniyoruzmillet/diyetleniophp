<?php
/**
 * Diyetlenio - Admin Danışan Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

$stmt = $conn->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id) as appointment_count,
           (SELECT SUM(amount) FROM payments WHERE user_id = u.id AND status = 'completed') as total_spent
    FROM users u
    WHERE u.user_type = 'client' AND u.email NOT LIKE 'deleted_%'
    ORDER BY u.created_at DESC
");
$clients = $stmt->fetchAll();

$pageTitle = 'Danışan Yönetimi';
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
                    <h2 class="mb-4">Danışan Yönetimi</h2>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad Soyad</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Randevu Sayısı</th>
                            <th>Toplam Harcama</th>
                            <th>Kayıt Tarihi</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?= $client['id'] ?></td>
                                <td><?= clean($client['full_name']) ?></td>
                                <td><?= clean($client['email']) ?></td>
                                <td><?= clean($client['phone'] ?? '-') ?></td>
                                <td><?= $client['appointment_count'] ?></td>
                                <td><?= number_format($client['total_spent'] ?? 0, 2) ?> ₺</td>
                                <td><?= date('d.m.Y', strtotime($client['created_at'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $client['status'] === 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($client['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewClient(<?= $client['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewClient(id) {
        window.location.href = '/admin/users.php?search=' + id;
    }
    </script>
</body>
</html>
