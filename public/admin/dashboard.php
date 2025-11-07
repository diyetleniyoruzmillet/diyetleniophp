<?php
/**
 * Admin Dashboard
 * Yönetici paneli ana sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()['user_type'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();

// İstatistikleri çek
$stats = [];

// Kullanıcı istatistikleri
$stmt = $conn->query("
    SELECT
        COUNT(*) as total_users,
        SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as total_clients,
        SUM(CASE WHEN user_type = 'dietitian' THEN 1 ELSE 0 END) as total_dietitians,
        SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as total_admins,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_registrations
    FROM users
");
$stats['users'] = $stmt->fetch();

// Diyetisyen istatistikleri
$stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending
    FROM dietitian_profiles
");
$stats['dietitians'] = $stmt->fetch();

// Randevu istatistikleri
$stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM appointments
");
$stats['appointments'] = $stmt->fetch();

// Acil talep istatistikleri
$stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN urgency_level = 'critical' THEN 1 ELSE 0 END) as critical
    FROM emergency_consultations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stats['emergency'] = $stmt->fetch();

// Son kayıtlar
$stmt = $conn->query("
    SELECT full_name, email, user_type, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_users = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 100px auto 50px;
        padding: 0 2rem;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border-left: 6px solid;
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .stat-card.primary {
        border-left-color: #3b82f6;
    }

    .stat-card.success {
        border-left-color: #10b981;
    }

    .stat-card.warning {
        border-left-color: #f59e0b;
    }

    .stat-card.danger {
        border-left-color: #ef4444;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stat-card.primary .stat-icon {
        background: #dbeafe;
        color: #3b82f6;
    }

    .stat-card.success .stat-icon {
        background: #d1fae5;
        color: #10b981;
    }

    .stat-card.warning .stat-icon {
        background: #fef3c7;
        color: #f59e0b;
    }

    .stat-card.danger .stat-icon {
        background: #fee2e2;
        color: #ef4444;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 600;
    }

    .section-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .quick-link {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 16px;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .quick-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
        color: white;
    }

    .quick-link i {
        font-size: 2rem;
    }

    .quick-link-text {
        flex: 1;
    }

    .quick-link-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .quick-link-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .recent-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .recent-item {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .recent-item:last-child {
        border-bottom: none;
    }

    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-client {
        background: #dbeafe;
        color: #2563eb;
    }

    .badge-dietitian {
        background: #d1fae5;
        color: #059669;
    }

    .badge-admin {
        background: #fee2e2;
        color: #dc2626;
    }
</style>

<div class="dashboard-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-gauge me-3"></i>
            Admin Dashboard
        </h1>
        <p style="color: #64748b;">Hoş geldiniz, <?= clean($auth->user()['full_name']) ?></p>
    </div>

    <!-- İstatistikler -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?= $stats['users']['total_users'] ?></div>
            <div class="stat-label">Toplam Kullanıcı</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-value"><?= $stats['users']['total_clients'] ?></div>
            <div class="stat-label">Danışanlar</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="stat-value"><?= $stats['users']['total_dietitians'] ?></div>
            <div class="stat-label">Diyetisyenler</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-value"><?= $stats['appointments']['today'] ?></div>
            <div class="stat-label">Bugünkü Randevular</div>
        </div>
    </div>

    <!-- Hızlı Linkler -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-bolt"></i>
            Hızlı Erişim
        </h2>
        <div class="quick-links">
            <a href="/admin/emergency-requests.php" class="quick-link" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i class="fas fa-ambulance"></i>
                <div class="quick-link-text">
                    <div class="quick-link-label">Acil Talepler</div>
                    <div class="quick-link-value"><?= $stats['emergency']['pending'] ?></div>
                </div>
            </a>

            <a href="/admin/users.php" class="quick-link" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <i class="fas fa-users"></i>
                <div class="quick-link-text">
                    <div class="quick-link-label">Kullanıcılar</div>
                    <div class="quick-link-value"><?= $stats['users']['active_users'] ?></div>
                </div>
            </a>

            <a href="/admin/dietitians.php" class="quick-link" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fas fa-user-check"></i>
                <div class="quick-link-text">
                    <div class="quick-link-label">Onay Bekleyen</div>
                    <div class="quick-link-value"><?= $stats['dietitians']['pending'] ?></div>
                </div>
            </a>

            <a href="/admin/appointments.php" class="quick-link" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fas fa-calendar"></i>
                <div class="quick-link-text">
                    <div class="quick-link-label">Randevular</div>
                    <div class="quick-link-value"><?= $stats['appointments']['pending'] ?></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Son Kayıtlar -->
        <div class="col-lg-8">
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i>
                    Son Kayıtlar
                </h2>
                <ul class="recent-list">
                    <?php foreach ($recent_users as $user): ?>
                        <li class="recent-item">
                            <div>
                                <strong><?= clean($user['full_name']) ?></strong>
                                <br>
                                <small style="color: #64748b;"><?= clean($user['email']) ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge badge-<?= $user['user_type'] ?>">
                                    <?= $user['user_type'] === 'client' ? 'Danışan' : ($user['user_type'] === 'dietitian' ? 'Diyetisyen' : 'Admin') ?>
                                </span>
                                <br>
                                <small style="color: #64748b;"><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Sistem Durumu -->
        <div class="col-lg-4">
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    Özet
                </h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 12px;">
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">Bugün Kayıt</div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['users']['today_registrations'] ?></div>
                    </div>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 12px;">
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">Onaylı Diyetisyen</div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['dietitians']['approved'] ?></div>
                    </div>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 12px;">
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">Tamamlanan Randevu</div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['appointments']['completed'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
