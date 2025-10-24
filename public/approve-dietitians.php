<?php
/**
 * Web-based Dietitian Approval Tool
 */

// Security token
$requiredToken = md5('approve-dietitians-2025-' . date('Y-m-d'));
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $requiredToken) {
    die("🔒 Access Denied. Invalid token.<br><br>Today's token: <strong>{$requiredToken}</strong>");
}

require_once __DIR__ . '/../includes/bootstrap.php';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diyetisyen Onaylama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .container {
            max-width: 1000px;
        }
        .card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 30px;
            border: none;
        }
        .btn-approve {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            font-weight: bold;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(67, 233, 123, 0.4);
        }
        .btn-approve:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(67, 233, 123, 0.6);
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .success-msg {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            font-size: 1.2rem;
            font-weight: bold;
            animation: fadeInUp 0.5s ease;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">
                    <i class="fas fa-user-md me-3"></i>Diyetisyen Onaylama Aracı
                </h2>
            </div>
            <div class="card-body p-4">

                <?php
                $conn = $db->getConnection();

                // Mevcut durumu göster
                $stmt = $conn->query("
                    SELECT COUNT(*) as total,
                           SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) as active,
                           SUM(CASE WHEN dp.is_approved = 1 THEN 1 ELSE 0 END) as approved,
                           SUM(CASE WHEN u.is_active = 1 AND dp.is_approved = 1 THEN 1 ELSE 0 END) as active_approved
                    FROM users u
                    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
                    WHERE u.user_type = 'dietitian'
                ");
                $stats = $stmt->fetch();

                if (isset($_POST['approve_all'])):
                    // Tüm diyetisyenleri onayla
                    $stmt = $conn->prepare("
                        UPDATE dietitian_profiles dp
                        INNER JOIN users u ON dp.user_id = u.id
                        SET dp.is_approved = 1,
                            u.is_active = 1
                        WHERE u.user_type = 'dietitian'
                    ");
                    $stmt->execute();
                    $affectedRows = $stmt->rowCount();

                    // Güncel istatistikleri al
                    $stmt = $conn->query("
                        SELECT COUNT(*) as total,
                               SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) as active,
                               SUM(CASE WHEN dp.is_approved = 1 THEN 1 ELSE 0 END) as approved,
                               SUM(CASE WHEN u.is_active = 1 AND dp.is_approved = 1 THEN 1 ELSE 0 END) as active_approved
                        FROM users u
                        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
                        WHERE u.user_type = 'dietitian'
                    ");
                    $stats = $stmt->fetch();
                    ?>
                    <div class="success-msg text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                        ✅ Tüm diyetisyenler başarıyla onaylandı!<br>
                        <small style="opacity: 0.9;">Güncellenen kayıt sayısı: <?= $affectedRows ?></small>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-box text-center">
                            <div class="stat-number"><?= $stats['total'] ?></div>
                            <div class="text-muted">Toplam Diyetisyen</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box text-center">
                            <div class="stat-number text-success"><?= $stats['active'] ?></div>
                            <div class="text-muted">Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box text-center">
                            <div class="stat-number text-info"><?= $stats['approved'] ?></div>
                            <div class="text-muted">Onaylı</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box text-center">
                            <div class="stat-number text-primary"><?= $stats['active_approved'] ?></div>
                            <div class="text-muted">Aktif & Onaylı</div>
                        </div>
                    </div>
                </div>

                <?php
                // Diyetisyen listesi
                $stmt = $conn->query("
                    SELECT u.id, u.full_name, u.email, u.is_active,
                           dp.title, dp.specialization, dp.is_approved, dp.experience_years
                    FROM users u
                    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
                    WHERE u.user_type = 'dietitian'
                    ORDER BY u.id
                ");
                $dietitians = $stmt->fetchAll();
                ?>

                <h4 class="mb-3">
                    <i class="fas fa-list me-2"></i>Diyetisyen Listesi (<?= count($dietitians) ?>)
                </h4>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ad Soyad</th>
                                <th>Email</th>
                                <th>Uzmanlık</th>
                                <th>Tecrübe</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dietitians as $d): ?>
                            <tr>
                                <td><?= $d['id'] ?></td>
                                <td><strong><?= clean($d['full_name']) ?></strong></td>
                                <td><small><?= clean($d['email']) ?></small></td>
                                <td><?= clean($d['specialization']) ?></td>
                                <td><?= $d['experience_years'] ?> yıl</td>
                                <td>
                                    <?php if ($d['is_active'] && $d['is_approved']): ?>
                                        <span class="badge bg-success">✅ Aktif & Onaylı</span>
                                    <?php elseif ($d['is_approved']): ?>
                                        <span class="badge bg-info">Onaylı</span>
                                    <?php elseif ($d['is_active']): ?>
                                        <span class="badge bg-warning">Sadece Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pasif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($stats['active_approved'] < $stats['total']): ?>
                <div class="text-center mt-4">
                    <form method="POST">
                        <button type="submit" name="approve_all" class="btn btn-approve">
                            <i class="fas fa-check-double me-2"></i>
                            Tüm Diyetisyenleri Onayla ve Aktif Et
                        </button>
                    </form>
                    <p class="text-muted mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Bu işlem tüm diyetisyenleri otomatik olarak onaylar ve aktif eder.
                    </p>
                </div>
                <?php else: ?>
                <div class="alert alert-success text-center mt-4">
                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                    <strong>Tüm diyetisyenler zaten aktif ve onaylı!</strong>
                </div>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-top">
                    <a href="/dietitians.php" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Diyetisyen Listesini Görüntüle
                    </a>
                    <a href="/admin/dashboard.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-tachometer-alt me-2"></i>Admin Paneli
                    </a>
                </div>

            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-white">
                <i class="fas fa-lock me-1"></i>
                <strong>Güvenlik:</strong> Bu dosyayı kullandıktan sonra silin!<br>
                <code style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px;">
                    rm public/approve-dietitians.php
                </code>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
