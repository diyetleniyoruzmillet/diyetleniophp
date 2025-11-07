<?php
/**
 * Dietitian Profile Page
 * Diyetisyen profil sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Diyetisyen kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    header('Location: /login.php');
    exit;
}

$user_id = $auth->user()['id'];
$conn = $db->getConnection();

// Profil bilgilerini çek
$stmt = $conn->prepare("
    SELECT u.*, dp.*
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$profile = $stmt->fetch();

$pageTitle = 'Profilim';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    .profile-container {
        max-width: 1000px;
        margin: 100px auto 50px;
        padding: 0 2rem;
    }

    .profile-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .profile-photo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #56ab2f;
    }

    .profile-info h1 {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .profile-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 600;
        background: #d1fae5;
        color: #059669;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .info-item {
        background: #f8fafc;
        padding: 1.25rem;
        border-radius: 12px;
    }

    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1rem;
        color: #0f172a;
        font-weight: 600;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.5rem;
    }

    .btn {
        padding: 0.875rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
    }

    .btn-outline {
        background: white;
        color: #56ab2f;
        border: 2px solid #56ab2f;
    }

    .btn-outline:hover {
        background: #56ab2f;
        color: white;
    }
</style>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <?php
            $pp = $profile['profile_photo'] ?? '';
            $avatar = $pp ? ('/assets/uploads/' . ltrim($pp,'/')) : '/images/default-avatar.png';
            ?>
            <img src="<?= clean($avatar) ?>" alt="<?= clean($profile['full_name']) ?>" class="profile-photo">
            <div class="profile-info">
                <h1><?= clean($profile['full_name']) ?></h1>
                <p style="color: #64748b; margin-bottom: 0.5rem;">
                    <?= clean($profile['title'] ?? 'Diyetisyen') ?>
                </p>
                <?php if ($profile['is_approved']): ?>
                    <span class="profile-badge">
                        <i class="fas fa-check-circle me-1"></i>
                        Onaylı Profil
                    </span>
                <?php else: ?>
                    <span class="profile-badge" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-clock me-1"></i>
                        Onay Bekliyor
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="section-title">
            <i class="fas fa-user me-2"></i>
            Kişisel Bilgiler
        </h2>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">E-posta</div>
                <div class="info-value"><?= clean($profile['email']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Telefon</div>
                <div class="info-value"><?= clean($profile['phone'] ?? 'Belirtilmemiş') ?></div>
            </div>
            <?php if ($profile['experience_years']): ?>
            <div class="info-item">
                <div class="info-label">Deneyim</div>
                <div class="info-value"><?= $profile['experience_years'] ?> Yıl</div>
            </div>
            <?php endif; ?>
            <?php if ($profile['consultation_fee']): ?>
            <div class="info-item">
                <div class="info-label">Danışma Ücreti</div>
                <div class="info-value"><?= number_format($profile['consultation_fee'], 0) ?> ₺</div>
            </div>
            <?php endif; ?>
            <?php if ($profile['rating_avg']): ?>
            <div class="info-item">
                <div class="info-label">Puan Ortalaması</div>
                <div class="info-value">
                    <i class="fas fa-star" style="color: #fbbf24;"></i>
                    <?= number_format($profile['rating_avg'], 1) ?> / 5.0
                </div>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <div class="info-label">Toplam Danışan</div>
                <div class="info-value"><?= $profile['total_clients'] ?? 0 ?></div>
            </div>
        </div>

        <?php if ($profile['specialization']): ?>
        <h2 class="section-title">
            <i class="fas fa-heartbeat me-2"></i>
            Uzmanlık Alanı
        </h2>
        <div class="info-item" style="margin-bottom: 2rem;">
            <div class="info-value"><?= clean($profile['specialization']) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($profile['about_me']): ?>
        <h2 class="section-title">
            <i class="fas fa-info-circle me-2"></i>
            Hakkımda
        </h2>
        <div class="info-item" style="margin-bottom: 2rem;">
            <div class="info-value" style="line-height: 1.8; white-space: pre-wrap;"><?= clean($profile['about_me']) ?></div>
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/dietitian/dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Dashboard'a Dön
            </a>
            <a href="/dietitian/edit-profile.php" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                Profili Düzenle
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
