<?php
/**
 * Admin Panel - Randevu Slot Süresi Ayarları
 * Diyetisyenler için randevu slot sürelerini yönetme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$availabilityService = new AvailabilityService($db);
$errors = [];
$success = false;

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            // Global varsayılan süre ayarla
            if ($action === 'set_global_default') {
                $duration = (int) ($_POST['duration'] ?? 45);

                if (!in_array($duration, [30, 45, 60])) {
                    throw new Exception('Geçerli süreler: 30, 45 veya 60 dakika');
                }

                // Tüm aktif diyetisyenlerin slot sürelerini güncelle
                $stmt = $conn->prepare("
                    UPDATE dietitian_availability da
                    INNER JOIN users u ON u.id = da.dietitian_id
                    SET da.slot_duration = ?
                    WHERE u.user_type = 'dietitian' AND u.is_active = 1
                ");
                $stmt->execute([$duration]);

                $affectedRows = $stmt->rowCount();
                setFlash('success', "Tüm diyetisyenler için varsayılan slot süresi {$duration} dakikaya güncellendi. ({$affectedRows} kayıt güncellendi)");
                $success = true;
            }

            // Belirli diyetisyen için süre ayarla
            if ($action === 'set_dietitian_duration') {
                $dietitianId = (int) ($_POST['dietitian_id'] ?? 0);
                $duration = (int) ($_POST['dietitian_duration'] ?? 45);

                if (!in_array($duration, [30, 45, 60])) {
                    throw new Exception('Geçerli süreler: 30, 45 veya 60 dakika');
                }

                $availabilityService->updateSlotDuration($dietitianId, $duration);

                setFlash('success', "Diyetisyen için slot süresi {$duration} dakikaya güncellendi.");
                $success = true;
            }

            redirect('/admin/slot-duration-settings.php');

        } catch (Exception $e) {
            $errors[] = 'Hata: ' . $e->getMessage();
        }
    }
}

// Tüm diyetisyenleri ve slot sürelerini getir
$stmt = $conn->query("
    SELECT
        u.id,
        u.full_name,
        u.email,
        COALESCE(
            (SELECT DISTINCT slot_duration
             FROM dietitian_availability
             WHERE dietitian_id = u.id
             LIMIT 1),
            45
        ) as current_duration,
        (SELECT COUNT(*)
         FROM dietitian_availability
         WHERE dietitian_id = u.id) as schedule_count
    FROM users u
    WHERE u.user_type = 'dietitian'
    AND u.is_active = 1
    ORDER BY u.full_name
");
$dietitians = $stmt->fetchAll();

// İstatistikler
$stats = [
    '30' => 0,
    '45' => 0,
    '60' => 0
];

foreach ($dietitians as $dietitian) {
    $duration = (string) $dietitian['current_duration'];
    if (isset($stats[$duration])) {
        $stats[$duration]++;
    }
}

$pageTitle = 'Slot Süresi Ayarları';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .admin-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .page-header h1 {
            font-weight: 900;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .stat-icon.duration-30 { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.duration-45 { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .stat-icon.duration-60 { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 600;
        }

        .card-custom {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary);
        }

        .global-controls {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        .duration-selector {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .duration-option {
            flex: 1;
            position: relative;
        }

        .duration-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .duration-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 25px;
            border: 3px solid #e5e7eb;
            border-radius: 16px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 700;
        }

        .duration-option label i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #9ca3af;
        }

        .duration-option input[type="radio"]:checked + label {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102,126,234,0.3);
        }

        .duration-option input[type="radio"]:checked + label i {
            color: white;
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-apply:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16,185,129,0.3);
        }

        .dietitians-table {
            width: 100%;
            margin-top: 20px;
        }

        .dietitians-table thead {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        }

        .dietitians-table th {
            padding: 18px;
            font-weight: 800;
            color: #374151;
            border: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dietitians-table td {
            padding: 20px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }

        .dietitians-table tr:hover {
            background: #f9fafb;
        }

        .duration-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .duration-badge.duration-30 { background: #d1fae5; color: #065f46; }
        .duration-badge.duration-45 { background: #e0e7ff; color: #3730a3; }
        .duration-badge.duration-60 { background: #fef3c7; color: #92400e; }

        .btn-change {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-change:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.3);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            border-bottom: none;
            padding: 30px 30px 20px;
        }

        .modal-body {
            padding: 20px 30px 30px;
        }

        .alert {
            border-radius: 16px;
            padding: 20px 25px;
            border: none;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="/admin/dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Admin Paneline Dön
        </a>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                        <div><?= clean($error) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1><i class="fas fa-clock"></i> Randevu Slot Süresi Ayarları</h1>
            <p class="text-muted mb-0" style="font-size: 1.1rem;">
                Diyetisyenler için randevu slot sürelerini buradan yönetebilirsiniz
            </p>
        </div>

        <!-- İstatistikler -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon duration-30">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-value"><?= $stats['30'] ?></div>
                <div class="stat-label">30 Dakika Kullanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon duration-45">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?= $stats['45'] ?></div>
                <div class="stat-label">45 Dakika Kullanan (Varsayılan)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon duration-60">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?= $stats['60'] ?></div>
                <div class="stat-label">60 Dakika Kullanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= count($dietitians) ?></div>
                <div class="stat-label">Toplam Diyetisyen</div>
            </div>
        </div>

        <!-- Global Ayar -->
        <div class="card-custom">
            <h3 class="section-title">
                <i class="fas fa-globe"></i>
                Tüm Diyetisyenler İçin Varsayılan Süre
            </h3>

            <div class="global-controls">
                <p style="font-weight: 600; color: #374151; margin-bottom: 5px;">
                    <i class="fas fa-info-circle text-primary"></i>
                    Aşağıdaki ayar tüm aktif diyetisyenlerin slot sürelerini değiştirecektir
                </p>
                <small class="text-muted">Bu işlem geri alınamaz. Bireysel ayarlar gerekiyorsa aşağıdaki tablodan yapabilirsiniz.</small>

                <form method="POST" onsubmit="return confirm('TÜM diyetisyenler için slot süresi değiştirilecek. Emin misiniz?');">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="set_global_default">

                    <div class="duration-selector">
                        <div class="duration-option">
                            <input type="radio" name="duration" value="30" id="global-30">
                            <label for="global-30">
                                <i class="fas fa-bolt"></i>
                                <strong style="font-size: 1.5rem;">30</strong>
                                <span>Dakika</span>
                                <small style="margin-top: 5px; opacity: 0.8;">Hızlı Görüşme</small>
                            </label>
                        </div>
                        <div class="duration-option">
                            <input type="radio" name="duration" value="45" id="global-45" checked>
                            <label for="global-45">
                                <i class="fas fa-clock"></i>
                                <strong style="font-size: 1.5rem;">45</strong>
                                <span>Dakika</span>
                                <small style="margin-top: 5px; opacity: 0.8;">Varsayılan</small>
                            </label>
                        </div>
                        <div class="duration-option">
                            <input type="radio" name="duration" value="60" id="global-60">
                            <label for="global-60">
                                <i class="fas fa-hourglass-half"></i>
                                <strong style="font-size: 1.5rem;">60</strong>
                                <span>Dakika</span>
                                <small style="margin-top: 5px; opacity: 0.8;">Detaylı Görüşme</small>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-apply">
                        <i class="fas fa-check-circle"></i>
                        Tüm Diyetisyenlere Uygula
                    </button>
                </form>
            </div>
        </div>

        <!-- Bireysel Ayarlar -->
        <div class="card-custom">
            <h3 class="section-title">
                <i class="fas fa-user-cog"></i>
                Diyetisyen Bazında Ayarlar
            </h3>

            <table class="dietitians-table">
                <thead>
                    <tr>
                        <th>Diyetisyen</th>
                        <th>E-posta</th>
                        <th>Program Durumu</th>
                        <th>Mevcut Slot Süresi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dietitians as $dietitian): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 1rem;"><?= clean($dietitian['full_name']) ?></strong>
                            </td>
                            <td>
                                <span style="color: #6b7280;"><?= clean($dietitian['email']) ?></span>
                            </td>
                            <td>
                                <?php if ($dietitian['schedule_count'] > 0): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i>
                                        <?= $dietitian['schedule_count'] ?> zaman dilimi
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Program yok
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="duration-badge duration-<?= $dietitian['current_duration'] ?>">
                                    <i class="fas fa-clock"></i>
                                    <strong><?= $dietitian['current_duration'] ?> dakika</strong>
                                </span>
                            </td>
                            <td>
                                <?php if ($dietitian['schedule_count'] > 0): ?>
                                    <button type="button" class="btn-change"
                                            onclick="openDietitianModal(<?= $dietitian['id'] ?>, '<?= clean($dietitian['full_name']) ?>', <?= $dietitian['current_duration'] ?>)">
                                        <i class="fas fa-edit"></i>
                                        Değiştir
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.9rem;">
                                        <i class="fas fa-info-circle"></i>
                                        Önce program oluşturmalı
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dietitian Duration Modal -->
    <div class="modal fade" id="dietitianDurationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalDietitianName"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="dietitianDurationForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="set_dietitian_duration">
                    <input type="hidden" name="dietitian_id" id="modalDietitianId">

                    <div class="modal-body">
                        <label class="form-label fw-bold mb-3">Yeni Slot Süresi</label>
                        <div class="duration-selector">
                            <div class="duration-option">
                                <input type="radio" name="dietitian_duration" value="30" id="dietitian-30">
                                <label for="dietitian-30">
                                    <i class="fas fa-bolt"></i>
                                    <strong style="font-size: 1.3rem;">30</strong>
                                    <span>dk</span>
                                </label>
                            </div>
                            <div class="duration-option">
                                <input type="radio" name="dietitian_duration" value="45" id="dietitian-45">
                                <label for="dietitian-45">
                                    <i class="fas fa-clock"></i>
                                    <strong style="font-size: 1.3rem;">45</strong>
                                    <span>dk</span>
                                </label>
                            </div>
                            <div class="duration-option">
                                <input type="radio" name="dietitian_duration" value="60" id="dietitian-60">
                                <label for="dietitian-60">
                                    <i class="fas fa-hourglass-half"></i>
                                    <strong style="font-size: 1.3rem;">60</strong>
                                    <span>dk</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn-apply w-100">
                            <i class="fas fa-save"></i>
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('dietitianDurationModal'));

        function openDietitianModal(id, name, currentDuration) {
            document.getElementById('modalDietitianId').value = id;
            document.getElementById('modalDietitianName').textContent = name + ' - Slot Süresi Ayarı';

            // Mevcut süreyi seç
            document.getElementById('dietitian-' + currentDuration).checked = true;

            modal.show();
        }
    </script>
</body>
</html>
