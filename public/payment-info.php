<?php
/**
 * Diyetlenio - Ödeme Bilgileri Sayfası
 * Randevu sonrası danışana IBAN bilgileri gösterilir
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    redirect('/login.php');
}

$appointmentId = $_GET['appointment'] ?? null;
if (!$appointmentId) {
    redirect('/client/appointments.php');
}

$conn = $db->getConnection();
$userId = $auth->id();

// Randevu bilgilerini çek
$stmt = $conn->prepare("
    SELECT a.*, 
           d.full_name as dietitian_name,
           dp.consultation_fee,
           dp.iban as dietitian_iban
    FROM appointments a
    INNER JOIN users d ON a.dietitian_id = d.id
    INNER JOIN dietitian_profiles dp ON d.id = dp.user_id
    WHERE a.id = ? AND a.client_id = ?
");
$stmt->execute([$appointmentId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    redirect('/client/appointments.php');
}

// Site ayarlarından default IBAN'ı çek
$stmt = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('company_iban', 'bank_name', 'account_holder', 'payment_instructions')");
$siteSettings = [];
while ($row = $stmt->fetch()) {
    $siteSettings[$row['setting_key']] = $row['setting_value'];
}

// Diyetisyenin IBAN'ı varsa onu kullan, yoksa site IBAN'ını
$ibanToUse = $appointment['dietitian_iban'] ?: ($siteSettings['company_iban'] ?? '');
$accountHolder = $appointment['dietitian_iban'] ? $appointment['dietitian_name'] : ($siteSettings['account_holder'] ?? 'Diyetlenio');
$bankName = $siteSettings['bank_name'] ?? 'Banka';

$pageTitle = 'Ödeme Bilgileri';
include __DIR__ . '/../includes/partials/header.php';
    <style>
        body { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); min-height: 100vh; display: flex; align-items: center; }
        .payment-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 600px; margin: 0 auto; }
        .iban-box { background: #e6fffa; padding: 20px; border-radius: 15px; border: 2px dashed #11998e; margin: 20px 0; }
        .iban-number { font-size: 1.5rem; font-weight: 700; color: #11998e; letter-spacing: 2px; font-family: 'Courier New', monospace; }
        .copy-btn { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="text-center mb-4">
                <i class="fas fa-university fa-4x text-success mb-3"></i>
                <h2>Ödeme Bilgileri</h2>
                <p class="text-muted">Randevunuz başarıyla oluşturuldu!</p>
            </div>

            <div class="alert alert-success">
                <h5><i class="fas fa-calendar-check me-2"></i>Randevu Detayları</h5>
                <p class="mb-1"><strong>Diyetisyen:</strong> <?= clean($appointment['dietitian_name']) ?></p>
                <p class="mb-1"><strong>Tarih:</strong> <?= formatDate($appointment['appointment_date']) ?></p>
                <p class="mb-0"><strong>Saat:</strong> <?= substr($appointment['start_time'], 0, 5) ?></p>
            </div>

            <div class="iban-box">
                <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>IBAN Bilgileri</h5>
                
                <div class="mb-2">
                    <small class="text-muted">Banka</small>
                    <p class="mb-0"><strong><?= clean($bankName) ?></strong></p>
                </div>

                <div class="mb-2">
                    <small class="text-muted">Hesap Sahibi</small>
                    <p class="mb-0"><strong><?= clean($accountHolder) ?></strong></p>
                </div>

                <div class="mb-3">
                    <small class="text-muted">IBAN Numarası</small>
                    <div class="d-flex align-items-center">
                        <p class="iban-number mb-0 flex-grow-1" id="iban"><?= clean($ibanToUse) ?></p>
                        <button class="btn btn-outline-success copy-btn" onclick="copyIBAN()">
                            <i class="fas fa-copy"></i> Kopyala
                        </button>
                    </div>
                </div>

                <div class="alert alert-warning mb-0">
                    <small>
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Ödeme Tutarı:</strong> <?= number_format($appointment['consultation_fee'], 2) ?> ₺
                    </small>
                </div>
            </div>

            <div class="alert alert-info">
                <small>
                    <?= clean($siteSettings['payment_instructions'] ?? 'Lütfen ödeme yaptıktan sonra dekontunuzu yükleyin.') ?>
                </small>
            </div>

            <div class="d-grid gap-2">
                <a href="/client/payment-upload.php?appointment_id=<?= $appointment['id'] ?>" class="btn btn-success btn-lg">
                    <i class="fas fa-upload me-2"></i>Dekont Yükle
                </a>
                <a href="/client/appointments.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Randevularıma Dön
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyIBAN() {
            const iban = document.getElementById('iban').textContent;
            navigator.clipboard.writeText(iban.replace(/\s/g, '')).then(() => {
                alert('IBAN kopyalandı!');
            });
        }
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
