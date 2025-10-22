<?php
/**
 * Diyetlenio - Admin Randevu Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Filtreler
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Randevuları çek
$whereClause = "WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $whereClause .= " AND a.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $whereClause .= " AND (c.full_name LIKE ? OR d.full_name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT a.*,
           c.full_name as client_name,
           d.full_name as dietitian_name,
           dp.title as dietitian_title,
           dp.consultation_fee
    FROM appointments a
    INNER JOIN users c ON a.client_id = c.id
    INNER JOIN users d ON a.dietitian_id = d.id
    INNER JOIN dietitian_profiles dp ON d.id = dp.user_id
    {$whereClause}
    ORDER BY a.appointment_date DESC
    LIMIT 100
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// İstatistikler
$stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments
");
$stats = $stmt->fetch();

$pageTitle = 'Randevu Yönetimi';
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
                    <!-- Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-calendar fa-2x text-primary mb-2"></i>
                                <h3><?= number_format($stats['total']) ?></h3>
                                <p class="text-muted mb-0">Toplam Randevu</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h3><?= number_format($stats['scheduled']) ?></h3>
                                <p class="text-muted mb-0">Planlanmış</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3><?= number_format($stats['completed']) ?></h3>
                                <p class="text-muted mb-0">Tamamlanan</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h3><?= number_format($stats['cancelled']) ?></h3>
                                <p class="text-muted mb-0">İptal Edilen</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="btn-group w-100">
                                        <a href="?status=all" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            Tümü
                                        </a>
                                        <a href="?status=scheduled" class="btn btn-sm <?= $status === 'scheduled' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            Planlandı
                                        </a>
                                        <a href="?status=completed" class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            Tamamlandı
                                        </a>
                                        <a href="?status=cancelled" class="btn btn-sm <?= $status === 'cancelled' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                            İptal
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <form method="GET" class="d-flex">
                                        <input type="hidden" name="status" value="<?= $status ?>">
                                        <input type="text" name="search" class="form-control me-2"
                                               placeholder="Danışan veya diyetisyen adı..."
                                               value="<?= clean($search) ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (count($appointments) === 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Randevu bulunamadı</h4>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tarih & Saat</th>
                                                <th>Danışan</th>
                                                <th>Diyetisyen</th>
                                                <th>Tür</th>
                                                <th>Durum</th>
                                                <th>Ücret</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($appointments as $apt): ?>
                                                <tr>
                                                    <td>#<?= $apt['id'] ?></td>
                                                    <td>
                                                        <strong><?= date('d.m.Y', strtotime($apt['appointment_date'])) ?></strong><br>
                                                        <small class="text-muted"><?= date('H:i', strtotime($apt['appointment_date'])) ?></small>
                                                    </td>
                                                    <td><?= clean($apt['client_name']) ?></td>
                                                    <td>
                                                        <?= clean($apt['dietitian_name']) ?><br>
                                                        <small class="text-muted"><?= clean($apt['dietitian_title']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($apt['is_online']): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-video me-1"></i>Online
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-clinic-medical me-1"></i>Yüz Yüze
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badges = [
                                                            'scheduled' => 'warning',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $labels = [
                                                            'scheduled' => 'Planlandı',
                                                            'completed' => 'Tamamlandı',
                                                            'cancelled' => 'İptal'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?= $badges[$apt['status']] ?>">
                                                            <?= $labels[$apt['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= number_format($apt['consultation_fee'], 0) ?> ₺</strong>
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
