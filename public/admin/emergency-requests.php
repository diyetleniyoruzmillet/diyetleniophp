<?php
/**
 * Admin - Emergency Consultation Requests
 * Acil destek taleplerini yönet
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin kontrolü
if (!$auth->check() || $auth->user()['user_type'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$conn = $db->getConnection();

// Filtrele
$status_filter = $_GET['status'] ?? 'all';
$urgency_filter = $_GET['urgency'] ?? 'all';

// Talepleri çek
$query = "
    SELECT ec.*,
           u.full_name as user_full_name,
           u.email as user_email,
           resp.full_name as responder_name
    FROM emergency_consultations ec
    LEFT JOIN users u ON ec.user_id = u.id
    LEFT JOIN users resp ON ec.responded_by = resp.id
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND ec.status = :status";
    $params['status'] = $status_filter;
}

if ($urgency_filter !== 'all') {
    $query .= " AND ec.urgency_level = :urgency";
    $params['urgency'] = $urgency_filter;
}

$query .= " ORDER BY
    CASE ec.urgency_level
        WHEN 'critical' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
    END,
    ec.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// İstatistikler
$stats_stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded,
        SUM(CASE WHEN urgency_level = 'critical' THEN 1 ELSE 0 END) as critical
    FROM emergency_consultations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stats = $stats_stmt->fetch();

$pageTitle = 'Acil Destek Talepleri';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    :root {
        --danger: #ef4444;
        --warning: #f59e0b;
        --success: #10b981;
        --info: #3b82f6;
    }

    .admin-container {
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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 2px solid #f1f5f9;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
    }

    .filters {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .filters select {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        margin-right: 1rem;
    }

    .request-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border-left: 6px solid #e2e8f0;
        transition: all 0.3s;
    }

    .request-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .request-card.critical {
        border-left-color: var(--danger);
        background: #fef2f2;
    }

    .request-card.high {
        border-left-color: #f97316;
    }

    .request-card.medium {
        border-left-color: var(--warning);
    }

    .request-card.low {
        border-left-color: var(--info);
    }

    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .request-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }

    .badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-critical {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-high {
        background: #fed7aa;
        color: #ea580c;
    }

    .badge-medium {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-low {
        background: #dbeafe;
        color: #2563eb;
    }

    .badge-pending {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-in-progress {
        background: #dbeafe;
        color: #2563eb;
    }

    .badge-responded {
        background: #d1fae5;
        color: #059669;
    }

    .request-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 12px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.875rem;
        color: #0f172a;
        font-weight: 600;
    }

    .request-message {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        margin: 1rem 0;
        line-height: 1.7;
    }

    .request-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: #10b981;
        color: white;
    }

    .btn-primary:hover {
        background: #059669;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #64748b;
        color: white;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
</style>

<div class="admin-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-ambulance me-3"></i>
            Acil Destek Talepleri
        </h1>
        <p style="color: #64748b;">Kullanıcılardan gelen acil destek taleplerini yönetin</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Toplam Talep (30 Gün)</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Bekleyen</div>
            <div class="stat-value" style="color: #f59e0b;"><?= $stats['pending'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">İşlemde</div>
            <div class="stat-value" style="color: #3b82f6;"><?= $stats['in_progress'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Kritik Talepler</div>
            <div class="stat-value" style="color: #ef4444;"><?= $stats['critical'] ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <form method="GET" class="d-flex align-items-center flex-wrap gap-3">
            <div>
                <label class="me-2 fw-bold">Durum:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tümü</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                    <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                    <option value="responded" <?= $status_filter === 'responded' ? 'selected' : '' ?>>Yanıtlandı</option>
                    <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Kapatıldı</option>
                </select>
            </div>
            <div>
                <label class="me-2 fw-bold">Aciliyet:</label>
                <select name="urgency" onchange="this.form.submit()">
                    <option value="all" <?= $urgency_filter === 'all' ? 'selected' : '' ?>>Tümü</option>
                    <option value="critical" <?= $urgency_filter === 'critical' ? 'selected' : '' ?>>Kritik</option>
                    <option value="high" <?= $urgency_filter === 'high' ? 'selected' : '' ?>>Yüksek</option>
                    <option value="medium" <?= $urgency_filter === 'medium' ? 'selected' : '' ?>>Orta</option>
                    <option value="low" <?= $urgency_filter === 'low' ? 'selected' : '' ?>>Düşük</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Requests -->
    <?php if (!empty($requests)): ?>
        <?php foreach ($requests as $request): ?>
            <div class="request-card <?= $request['urgency_level'] ?>">
                <div class="request-header">
                    <div>
                        <div class="request-title">
                            <?= clean($request['full_name']) ?>
                            <?php if ($request['user_id']): ?>
                                <small style="color: #64748b; font-weight: normal;">(Kayıtlı Kullanıcı)</small>
                            <?php endif; ?>
                        </div>
                        <small style="color: #64748b;">
                            <i class="far fa-clock me-1"></i>
                            <?= date('d.m.Y H:i', strtotime($request['created_at'])) ?>
                        </small>
                    </div>
                    <div class="badges">
                        <span class="badge badge-<?= $request['urgency_level'] ?>">
                            <?= strtoupper($request['urgency_level']) ?>
                        </span>
                        <span class="badge badge-<?= $request['status'] ?>">
                            <?= strtoupper(str_replace('_', ' ', $request['status'])) ?>
                        </span>
                    </div>
                </div>

                <div class="request-info">
                    <div class="info-item">
                        <span class="info-label">E-posta</span>
                        <span class="info-value"><?= clean($request['email']) ?></span>
                    </div>
                    <?php if ($request['phone']): ?>
                    <div class="info-item">
                        <span class="info-label">Telefon</span>
                        <span class="info-value"><?= clean($request['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['age']): ?>
                    <div class="info-item">
                        <span class="info-label">Yaş</span>
                        <span class="info-value"><?= $request['age'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['weight'] && $request['height']): ?>
                    <div class="info-item">
                        <span class="info-label">Boy/Kilo</span>
                        <span class="info-value"><?= $request['height'] ?> cm / <?= $request['weight'] ?> kg</span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($request['health_conditions']): ?>
                <div class="mt-2">
                    <strong style="color: #64748b; font-size: 0.875rem;">Sağlık Durumu:</strong>
                    <p style="margin: 0.5rem 0; color: #0f172a;"><?= nl2br(clean($request['health_conditions'])) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($request['medications']): ?>
                <div class="mt-2">
                    <strong style="color: #64748b; font-size: 0.875rem;">İlaçlar:</strong>
                    <p style="margin: 0.5rem 0; color: #0f172a;"><?= nl2br(clean($request['medications'])) ?></p>
                </div>
                <?php endif; ?>

                <div class="request-message">
                    <strong style="color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">Talep Mesajı:</strong>
                    <?= nl2br(clean($request['message'])) ?>
                </div>

                <?php if ($request['response_message']): ?>
                <div class="request-message" style="background: #f0fdf4; border-color: #10b981;">
                    <strong style="color: #059669; font-size: 0.875rem; display: block; margin-bottom: 0.5rem;">
                        <i class="fas fa-reply me-1"></i>
                        Yanıt (<?= clean($request['responder_name']) ?>):
                    </strong>
                    <?= nl2br(clean($request['response_message'])) ?>
                    <small style="color: #64748b; display: block; margin-top: 0.5rem;">
                        <?= date('d.m.Y H:i', strtotime($request['responded_at'])) ?>
                    </small>
                </div>
                <?php endif; ?>

                <div class="request-actions">
                    <a href="/admin/emergency-request-detail.php?id=<?= $request['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        Detay & Yanıtla
                    </a>
                    <?php if ($request['email']): ?>
                    <a href="mailto:<?= $request['email'] ?>" class="btn btn-secondary">
                        <i class="fas fa-envelope"></i>
                        E-posta Gönder
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3 style="color: #0f172a; margin-bottom: 1rem;">Talep Bulunamadı</h3>
            <p style="color: #64748b;">Seçilen filtrelere uygun acil destek talebi bulunmamaktadır.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
