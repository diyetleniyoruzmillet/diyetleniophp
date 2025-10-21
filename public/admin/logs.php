<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$auth->requireAdmin();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 100");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sistem Logları - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Sistem Logları</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kullanıcı</th>
                    <th>Aksiyon</th>
                    <th>Açıklama</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= formatDate($log['created_at'], DATETIME_FORMAT) ?></td>
                        <td><?= clean($log['full_name'] ?? 'Sistem') ?></td>
                        <td><span class="badge bg-info"><?= clean($log['action']) ?></span></td>
                        <td><?= clean($log['description']) ?></td>
                        <td><?= clean($log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
