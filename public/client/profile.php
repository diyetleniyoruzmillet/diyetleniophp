<?php
/**
 * Client Profile Page
 * Danışan profil sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Client kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    header('Location: /login.php');
    exit;
}

$user_id = $auth->user()['id'];
$conn = $db->getConnection();

// Profil bilgilerini çek
$stmt = $conn->prepare("
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    WHERE u.id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$profile = $stmt->fetch();

// Son kilo takibi
$weight_stmt = $conn->prepare("
    SELECT weight, recorded_at
    FROM weight_tracking
    WHERE user_id = :user_id
    ORDER BY recorded_at DESC
    LIMIT 1
");
$weight_stmt->execute(['user_id' => $user_id]);
$latest_weight = $weight_stmt->fetch();

// Atanmış diyetisyen
$dietitian_stmt = $conn->prepare("
    SELECT u.id, u.full_name, dp.title, u.profile_photo
    FROM client_dietitian_assignments cda
    INNER JOIN users u ON cda.dietitian_id = u.id
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE cda.client_id = :user_id AND cda.is_active = 1
    LIMIT 1
");
$dietitian_stmt->execute(['user_id' => $user_id]);
$dietitian = $dietitian_stmt->fetch();

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
        background: #dbeafe;
        color: #2563eb;
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

    .dietitian-card {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
        padding: 2rem;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .dietitian-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
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
                    Danışan
                </p>
                <span class="profile-badge">
                    <i class="fas fa-user me-1"></i>
                    Aktif Üye
                </span>
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
            <?php if ($profile['date_of_birth']): ?>
            <div class="info-item">
                <div class="info-label">Doğum Tarihi</div>
                <div class="info-value"><?= date('d.m.Y', strtotime($profile['date_of_birth'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($profile['gender']): ?>
            <div class="info-item">
                <div class="info-label">Cinsiyet</div>
                <div class="info-value">
                    <?php
                    $genders = ['male' => 'Erkek', 'female' => 'Kadın', 'other' => 'Diğer'];
                    echo $genders[$profile['gender']] ?? $profile['gender'];
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($profile['height']): ?>
            <div class="info-item">
                <div class="info-label">Boy</div>
                <div class="info-value"><?= $profile['height'] ?> cm</div>
            </div>
            <?php endif; ?>
            <?php if ($latest_weight): ?>
            <div class="info-item">
                <div class="info-label">Güncel Kilo</div>
                <div class="info-value"><?= $latest_weight['weight'] ?> kg</div>
            </div>
            <?php endif; ?>
            <?php if ($profile['target_weight']): ?>
            <div class="info-item">
                <div class="info-label">Hedef Kilo</div>
                <div class="info-value"><?= $profile['target_weight'] ?> kg</div>
            </div>
            <?php endif; ?>
            <?php if ($profile['activity_level']): ?>
            <div class="info-item">
                <div class="info-label">Aktivite Seviyesi</div>
                <div class="info-value">
                    <?php
                    $levels = [
                        'sedentary' => 'Hareketsiz',
                        'light' => 'Az Hareketli',
                        'moderate' => 'Orta Seviye',
                        'active' => 'Aktif',
                        'very_active' => 'Çok Aktif'
                    ];
                    echo $levels[$profile['activity_level']] ?? $profile['activity_level'];
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($profile['dietary_preferences']): ?>
        <h2 class="section-title">
            <i class="fas fa-utensils me-2"></i>
            Beslenme Tercihleri
        </h2>
        <div class="info-item" style="margin-bottom: 2rem;">
            <div class="info-value"><?= clean($profile['dietary_preferences']) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($profile['health_conditions']): ?>
        <h2 class="section-title">
            <i class="fas fa-heartbeat me-2"></i>
            Sağlık Durumu
        </h2>
        <div class="info-item" style="margin-bottom: 2rem;">
            <div class="info-value" style="line-height: 1.8; white-space: pre-wrap;"><?= clean($profile['health_conditions']) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($profile['allergies']): ?>
        <h2 class="section-title">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Alerjiler
        </h2>
        <div class="info-item" style="margin-bottom: 2rem;">
            <div class="info-value" style="line-height: 1.8; white-space: pre-wrap;"><?= clean($profile['allergies']) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($dietitian): ?>
        <h2 class="section-title">
            <i class="fas fa-user-md me-2"></i>
            Diyetisyenim
        </h2>
        <div class="dietitian-card" style="margin-bottom: 2rem;">
            <?php
            $dp = $dietitian['profile_photo'] ?? '';
            $d_avatar = $dp ? ('/assets/uploads/' . ltrim($dp,'/')) : '/images/default-avatar.png';
            ?>
            <img src="<?= clean($d_avatar) ?>" alt="<?= clean($dietitian['full_name']) ?>" class="dietitian-photo">
            <div style="flex: 1;">
                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">
                    <?= clean($dietitian['full_name']) ?>
                </h3>
                <p style="margin: 0; opacity: 0.9;">
                    <?= clean($dietitian['title']) ?>
                </p>
            </div>
            <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn" style="background: white; color: #56ab2f;">
                <i class="fas fa-eye"></i>
                Profil
            </a>
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/client/dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Dashboard'a Dön
            </a>
            <a href="/client/edit-profile.php" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                Profili Düzenle
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
