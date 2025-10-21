<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$auth->requireAdmin();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
    }
    setFlash('success', 'Ayarlar kaydedildi!');
    redirect('/admin/site-settings.php');
}

// Ayarları çek
$stmt = $conn->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Site Ayarları - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Site Ayarları</h1>

        <?php if ($flash = getFlash('success')): ?>
            <div class="alert alert-success"><?= $flash ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="card mb-4">
                <div class="card-header"><h5>Genel Ayarlar</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Site Adı</label>
                        <input type="text" name="settings[site_name]" class="form-control" value="<?= clean($settings['site_name'] ?? 'Diyetlenio') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Site Açıklaması</label>
                        <textarea name="settings[site_description]" class="form-control" rows="3"><?= clean($settings['site_description'] ?? '') ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>İletişim Email</label>
                            <input type="email" name="settings[contact_email]" class="form-control" value="<?= clean($settings['contact_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>İletişim Telefon</label>
                            <input type="text" name="settings[contact_phone]" class="form-control" value="<?= clean($settings['contact_phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5>Ödeme Ayarları (IBAN)</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Banka Adı</label>
                        <input type="text" name="settings[bank_name]" class="form-control" value="<?= clean($settings['bank_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>IBAN Numarası</label>
                        <input type="text" name="settings[company_iban]" class="form-control" placeholder="TR00 0000 0000 0000 0000 0000 00" value="<?= clean($settings['company_iban'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Hesap Sahibi</label>
                        <input type="text" name="settings[account_holder]" class="form-control" value="<?= clean($settings['account_holder'] ?? '') ?>">
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Bu IBAN bilgileri, danışanların ödeme yapması için randevu sayfasında gösterilecektir.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5>Sosyal Medya</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Facebook URL</label>
                        <input type="url" name="settings[facebook_url]" class="form-control" value="<?= clean($settings['facebook_url'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Instagram URL</label>
                        <input type="url" name="settings[instagram_url]" class="form-control" value="<?= clean($settings['instagram_url'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Twitter URL</label>
                        <input type="url" name="settings[twitter_url]" class="form-control" value="<?= clean($settings['twitter_url'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5>Randevu Ayarları</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Randevu Süresi (dakika)</label>
                            <input type="number" name="settings[appointment_duration]" class="form-control" value="<?= clean($settings['appointment_duration'] ?? '45') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>İptal Süresi (saat)</label>
                            <input type="number" name="settings[cancellation_hours]" class="form-control" value="<?= clean($settings['cancellation_hours'] ?? '2') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Hatırlatma Süresi (saat)</label>
                            <input type="number" name="settings[reminder_hours]" class="form-control" value="<?= clean($settings['reminder_hours'] ?? '1') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save me-2"></i>Ayarları Kaydet
            </button>
            <a href="/admin/dashboard.php" class="btn btn-secondary btn-lg">İptal</a>
        </form>
    </div>
</body>
</html>
