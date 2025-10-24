<?php
/**
 * Diyetisyen Onay Bekleme Sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    redirect('/login.php');
}

$pageTitle = 'Onay Süreci';
$metaDescription = 'Diyetisyen hesabınız onay sürecinde. Başvurunuz inceleniyor.';

// Profil onay durumunu ve varsa ret gerekçesini çek
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT is_approved, rejection_reason FROM dietitian_profiles WHERE user_id = ? LIMIT 1");
$stmt->execute([$auth->user()->getId()]);
$profile = $stmt->fetch() ?: ['is_approved' => 0, 'rejection_reason' => null];

// Onaylandı ise dashboard'a yönlendir
if ((int)$profile['is_approved'] === 1) {
    redirect('/dietitian/dashboard.php');
}

include __DIR__ . '/../../includes/partials/header.php';
?>

<div class="container" style="max-width: 900px;">
    <div class="my-5 p-4 p-md-5 rounded-lg glass-card border-soft shadow-lg">
        <div class="text-center mb-4">
            <i class="fas fa-hourglass-half fa-3x text-warning mb-3"></i>
            <h2 class="fw-bold">Hesabınız Onay Sürecinde</h2>
            <p class="text-muted-700">Başvurunuz uzman ekibimiz tarafından inceleniyor. Bu süreç genellikle 24-48 saat sürer.</p>
        </div>

        <?php if (!empty($profile['rejection_reason'])): ?>
            <div class="alert alert-warning">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle me-2 mt-1"></i>
                    <div>
                        <strong>Önceki başvurunuzla ilgili not:</strong>
                        <div class="mt-1"><?= clean($profile['rejection_reason']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="p-4 border-soft rounded-lg h-100">
                    <h5 class="fw-bold mb-3"><i class="fas fa-clipboard-check me-2 text-success"></i>Onay Kriterleri</h5>
                    <ul class="mb-0">
                        <li>Geçerli iletişim ve kimlik bilgileri</li>
                        <li>Diploma/uzmanlık bilgileri</li>
                        <li>Profil fotoğrafı ve tanıtım metni</li>
                        <li>Ücretlendirme ve müsaitlik bilgileri</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-4 border-soft rounded-lg h-100">
                    <h5 class="fw-bold mb-3"><i class="fas fa-tasks me-2 text-primary"></i>Ne Yapabilirsiniz?</h5>
                    <ul class="mb-0">
                        <li>Profil bilgilerinizi gözden geçirin ve tamamlayın</li>
                        <li>Eksik belge/alan varsa yükleyin/doldurun</li>
                        <li>Gerekirse destek ekibiyle iletişime geçin</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
            <a href="/dietitian/profile.php" class="btn btn-gradient">
                <i class="fas fa-user-edit me-2"></i>Profilimi Düzenle
            </a>
            <a href="/" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Ana Sayfa
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
