<?php
/**
 * Dietitian Profile Page - Diyetisyen Profil Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// Get dietitian ID from URL
$dietitianId = (int) ($_GET['id'] ?? 0);

if (!$dietitianId) {
    header('Location: /dietitians.php');
    exit;
}

// Fetch dietitian details
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.profile_photo, u.email, u.phone,
           dp.title, dp.specialization, dp.about_me, dp.education,
           dp.certificates, dp.experience_years, dp.consultation_fee,
           dp.rating_avg, dp.rating_count, dp.total_clients,
           dp.is_approved, dp.is_on_call
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = ? AND u.user_type = 'dietitian' AND u.is_active = 1 AND dp.is_approved = 1
");

$stmt->execute([$dietitianId]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    header('Location: /dietitians.php');
    exit;
}

// Fetch dietitian availability
$availabilityStmt = $conn->prepare("
    SELECT * FROM dietitian_availability
    WHERE dietitian_id = ? AND is_available = 1
    ORDER BY day_of_week, start_time
");
$availabilityStmt->execute([$dietitianId]);
$availability = $availabilityStmt->fetchAll();

// Fetch recent reviews
$reviewsStmt = $conn->prepare("
    SELECT r.*, u.full_name as client_name, u.profile_photo as client_photo
    FROM reviews r
    INNER JOIN users u ON r.client_id = u.id
    WHERE r.dietitian_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviewsStmt->execute([$dietitianId]);
$reviews = $reviewsStmt->fetchAll();

$pageTitle = $dietitian['full_name'] . ' - Profil';
$metaDescription = $dietitian['title'] . ' - ' . substr($dietitian['about_me'], 0, 150);
include __DIR__ . '/../includes/partials/header.php';
?>

<style>
    :root {
        --primary: #10b981;
        --primary-dark: #059669;
        --text-dark: #0f172a;
        --text-light: #64748b;
        --bg-light: #f8fafc;
        --border-color: #e2e8f0;
    }

    body {
        background: var(--bg-light);
        font-family: 'Inter', sans-serif;
    }

    .profile-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 4rem 0 2rem;
        margin-top: 70px;
    }

    .profile-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        margin-top: -4rem;
        position: relative;
        z-index: 10;
    }

    .profile-photo {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        object-fit: cover;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }

    .stats-card {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        border: 2px solid #bbf7d0;
    }

    .stats-card i {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .stats-card h3 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0.5rem 0;
    }

    .stats-card p {
        color: var(--text-light);
        margin: 0;
        font-size: 0.9rem;
    }

    .section-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 2rem;
    }

    .section-card h2 {
        color: var(--text-dark);
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-card h2 i {
        color: var(--primary);
    }

    .specialization-tag {
        display: inline-block;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #166534;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0.25rem;
    }

    .availability-day {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid #e2e8f0;
        transition: all 0.3s;
    }

    .availability-day:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }

    .day-name {
        font-weight: 600;
        color: var(--text-dark);
    }

    .time-range {
        color: var(--primary);
        font-weight: 600;
    }

    .review-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 2px solid #e2e8f0;
    }

    .review-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .reviewer-photo {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .rating-stars {
        color: #fbbf24;
        font-size: 1.1rem;
    }

    .btn-book-now {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 1.25rem 3rem;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        width: 100%;
        margin-top: 1rem;
    }

    .btn-book-now:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
    }

    .price-tag {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        padding: 1rem 2rem;
        border-radius: 16px;
        text-align: center;
        border: 2px solid #fcd34d;
        margin-bottom: 1rem;
    }

    .price-tag h3 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0;
    }

    .price-tag p {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .badge-on-call {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 2px solid #fcd34d;
    }

    .badge-on-call i {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    @media (max-width: 768px) {
        .profile-card {
            padding: 1.5rem;
            margin-top: -3rem;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
        }
    }
</style>

<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <img src="<?= clean($dietitian['profile_photo'] ?? '/images/default-avatar.png') ?>"
                     alt="<?= clean($dietitian['full_name']) ?>"
                     class="profile-photo">
                <h1 class="mt-3 mb-2"><?= clean($dietitian['full_name']) ?></h1>
                <p class="lead mb-0"><?= clean($dietitian['title']) ?></p>
                <?php if ($dietitian['is_on_call']): ?>
                    <div class="mt-3">
                        <span class="badge-on-call">
                            <i class="fas fa-phone-volume"></i>
                            Acil Nöbetçi Diyetisyen
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <!-- Left Column: Profile Details -->
        <div class="col-lg-8">
            <div class="profile-card">
                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stats-card">
                            <i class="fas fa-star"></i>
                            <h3><?= number_format($dietitian['rating_avg'], 1) ?></h3>
                            <p><?= $dietitian['rating_count'] ?> Değerlendirme</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card">
                            <i class="fas fa-users"></i>
                            <h3><?= number_format($dietitian['total_clients']) ?></h3>
                            <p>Mutlu Danışan</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card">
                            <i class="fas fa-briefcase"></i>
                            <h3><?= $dietitian['experience_years'] ?></h3>
                            <p>Yıl Deneyim</p>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <div class="section-card">
                    <h2><i class="fas fa-user-circle"></i> Hakkımda</h2>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        <?= nl2br(clean($dietitian['about_me'])) ?>
                    </p>
                </div>

                <!-- Specializations -->
                <?php if (!empty($dietitian['specialization'])): ?>
                <div class="section-card">
                    <h2><i class="fas fa-heartbeat"></i> Uzmanlık Alanları</h2>
                    <div>
                        <?php
                        $specializations = explode(',', $dietitian['specialization']);
                        foreach ($specializations as $spec):
                        ?>
                            <span class="specialization-tag"><?= clean(trim($spec)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Education -->
                <?php if (!empty($dietitian['education'])): ?>
                <div class="section-card">
                    <h2><i class="fas fa-graduation-cap"></i> Eğitim</h2>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        <?= nl2br(clean($dietitian['education'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Certificates -->
                <?php if (!empty($dietitian['certificates'])): ?>
                <div class="section-card">
                    <h2><i class="fas fa-certificate"></i> Sertifikalar</h2>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        <?= nl2br(clean($dietitian['certificates'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Availability -->
                <?php if (!empty($availability)): ?>
                <div class="section-card">
                    <h2><i class="fas fa-calendar-alt"></i> Müsaitlik Durumu</h2>
                    <?php
                    $dayNames = [
                        0 => 'Pazar',
                        1 => 'Pazartesi',
                        2 => 'Salı',
                        3 => 'Çarşamba',
                        4 => 'Perşembe',
                        5 => 'Cuma',
                        6 => 'Cumartesi'
                    ];

                    foreach ($availability as $slot):
                    ?>
                        <div class="availability-day">
                            <span class="day-name"><?= $dayNames[$slot['day_of_week']] ?></span>
                            <span class="time-range">
                                <?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Reviews -->
                <?php if (!empty($reviews)): ?>
                <div class="section-card">
                    <h2><i class="fas fa-comments"></i> Danışan Yorumları</h2>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <img src="<?= clean($review['client_photo'] ?? '/images/default-avatar.png') ?>"
                                     alt="<?= clean($review['client_name']) ?>"
                                     class="reviewer-photo">
                                <div>
                                    <h5 class="mb-1" style="color: var(--text-dark);">
                                        <?= clean($review['client_name']) ?>
                                    </h5>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <p style="color: var(--text-light); margin: 0;">
                                <?= clean($review['comment']) ?>
                            </p>
                            <small class="text-muted">
                                <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Booking -->
        <div class="col-lg-4">
            <div class="section-card" id="book" style="position: sticky; top: 90px;">
                <h2><i class="fas fa-calendar-check"></i> Randevu Al</h2>

                <div class="price-tag">
                    <h3><?= number_format($dietitian['consultation_fee'], 0) ?> ₺</h3>
                    <p>Seans Ücreti</p>
                </div>

                <?php if ($auth->check() && $auth->user()['user_type'] === 'client'): ?>
                    <form action="/api/create-appointment.php" method="POST">
                        <input type="hidden" name="dietitian_id" value="<?= $dietitian['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label" style="color: var(--text-dark); font-weight: 600;">
                                <i class="fas fa-calendar me-2"></i>Tarih Seçin
                            </label>
                            <input type="date" class="form-control" name="appointment_date"
                                   min="<?= date('Y-m-d') ?>" required
                                   style="border-radius: 12px; border: 2px solid var(--border-color); padding: 0.75rem;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="color: var(--text-dark); font-weight: 600;">
                                <i class="fas fa-clock me-2"></i>Saat Seçin
                            </label>
                            <select class="form-select" name="start_time" required
                                    style="border-radius: 12px; border: 2px solid var(--border-color); padding: 0.75rem;">
                                <option value="">Saat seçiniz...</option>
                                <?php for ($h = 9; $h < 18; $h++): ?>
                                    <option value="<?= sprintf('%02d:00:00', $h) ?>">
                                        <?= sprintf('%02d:00', $h) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="color: var(--text-dark); font-weight: 600;">
                                <i class="fas fa-comment-medical me-2"></i>Notunuz (Opsiyonel)
                            </label>
                            <textarea class="form-control" name="notes" rows="3"
                                      placeholder="Varsa özel notlarınızı yazabilirsiniz..."
                                      style="border-radius: 12px; border: 2px solid var(--border-color); padding: 0.75rem;"></textarea>
                        </div>

                        <button type="submit" class="btn-book-now">
                            <i class="fas fa-check-circle me-2"></i>
                            Randevu Oluştur
                        </button>
                    </form>
                <?php elseif ($auth->check() && $auth->user()['user_type'] === 'dietitian'): ?>
                    <div class="alert alert-info" style="border-radius: 12px;">
                        <i class="fas fa-info-circle me-2"></i>
                        Diyetisyen hesabıyla randevu alamazsınız.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" style="border-radius: 12px; margin-bottom: 1rem;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Randevu almak için giriş yapmalısınız.
                    </div>
                    <a href="/login.php?redirect=<?= urlencode('/dietitian-profile.php?id=' . $dietitian['id']) ?>"
                       class="btn-book-now" style="text-decoration: none; display: block; text-align: center;">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Giriş Yap
                    </a>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Hesabınız yok mu? <a href="/register-client.php" style="color: var(--primary); font-weight: 600;">Kayıt Olun</a>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
