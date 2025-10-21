<?php
/**
 * Diyetlenio - Diyetisyen Randevularım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'scheduled';
$clientId = $_GET['client_id'] ?? null;

// Randevuları çek
$whereClause = "WHERE a.dietitian_id = ?";
$params = [$userId];

if ($status === 'scheduled') {
    $whereClause .= " AND a.status = 'scheduled' AND a.appointment_date >= NOW()";
} elseif ($status === 'completed') {
    $whereClause .= " AND a.status = 'completed'";
} elseif ($status === 'cancelled') {
    $whereClause .= " AND a.status = 'cancelled'";
}

if ($clientId) {
    $whereClause .= " AND a.client_id = ?";
    $params[] = $clientId;
}

$stmt = $conn->prepare("
    SELECT a.*, u.full_name as client_name, u.email as client_email,
           u.phone as client_phone
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    {$whereClause}
    ORDER BY a.appointment_date DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Randevularım';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .appointment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link active" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevular
                        </a>
                        <a class="nav-link" href="/dietitian/availability.php">
                            <i class="fas fa-clock me-2"></i>Müsaitlik
                        </a>
                        <a class="nav-link" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
                        </a>
                        <a class="nav-link" href="/dietitian/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link" href="/dietitian/profile.php">
                            <i class="fas fa-user me-2"></i>Profilim
                        </a>
                        <hr class="text-white-50 my-3">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-10">
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Randevularım</h2>
                        <div class="btn-group">
                            <a href="?status=scheduled" class="btn <?= $status === 'scheduled' ? 'btn-success' : 'btn-outline-success' ?>">
                                Yaklaşan
                            </a>
                            <a href="?status=completed" class="btn <?= $status === 'completed' ? 'btn-success' : 'btn-outline-success' ?>">
                                Tamamlanan
                            </a>
                            <a href="?status=cancelled" class="btn <?= $status === 'cancelled' ? 'btn-success' : 'btn-outline-success' ?>">
                                İptal
                            </a>
                        </div>
                    </div>

                    <?php if (count($appointments) === 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Randevu bulunamadı</h4>
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $apt): ?>
                            <div class="appointment-card">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="h3 mb-0 text-success"><?= date('d', strtotime($apt['appointment_date'])) ?></div>
                                            <div class="text-muted"><?= date('M Y', strtotime($apt['appointment_date'])) ?></div>
                                            <div class="text-primary fw-bold"><?= date('H:i', strtotime($apt['appointment_date'])) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <h5 class="mb-1"><?= clean($apt['client_name']) ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-envelope me-2"></i><?= clean($apt['client_email']) ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-phone me-2"></i><?= clean($apt['client_phone']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <?php if ($apt['is_online']): ?>
                                            <span class="badge bg-info mb-2">
                                                <i class="fas fa-video me-1"></i>Online Görüşme
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mb-2">
                                                <i class="fas fa-clinic-medical me-1"></i>Yüz Yüze
                                            </span>
                                        <?php endif; ?>
                                        <br>
                                        <?php
                                        $badges = [
                                            'scheduled' => 'warning',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $labels = [
                                            'scheduled' => 'Planlandı',
                                            'completed' => 'Tamamlandı',
                                            'cancelled' => 'İptal Edildi'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badges[$apt['status']] ?>">
                                            <?= $labels[$apt['status']] ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <?php if ($apt['status'] === 'scheduled'): ?>
                                            <?php if ($apt['is_online']): ?>
                                                <a href="/video-call.php?appointment=<?= $apt['id'] ?>" class="btn btn-success btn-sm w-100 mb-2">
                                                    <i class="fas fa-video me-1"></i>Başlat
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger btn-sm w-100">
                                                <i class="fas fa-times"></i> İptal
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($apt['notes']): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <strong>Notlar:</strong> <?= nl2br(clean($apt['notes'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
