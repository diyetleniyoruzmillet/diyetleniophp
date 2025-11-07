<?php
/**
 * Admin Profile Page
 * Admin profil sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    header('Location: /login.php');
    exit;
}

$user_id = $auth->user()['id'];
$conn = $db->getConnection();

// Profil bilgilerini çek
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$profile = $stmt->fetch();

$pageTitle = 'Profilim';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 100px auto 50px;
        padding: 0 2rem;
    }

    .profile-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
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
        border: 4px solid #ef4444;
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
        background: #fee2e2;
        color: #dc2626;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
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
        margin-top: 2rem;
    }

    .btn-outline {
        background: white;
        color: #ef4444;
        border: 2px solid #ef4444;
    }

    .btn-outline:hover {
        background: #ef4444;
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
                    Sistem Yöneticisi
                </p>
                <span class="profile-badge">
                    <i class="fas fa-shield-alt me-1"></i>
                    Admin
                </span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">E-posta</div>
                <div class="info-value"><?= clean($profile['email']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Telefon</div>
                <div class="info-value"><?= clean($profile['phone'] ?? 'Belirtilmemiş') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Hesap Oluşturma</div>
                <div class="info-value"><?= date('d.m.Y', strtotime($profile['created_at'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Durum</div>
                <div class="info-value">
                    <?php if ($profile['is_active']): ?>
                        <span style="color: #10b981;">
                            <i class="fas fa-check-circle me-1"></i>
                            Aktif
                        </span>
                    <?php else: ?>
                        <span style="color: #ef4444;">
                            <i class="fas fa-times-circle me-1"></i>
                            Pasif
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="/admin/dashboard.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Dashboard'a Dön
        </a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
