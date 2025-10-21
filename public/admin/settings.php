<?php
/**
 * Diyetlenio - Admin Site Ayarları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Ayarları kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO site_settings (setting_key, setting_value, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $value]);
        }
        setFlash('success', 'Ayarlar kaydedildi.');
        redirect('/admin/settings.php');
    }
}

// Ayarları çek
$stmt = $conn->query("SELECT * FROM site_settings ORDER BY setting_key");
$settingsData = $stmt->fetchAll();

$settings = [];
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$pageTitle = 'Site Ayarları';
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
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/admin/users.php">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </a>
                        <a class="nav-link active" href="/admin/settings.php">
                            <i class="fas fa-cog me-2"></i>Site Ayarları
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
                    <h2 class="mb-4">Site Ayarları</h2>

                    <?php if (hasFlash()): ?>
                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Genel Ayarlar</h5>

                                        <div class="mb-3">
                                            <label class="form-label">Site Adı</label>
                                            <input type="text" name="settings[site_name]" class="form-control"
                                                   value="<?= clean($settings['site_name'] ?? 'Diyetlenio') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Site Açıklaması</label>
                                            <textarea name="settings[site_description]" class="form-control" rows="3"><?= clean($settings['site_description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">İletişim Email</label>
                                            <input type="email" name="settings[contact_email]" class="form-control"
                                                   value="<?= clean($settings['contact_email'] ?? '') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">İletişim Telefon</label>
                                            <input type="text" name="settings[contact_phone]" class="form-control"
                                                   value="<?= clean($settings['contact_phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Randevu Ayarları</h5>

                                        <div class="mb-3">
                                            <label class="form-label">Minimum Randevu Süresi (dakika)</label>
                                            <input type="number" name="settings[appointment_duration]" class="form-control"
                                                   value="<?= clean($settings['appointment_duration'] ?? '30') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">İptal Süresi (saat)</label>
                                            <input type="number" name="settings[cancellation_hours]" class="form-control"
                                                   value="<?= clean($settings['cancellation_hours'] ?? '24') ?>">
                                            <small class="text-muted">Randevuya kaç saat kala iptal edilebilir</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Ödeme Ayarları</h5>

                                        <div class="mb-3">
                                            <label class="form-label">Komisyon Oranı (%)</label>
                                            <input type="number" name="settings[commission_rate]" class="form-control"
                                                   step="0.01" value="<?= clean($settings['commission_rate'] ?? '10') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Para Birimi</label>
                                            <input type="text" name="settings[currency]" class="form-control"
                                                   value="<?= clean($settings['currency'] ?? 'TRY') ?>">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="save_settings" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>Ayarları Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
