<?php
/**
 * Acil Diyetisyen - Emergency Dietitian Support
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// Fetch on-call dietitians
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.profile_photo, u.phone,
           dp.title, dp.specialization, dp.about_me,
           dp.consultation_fee, dp.rating_avg, dp.rating_count,
           dp.experience_years
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.user_type = 'dietitian'
    AND u.is_active = 1
    AND dp.is_approved = 1
    AND dp.is_on_call = 1
    ORDER BY dp.rating_avg DESC
");

$stmt->execute();
$onCallDietitians = $stmt->fetchAll();

$pageTitle = 'Acil Diyetisyen Desteği';
$metaDescription = 'Acil durumlarda 7/24 ulaşabileceğiniz uzman diyetisyenlerimiz';
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    :root {
        --emergency-red: #ef4444;
        --emergency-orange: #f59e0b;
        --primary: #10b981;
        --text-dark: #0f172a;
        --text-light: #64748b;
    }

    .emergency-hero {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 120px 0 80px;
        text-align: center;
        margin-top: 70px;
        position: relative;
        overflow: hidden;
    }

    .emergency-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.1;
    }

    .emergency-hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        position: relative;
        animation: pulse-text 2s infinite;
    }

    .emergency-hero .subtitle {
        font-size: 1.3rem;
        opacity: 0.95;
        margin-bottom: 2rem;
    }

    .emergency-badge {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        padding: 1rem 2rem;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .emergency-badge i {
        animation: pulse-icon 1.5s infinite;
        font-size: 1.5rem;
    }

    @keyframes pulse-text {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    @keyframes pulse-icon {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.7; }
    }

    .info-section {
        padding: 60px 0;
        background: #fff7ed;
    }

    .info-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-align: center;
        height: 100%;
        border: 3px solid #fed7aa;
    }

    .info-card i {
        font-size: 3rem;
        color: var(--emergency-orange);
        margin-bottom: 1rem;
    }

    .info-card h3 {
        color: var(--text-dark);
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .info-card p {
        color: var(--text-light);
        line-height: 1.7;
    }

    .dietitians-section {
        padding: 60px 0;
        background: #f8fafc;
    }

    .dietitian-card {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        border: 3px solid #10b981;
    }

    .dietitian-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
    }

    .dietitian-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 50px rgba(0,0,0,0.15);
    }

    .on-call-badge {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }

    .on-call-badge i {
        animation: pulse-icon 1.5s infinite;
    }

    .dietitian-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #10b981;
        margin: 0 auto 1.5rem;
        display: block;
    }

    .dietitian-name {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-dark);
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .dietitian-title {
        color: var(--primary);
        font-weight: 600;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .dietitian-stats {
        display: flex;
        justify-content: space-around;
        padding: 1.5rem 0;
        border-top: 2px solid #f1f5f9;
        border-bottom: 2px solid #f1f5f9;
        margin-bottom: 1.5rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--primary);
        display: block;
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--text-light);
    }

    .btn-emergency-contact {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        width: 100%;
        transition: all 0.3s;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        font-size: 1rem;
    }

    .btn-emergency-contact:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
    }

    .btn-view-profile {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        width: 100%;
        transition: all 0.3s;
        margin-top: 0.75rem;
    }

    .btn-view-profile:hover {
        background: var(--primary);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
        background: white;
        border-radius: 24px;
    }

    .empty-state i {
        font-size: 5rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .emergency-hero h1 {
            font-size: 2.5rem;
        }

        .emergency-hero {
            padding: 100px 0 60px;
        }
    }
</style>

<div class="emergency-hero">
    <div class="container">
        <div class="emergency-badge">
            <i class="fas fa-heartbeat"></i>
            <span>7/24 Acil Destek</span>
        </div>
        <h1 class="mt-4"><i class="fas fa-ambulance me-3"></i>Acil Diyetisyen Desteği</h1>
        <p class="subtitle">Anında ulaşabileceğiniz uzman diyetisyenlerimiz</p>
    </div>
</div>

<div class="info-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h3>7/24 Hizmet</h3>
                    <p>Gün veya gece fark etmeksizin acil durumlarda uzman diyetisyenlerimize ulaşabilirsiniz.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="info-card">
                    <i class="fas fa-phone-volume"></i>
                    <h3>Hızlı İletişim</h3>
                    <p>Nöbetçi diyetisyenlerimiz acil durumlarınız için anında size destek sağlar.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="info-card">
                    <i class="fas fa-user-md"></i>
                    <h3>Uzman Kadro</h3>
                    <p>Tüm nöbetçi diyetisyenlerimiz deneyimli ve sertifikalı profesyonellerdir.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dietitians-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="font-size: 2.5rem; font-weight: 800; color: var(--text-dark);">
                Şu Anda Nöbetçi Diyetisyenler
            </h2>
            <p style="color: var(--text-light); font-size: 1.1rem;">
                Aşağıdaki diyetisyenlerimiz şu anda nöbetçi ve size anında yardımcı olabilir
            </p>
        </div>

        <?php if (!empty($onCallDietitians)): ?>
        <div class="row">
            <?php foreach ($onCallDietitians as $dietitian): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="dietitian-card">
                    <div class="on-call-badge">
                        <i class="fas fa-circle"></i>
                        NÖBETÇİ
                    </div>

                    <img src="<?= clean($dietitian['profile_photo'] ?? '/images/default-avatar.png') ?>"
                         alt="<?= clean($dietitian['full_name']) ?>"
                         class="dietitian-photo">

                    <h3 class="dietitian-name"><?= clean($dietitian['full_name']) ?></h3>
                    <p class="dietitian-title"><?= clean($dietitian['title']) ?></p>

                    <div class="dietitian-stats">
                        <div class="stat-item">
                            <span class="stat-value">
                                <i class="fas fa-star" style="color: #fbbf24;"></i>
                                <?= number_format($dietitian['rating_avg'], 1) ?>
                            </span>
                            <span class="stat-label">Puan</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $dietitian['experience_years'] ?></span>
                            <span class="stat-label">Yıl Deneyim</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($dietitian['consultation_fee'], 0) ?>₺</span>
                            <span class="stat-label">Ücret</span>
                        </div>
                    </div>

                    <?php if (!empty($dietitian['specialization'])): ?>
                    <div class="mb-3">
                        <small style="color: var(--text-light); font-weight: 600;">
                            <i class="fas fa-heartbeat me-1" style="color: var(--primary);"></i>
                            <?= clean($dietitian['specialization']) ?>
                        </small>
                    </div>
                    <?php endif; ?>

                    <?php if ($auth->check() && $auth->user()['user_type'] === 'client'): ?>
                        <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>#book"
                           class="btn-emergency-contact">
                            <i class="fas fa-phone-alt me-2"></i>
                            Acil Randevu Al
                        </a>
                    <?php else: ?>
                        <a href="/login.php?redirect=<?= urlencode('/dietitian-profile.php?id=' . $dietitian['id']) ?>"
                           class="btn-emergency-contact">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Giriş Yapın
                        </a>
                    <?php endif; ?>

                    <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>"
                       class="btn-view-profile">
                        <i class="fas fa-user me-2"></i>
                        Profili Görüntüle
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-moon"></i>
            <h3 style="color: var(--text-dark); font-weight: 700; margin-bottom: 1rem;">
                Şu Anda Nöbetçi Diyetisyen Bulunmuyor
            </h3>
            <p style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 2rem;">
                Acil olmayan durumlar için tüm diyetisyenlerimize normal çalışma saatlerinde ulaşabilirsiniz.
            </p>
            <a href="/dietitians.php" class="btn btn-primary" style="padding: 1rem 2.5rem; border-radius: 12px; font-weight: 700;">
                <i class="fas fa-users me-2"></i>
                Tüm Diyetisyenleri Görüntüle
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
