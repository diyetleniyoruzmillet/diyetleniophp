<?php
/**
 * Dietitian Dashboard - Randevular ve Video Görüşme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    header('Location: /login.php');
    exit;
}

$user = $auth->user();
$conn = $db->getConnection();

// Upcoming appointments
$stmt = $conn->prepare("
    SELECT a.*,
           u.full_name as client_name,
           dp.title as dietitian_title,
           dp.specialization,
           dp.rating_avg,
           u.profile_photo
    FROM appointments a
    LEFT JOIN users u ON a.client_id = u.id
    LEFT JOIN dietitian_profiles dp ON a.client_id = dp.user_id
    WHERE a.dietitian_id = ? AND a.status = 'scheduled'
    AND CONCAT(a.appointment_date, ' ', a.start_time) >= NOW()
    ORDER BY a.appointment_date ASC, a.start_time ASC
    LIMIT 10
");
$stmt->execute([$auth->id()]);
$upcomingAppointments = $stmt->fetchAll();

// Past appointments
$stmt = $conn->prepare("
    SELECT a.*,
           u.full_name as client_name,
           dp.title as dietitian_title
    FROM appointments a
    LEFT JOIN users u ON a.client_id = u.id
    LEFT JOIN dietitian_profiles dp ON a.client_id = dp.user_id
    WHERE a.dietitian_id = ? AND (a.status = 'completed' OR
          (a.status = 'scheduled' AND CONCAT(a.appointment_date, ' ', a.start_time) < NOW()))
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 5
");
$stmt->execute([$auth->id()]);
$pastAppointments = $stmt->fetchAll();

$pageTitle = 'Panelim';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    body {
        background: #f8fafc;
    }

    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .welcome-section {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 24px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 40px rgba(86, 171, 47, 0.3);
    }

    .welcome-section h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .welcome-section p {
        font-size: 1.1rem;
        opacity: 0.95;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        padding: 2rem;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        border-color: #10b981;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        margin-bottom: 1rem;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        color: #64748b;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .section-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-title i {
        color: #10b981;
    }

    .appointment-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all 0.3s;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .appointment-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .appointment-card:hover {
        transform: translateX(5px);
        border-color: #10b981;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .appointment-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .dietitian-info {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .dietitian-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #10b981;
    }

    .dietitian-name {
        font-size: 1.3rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .dietitian-title {
        color: #64748b;
        font-size: 0.95rem;
    }

    .appointment-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-upcoming {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .badge-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        animation: pulse-badge 2s ease-in-out infinite;
    }

    @keyframes pulse-badge {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    }

    .appointment-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 12px;
    }

    .detail-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
    }

    .detail-content strong {
        display: block;
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .detail-content span {
        font-size: 1rem;
        font-weight: 600;
        color: #0f172a;
    }

    .appointment-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .btn-video {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .btn-video:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        color: white;
    }

    .btn-video i {
        font-size: 1.1rem;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #e2e8f0;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-cancel:hover {
        background: #fee2e2;
        border-color: #fecaca;
        color: #ef4444;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.75rem;
    }

    .empty-state p {
        color: #64748b;
        margin-bottom: 2rem;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        color: white;
    }

    @media (max-width: 768px) {
        .welcome-section h1 {
            font-size: 1.8rem;
        }

        .appointment-header {
            flex-direction: column;
            gap: 1rem;
        }

        .appointment-actions {
            flex-direction: column;
        }

        .btn-video, .btn-cancel {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1>Hoş Geldiniz, <?= clean($user->getFullName()) ?>!</h1>
        <p>Randevularınızı ve video görüşmelerinizi buradan yönetebilirsiniz.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-value"><?= count($upcomingAppointments) ?></div>
            <div class="stat-label">Yaklaşan Randevu</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?= count($pastAppointments) ?></div>
            <div class="stat-label">Tamamlanan Seans</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <div class="stat-value">98%</div>
            <div class="stat-label">Memnuniyet Oranı</div>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <h2 class="section-title">
        <i class="fas fa-calendar-alt"></i>
        Yaklaşan Randevular
    </h2>

    <?php if (count($upcomingAppointments) > 0): ?>
        <?php foreach ($upcomingAppointments as $appointment):
            $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
            $now = time();
            $thirtyMinsBefore = $appointmentDateTime - (30 * 60);
            $canJoin = $now >= $thirtyMinsBefore;
            $isActive = $now >= $thirtyMinsBefore && $now <= ($appointmentDateTime + 60 * 60);

            $photoUrl = $appointment['profile_photo'] ?
                ('/assets/uploads/' . ltrim($appointment['profile_photo'], '/')) :
                '/images/default-avatar.png';
        ?>
        <div class="appointment-card">
            <div class="appointment-header">
                <div class="dietitian-info">
                    <img src="<?= clean($photoUrl) ?>" alt="<?= clean($appointment['client_name']) ?>" class="dietitian-avatar">
                    <div>
                        <div class="dietitian-name"><?= clean($appointment['client_name']) ?></div>
                        <div class="dietitian-title"><?= clean($appointment['dietitian_title'] ?? 'Diyetisyen') ?></div>
                    </div>
                </div>
                <span class="appointment-badge <?= $isActive ? 'badge-active' : 'badge-upcoming' ?>">
                    <i class="fas fa-circle"></i>
                    <?= $isActive ? 'Şimdi Katılabilirsiniz' : 'Yaklaşan' ?>
                </span>
            </div>

            <div class="appointment-details">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Tarih</strong>
                        <span><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Saat</strong>
                        <span><?= date('H:i', strtotime($appointment['start_time'])) ?></span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="detail-content">
                        <strong>Süre</strong>
                        <span><?= $appointment['duration'] ?? 45 ?> dakika</span>
                    </div>
                </div>
            </div>

            <div class="appointment-actions">
                <?php if ($canJoin): ?>
                    <a href="/video-room.php?appointment_id=<?= $appointment['id'] ?>" class="btn-video">
                        <i class="fas fa-video"></i>
                        <span>Görüşmeye Katıl</span>
                    </a>
                <?php else:
                    $minutesUntil = ceil(($thirtyMinsBefore - $now) / 60);
                ?>
                    <button class="btn-video" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <i class="fas fa-clock"></i>
                        <span><?= $minutesUntil ?> dakika sonra katılabilirsiniz</span>
                    </button>
                <?php endif; ?>
                <a href="#" class="btn-cancel" onclick="return confirm('Randevuyu iptal etmek istediğinizden emin misiniz?');">
                    <i class="fas fa-times"></i>
                    <span>İptal Et</span>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Yaklaşan Randevunuz Yok</h3>
            <p>Müsaitlik takviminizi ayarlayın ve sağlıklı yaşam yolculuğunuza başlayın!</p>
            <a href="/dietitian/availability.php" class="btn-primary-custom">
                <i class="fas fa-search"></i>
                <span>Müsaitlik Ayarla</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
