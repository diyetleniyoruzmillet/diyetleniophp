<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    redirect('/login.php');
}

$stmt = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id) as appointment_count,
           (SELECT SUM(amount) FROM payments WHERE user_id = u.id AND status = 'completed') as total_spent
    FROM users u
    WHERE u.user_type = 'client'
    ORDER BY u.created_at DESC
");
$clients = $stmt->fetchAll();

include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Danışan Yönetimi</h1>

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
                                <td><?= clean($client['first_name'] . ' ' . $client['last_name']) ?></td>
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

<script>
function viewClient(id) {
    alert('Danışan detay sayfası yakında eklenecek. ID: ' + id);
}
</script>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
