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
    <style>
        /* Modern Stats Cards */
        .stats-card {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transition: transform 0.5s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }

        .stats-card:hover::before {
            transform: translate(-25%, -25%);
        }

        .stats-card.scheduled-card {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            box-shadow: 0 8px 20px rgba(250, 112, 154, 0.3);
        }

        .stats-card.completed-card {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 8px 20px rgba(67, 233, 123, 0.3);
        }

        .stats-card.cancelled-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
        }

        .stats-card i {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stats-card p {
            font-size: 0.9rem;
            opacity: 0.95;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Filter Card */
        .filter-card {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-filter {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
        }

        .btn-filter.active {
            background: white;
            color: #56ab2f;
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }

        /* Modern Table */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .modern-table {
            margin: 0;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
        }

        .modern-table thead th {
            padding: 18px 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .modern-table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
        }

        /* Modern Badges */
        .badge-modern {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        .badge-scheduled {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            box-shadow: 0 3px 10px rgba(250, 112, 154, 0.3);
        }

        .badge-completed {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 3px 10px rgba(67, 233, 123, 0.3);
        }

        .badge-cancelled {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 3px 10px rgba(245, 87, 108, 0.3);
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box input {
            border-radius: 25px;
            padding: 12px 45px 12px 20px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-box input::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .search-box input:focus {
            border-color: white;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            background: rgba(255,255,255,0.3);
        }

        .search-box .btn-search {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
            background: white;
            color: #56ab2f;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease;
        }

        /* Empty State */
        .empty-state {
            padding: 80px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 5rem;
            color: #d0d0d0;
            margin-bottom: 20px;
        }

        .empty-state p {
            color: #999;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <!-- Stats -->
                    <div class="row g-3 mb-4 fade-in-up">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-calendar-alt"></i>
                                <h3><?= number_format($stats['total']) ?></h3>
                                <p class="mb-0">Toplam Randevu</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card scheduled-card">
                                <i class="fas fa-clock"></i>
                                <h3><?= number_format($stats['scheduled']) ?></h3>
                                <p class="mb-0">Planlanmış</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card completed-card">
                                <i class="fas fa-check-circle"></i>
                                <h3><?= number_format($stats['completed']) ?></h3>
                                <p class="mb-0">Tamamlanan</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card cancelled-card">
                                <i class="fas fa-times-circle"></i>
                                <h3><?= number_format($stats['cancelled']) ?></h3>
                                <p class="mb-0">İptal Edilen</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filter-card fade-in-up">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <div class="btn-group w-100">
                                    <a href="?status=all" class="btn btn-filter <?= $status === 'all' ? 'active' : '' ?>">
                                        <i class="fas fa-list me-2"></i>Tümü
                                    </a>
                                    <a href="?status=scheduled" class="btn btn-filter <?= $status === 'scheduled' ? 'active' : '' ?>">
                                        <i class="fas fa-clock me-2"></i>Planlandı
                                    </a>
                                    <a href="?status=completed" class="btn btn-filter <?= $status === 'completed' ? 'active' : '' ?>">
                                        <i class="fas fa-check me-2"></i>Tamamlandı
                                    </a>
                                    <a href="?status=cancelled" class="btn btn-filter <?= $status === 'cancelled' ? 'active' : '' ?>">
                                        <i class="fas fa-times me-2"></i>İptal
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <form method="GET" class="search-box">
                                    <input type="hidden" name="status" value="<?= $status ?>">
                                    <input type="text" name="search" class="form-control"
                                           placeholder="🔍 Danışan veya diyetisyen adı ile ara..."
                                           value="<?= clean($search) ?>">
                                    <button type="submit" class="btn btn-search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Table -->
                    <div class="table-container fade-in-up">
                        <?php if (count($appointments) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>Randevu bulunamadı</p>
                                <small class="text-muted">Arama kriterlerinizi değiştirmeyi deneyin</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table modern-table">
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
                                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 3px 10px rgba(79, 172, 254, 0.3);">
                                                                <i class="fas fa-video me-1"></i>Online
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-modern" style="background: linear-gradient(135deg, #a8a8a8 0%, #757575 100%); box-shadow: 0 3px 10px rgba(168, 168, 168, 0.3);">
                                                                <i class="fas fa-clinic-medical me-1"></i>Yüz Yüze
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badgeClasses = [
                                                            'scheduled' => 'badge-scheduled',
                                                            'completed' => 'badge-completed',
                                                            'cancelled' => 'badge-cancelled'
                                                        ];
                                                        $labels = [
                                                            'scheduled' => 'Planlandı',
                                                            'completed' => 'Tamamlandı',
                                                            'cancelled' => 'İptal'
                                                        ];
                                                        $icons = [
                                                            'scheduled' => 'fa-clock',
                                                            'completed' => 'fa-check-circle',
                                                            'cancelled' => 'fa-times-circle'
                                                        ];
                                                        ?>
                                                        <span class="badge badge-modern <?= $badgeClasses[$apt['status']] ?>">
                                                            <i class="fas <?= $icons[$apt['status']] ?> me-1"></i>
                                                            <?= $labels[$apt['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 10px 18px; border-radius: 20px; font-weight: 700; font-size: 1rem; box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);">
                                                            <?= number_format($apt['consultation_fee'], 0) ?> ₺
                                                        </span>
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
