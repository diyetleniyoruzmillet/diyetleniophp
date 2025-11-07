<?php
/**
 * Admin Appointments Management
 * Randevu yönetim sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();

// Filtreleme
$status = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

// Query builder
$where = ['1=1'];
$params = [];

if ($status !== 'all') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($dateFilter === 'today') {
    $where[] = 'DATE(a.appointment_date) = CURDATE()';
} elseif ($dateFilter === 'week') {
    $where[] = 'YEARWEEK(a.appointment_date, 1) = YEARWEEK(CURDATE(), 1)';
} elseif ($dateFilter === 'month') {
    $where[] = 'YEAR(a.appointment_date) = YEAR(CURDATE()) AND MONTH(a.appointment_date) = MONTH(CURDATE())';
}

if ($search) {
    $where[] = '(c.full_name LIKE ? OR d.full_name LIKE ? OR c.email LIKE ? OR d.email LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Randevuları çek
$stmt = $conn->prepare("
    SELECT a.*,
           c.full_name as client_name,
           c.email as client_email,
           c.profile_photo as client_photo,
           d.full_name as dietitian_name,
           d.email as dietitian_email,
           d.profile_photo as dietitian_photo,
           dp.title as dietitian_title
    FROM appointments a
    LEFT JOIN users c ON a.client_id = c.id
    LEFT JOIN users d ON a.dietitian_id = d.id
    LEFT JOIN dietitian_profiles dp ON d.id = dp.user_id
    WHERE $whereClause
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 100
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// İstatistikler
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM appointments
")->fetch();

$pageTitle = 'Randevu Yönetimi';
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
    .stat-box.scheduled { border-left-color: #10b981; }
    .stat-box.completed { border-left-color: #8b5cf6; }
    .stat-box.cancelled { border-left-color: #ef4444; }
    .stat-box.today { border-left-color: #f59e0b; }

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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        transition: all 0.2s;
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
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .appointments-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f8fafc;
    }

    th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 1rem;
        border-top: 1px solid #f1f5f9;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }

    .user-name {
        font-weight: 600;
        color: #0f172a;
        font-size: 0.875rem;
    }

    .user-email {
        font-size: 0.75rem;
        color: #64748b;
    }

    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-scheduled { background: #d1fae5; color: #059669; }
    .badge-completed { background: #e9d5ff; color: #7c3aed; }
    .badge-cancelled { background: #fee2e2; color: #dc2626; }
    .badge-pending { background: #fef3c7; color: #d97706; }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-calendar-alt me-2"></i>
            Randevu Yönetimi
        </h1>
        <a href="/admin/dashboard.php" class="btn-reset">
            <i class="fas fa-arrow-left me-1"></i>
            Dashboard'a Dön
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box total">
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Toplam Randevu</div>
        </div>
        <div class="stat-box scheduled">
            <div class="stat-value"><?= number_format($stats['scheduled']) ?></div>
            <div class="stat-label">Planlanmış</div>
        </div>
        <div class="stat-box completed">
            <div class="stat-value"><?= number_format($stats['completed']) ?></div>
            <div class="stat-label">Tamamlanmış</div>
        </div>
        <div class="stat-box cancelled">
            <div class="stat-value"><?= number_format($stats['cancelled']) ?></div>
            <div class="stat-label">İptal Edilmiş</div>
        </div>
        <div class="stat-box today">
            <div class="stat-value"><?= number_format($stats['today']) ?></div>
            <div class="stat-label">Bugünkü Randevular</div>
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
                        <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Planlanmış</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Tamamlanmış</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>İptal Edilmiş</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tarih</label>
                    <select name="date" class="form-select">
                        <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>Tümü</option>
                        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Bugün</option>
                        <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Bu Hafta</option>
                        <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Bu Ay</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ara</label>
                    <input type="text" name="search" class="form-control" placeholder="Danışan veya diyetisyen adı..." value="<?= clean($search) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search me-1"></i>
                        Filtrele
                    </button>
                    <a href="/admin/appointments.php" class="btn-reset ms-2">
                        <i class="fas fa-redo me-1"></i>
                        Sıfırla
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Appointments Table -->
    <?php if (count($appointments) > 0): ?>
    <div class="appointments-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Danışan</th>
                    <th>Diyetisyen</th>
                    <th>Tarih & Saat</th>
                    <th>Süre</th>
                    <th>Durum</th>
                    <th>Oluşturulma</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app):
                    $clientAvatar = $app['client_photo'] ? '/assets/uploads/' . ltrim($app['client_photo'], '/') : '/images/default-avatar.png';
                    $dietitianAvatar = $app['dietitian_photo'] ? '/assets/uploads/' . ltrim($app['dietitian_photo'], '/') : '/images/default-avatar.png';
                ?>
                <tr>
                    <td><strong>#<?= $app['id'] ?></strong></td>
                    <td>
                        <div class="user-info">
                            <img src="<?= clean($clientAvatar) ?>" alt="<?= clean($app['client_name']) ?>" class="user-avatar">
                            <div>
                                <div class="user-name"><?= clean($app['client_name']) ?></div>
                                <div class="user-email"><?= clean($app['client_email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="user-info">
                            <img src="<?= clean($dietitianAvatar) ?>" alt="<?= clean($app['dietitian_name']) ?>" class="user-avatar">
                            <div>
                                <div class="user-name"><?= clean($app['dietitian_name']) ?></div>
                                <div class="user-email"><?= clean($app['dietitian_title'] ?? 'Diyetisyen') ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a;">
                            <?= date('d.m.Y', strtotime($app['appointment_date'])) ?>
                        </div>
                        <div style="font-size: 0.875rem; color: #64748b;">
                            <?= date('H:i', strtotime($app['start_time'])) ?>
                        </div>
                    </td>
                    <td><?= $app['duration'] ?? 45 ?> dk</td>
                    <td>
                        <span class="badge badge-<?= $app['status'] ?>">
                            <?php
                            $statusLabels = [
                                'scheduled' => 'Planlanmış',
                                'completed' => 'Tamamlandı',
                                'cancelled' => 'İptal',
                                'pending' => 'Beklemede'
                            ];
                            echo $statusLabels[$app['status']] ?? $app['status'];
                            ?>
                        </span>
                    </td>
                    <td style="font-size: 0.875rem; color: #64748b;">
                        <?= date('d.m.Y', strtotime($app['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="appointments-table">
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Randevu Bulunamadı</h3>
            <p style="color: #64748b;">Arama kriterlerinize uygun randevu bulunmamaktadır.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
