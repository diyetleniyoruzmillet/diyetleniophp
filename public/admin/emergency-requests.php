<?php
/**
 * Admin - Acil Diyetisyen Talepleri
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$errors = [];
$success = false;

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        try {
            $requestId = (int) ($_POST['request_id'] ?? 0);

            if ($_POST['action'] === 'update_status') {
                $newStatus = $_POST['status'] ?? '';
                $adminNotes = $_POST['admin_notes'] ?? '';

                $stmt = $conn->prepare("
                    UPDATE emergency_consultations
                    SET status = ?, admin_notes = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$newStatus, $adminNotes, $requestId]);

                setFlash('success', 'Talep durumu güncellendi.');
            } elseif ($_POST['action'] === 'respond') {
                $response = $_POST['response_message'] ?? '';

                $stmt = $conn->prepare("
                    UPDATE emergency_consultations
                    SET status = 'responded',
                        response_message = ?,
                        responded_at = NOW(),
                        responded_by = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$response, $auth->id(), $requestId]);

                // Kullanıcıya e-posta gönder
                $stmt = $conn->prepare("SELECT * FROM emergency_consultations WHERE id = ?");
                $stmt->execute([$requestId]);
                $request = $stmt->fetch();

                if ($request) {
                    try {
                        $mailer = new Mailer();
                        $emailBody = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <style>
                                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center; color: white; }
                                .content { padding: 30px; }
                                .response-box { background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981; }
                                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>✅ Talebinize Yanıt Verildi</h1>
                                </div>
                                <div class='content'>
                                    <p>Merhaba <strong>" . htmlspecialchars($request['full_name']) . "</strong>,</p>
                                    <p>Acil diyetisyen talebiniz (#" . $requestId . ") yanıtlandı.</p>
                                    <div class='response-box'>
                                        <h3 style='margin-top: 0;'>💬 Yanıt:</h3>
                                        <p>" . nl2br(htmlspecialchars($response)) . "</p>
                                    </div>
                                    <p>Ek sorularınız için bizimle iletişime geçebilirsiniz.</p>
                                </div>
                                <div class='footer'>
                                    © " . date('Y') . " Diyetlenio. Tüm hakları saklıdır.
                                </div>
                            </div>
                        </body>
                        </html>
                        ";

                        $mailer->send($request['email'], 'Acil Talebinize Yanıt - Diyetlenio', $emailBody);
                    } catch (Exception $e) {
                        error_log('Response email error: ' . $e->getMessage());
                    }
                }

                setFlash('success', 'Yanıt gönderildi ve kullanıcıya e-posta ile bildirildi.');
            }

            redirect('/admin/emergency-requests.php');

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Filtreleme
$statusFilter = $_GET['status'] ?? 'all';
$urgencyFilter = $_GET['urgency'] ?? 'all';

$query = "
    SELECT ec.*,
           u.full_name as user_full_name,
           r.full_name as responder_name
    FROM emergency_consultations ec
    LEFT JOIN users u ON u.id = ec.user_id
    LEFT JOIN users r ON r.id = ec.responded_by
    WHERE 1=1
";

if ($statusFilter !== 'all') {
    $query .= " AND ec.status = :status";
}
if ($urgencyFilter !== 'all') {
    $query .= " AND ec.urgency_level = :urgency";
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
if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter);
}
if ($urgencyFilter !== 'all') {
    $stmt->bindValue(':urgency', $urgencyFilter);
}
$stmt->execute();
$requests = $stmt->fetchAll();

// İstatistikler
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded,
        SUM(CASE WHEN urgency_level = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN urgency_level = 'high' THEN 1 ELSE 0 END) as high
    FROM emergency_consultations
")->fetch();

$pageTitle = 'Acil Diyetisyen Talepleri';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --danger: #dc2626;
            --warning: #f97316;
            --success: #10b981;
            --info: #3b82f6;
        }

        body {
            background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .admin-container {
            max-width: 1600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .page-header h1 {
            font-weight: 900;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--danger), var(--warning));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .stat-card.danger .stat-value { color: var(--danger); }
        .stat-card.warning .stat-value { color: var(--warning); }
        .stat-card.success .stat-value { color: var(--success); }
        .stat-card.info .stat-value { color: var(--info); }

        .filters-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .request-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 6px solid #e5e7eb;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .request-card.urgency-critical { border-left-color: #dc2626; }
        .request-card.urgency-high { border-left-color: #f97316; }
        .request-card.urgency-medium { border-left-color: #f59e0b; }
        .request-card.urgency-low { border-left-color: #10b981; }

        .urgency-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .urgency-critical { background: #fee2e2; color: #991b1b; }
        .urgency-high { background: #ffedd5; color: #9a3412; }
        .urgency-medium { background: #fef3c7; color: #92400e; }
        .urgency-low { background: #d1fae5; color: #065f46; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .status-pending { background: #dbeafe; color: #1e40af; }
        .status-in_progress { background: #fef3c7; color: #92400e; }
        .status-responded { background: #d1fae5; color: #065f46; }
        .status-closed { background: #f3f4f6; color: #6b7280; }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }

        .request-info {
            flex: 1;
        }

        .request-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .request-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .request-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .request-message {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }

        .detail-item {
            font-size: 0.9rem;
        }

        .detail-label {
            color: #6b7280;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 700;
        }

        .action-btn {
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 5px;
        }

        .btn-respond {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-respond:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #dc2626, #f97316);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="/admin/dashboard.php" class="btn-back mb-3">
            <i class="fas fa-arrow-left"></i> Dashboard'a Dön
        </a>

        <div class="page-header">
            <h1><i class="fas fa-heartbeat me-3"></i><?= clean($pageTitle) ?></h1>
            <p class="text-muted mb-0" style="font-size: 1.1rem;">Acil diyetisyen danışma taleplerini yönetin</p>
        </div>

        <!-- İstatistikler -->
        <div class="stats-grid">
            <div class="stat-card danger">
                <div class="stat-value"><?= $stats['critical'] ?? 0 ?></div>
                <div class="stat-label"><i class="fas fa-exclamation-triangle me-1"></i>Kritik</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['high'] ?? 0 ?></div>
                <div class="stat-label"><i class="fas fa-exclamation-circle me-1"></i>Yüksek</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value"><?= $stats['pending'] ?? 0 ?></div>
                <div class="stat-label"><i class="fas fa-clock me-1"></i>Bekleyen</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['in_progress'] ?? 0 ?></div>
                <div class="stat-label"><i class="fas fa-spinner me-1"></i>İşlemde</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?= $stats['responded'] ?? 0 ?></div>
                <div class="stat-label"><i class="fas fa-check-circle me-1"></i>Yanıtlandı</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #667eea;"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-label"><i class="fas fa-list me-1"></i>Toplam</div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="filters-card">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Durum</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tümü</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                        <option value="responded" <?= $statusFilter === 'responded' ? 'selected' : '' ?>>Yanıtlandı</option>
                        <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Kapatıldı</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Aciliyet</label>
                    <select name="urgency" class="form-select">
                        <option value="all" <?= $urgencyFilter === 'all' ? 'selected' : '' ?>>Tümü</option>
                        <option value="critical" <?= $urgencyFilter === 'critical' ? 'selected' : '' ?>>Kritik</option>
                        <option value="high" <?= $urgencyFilter === 'high' ? 'selected' : '' ?>>Yüksek</option>
                        <option value="medium" <?= $urgencyFilter === 'medium' ? 'selected' : '' ?>>Orta</option>
                        <option value="low" <?= $urgencyFilter === 'low' ? 'selected' : '' ?>>Düşük</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filtrele
                    </button>
                </div>
            </form>
        </div>

        <!-- Talepler -->
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Henüz talep bulunmuyor</h3>
                <p class="text-muted">Yeni acil talepler burada görünecektir</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card urgency-<?= $request['urgency_level'] ?>">
                    <div class="request-header">
                        <div class="request-info">
                            <div class="request-title">
                                #<?= $request['id'] ?> - <?= clean($request['full_name']) ?>
                            </div>
                            <div class="request-meta">
                                <div class="request-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d.m.Y H:i', strtotime($request['created_at'])) ?>
                                </div>
                                <div class="request-meta-item">
                                    <i class="fas fa-envelope"></i>
                                    <?= clean($request['email']) ?>
                                </div>
                                <?php if ($request['phone']): ?>
                                    <div class="request-meta-item">
                                        <i class="fas fa-phone"></i>
                                        <?= clean($request['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <span class="urgency-badge urgency-<?= $request['urgency_level'] ?>">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php
                                    $urgencyLabels = ['low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek', 'critical' => 'Kritik'];
                                    echo $urgencyLabels[$request['urgency_level']];
                                    ?>
                                </span>
                                <span class="status-badge status-<?= $request['status'] ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php
                                    $statusLabels = ['pending' => 'Bekleyen', 'in_progress' => 'İşlemde', 'responded' => 'Yanıtlandı', 'closed' => 'Kapatıldı'];
                                    echo $statusLabels[$request['status']];
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <button class="action-btn btn-respond" data-bs-toggle="modal" data-bs-target="#detailModal<?= $request['id'] ?>">
                                <i class="fas fa-eye me-2"></i>Detay
                            </button>
                        </div>
                    </div>

                    <div class="request-message">
                        <strong><i class="fas fa-comment me-2"></i>Mesaj:</strong><br>
                        <?= nl2br(clean(substr($request['message'], 0, 200))) ?>
                        <?= strlen($request['message']) > 200 ? '...' : '' ?>
                    </div>

                    <div class="request-details">
                        <?php if ($request['age']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Yaş</span>
                                <span class="detail-value"><?= $request['age'] ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($request['weight']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Kilo</span>
                                <span class="detail-value"><?= $request['weight'] ?> kg</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($request['height']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Boy</span>
                                <span class="detail-value"><?= $request['height'] ?> cm</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($request['responded_at']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Yanıt Tarihi</span>
                                <span class="detail-value"><?= date('d.m.Y H:i', strtotime($request['responded_at'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Detail Modal -->
                <div class="modal fade" id="detailModal<?= $request['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content" style="border-radius: 20px; border: none;">
                            <div class="modal-header modal-header-custom">
                                <h5 class="modal-title">
                                    <i class="fas fa-heartbeat me-2"></i>
                                    Talep Detayı #<?= $request['id'] ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Tüm bilgiler -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-user me-2"></i>Kişi Bilgileri</h6>
                                        <table class="table table-sm">
                                            <tr><th width="150">Ad Soyad:</th><td><?= clean($request['full_name']) ?></td></tr>
                                            <tr><th>E-posta:</th><td><?= clean($request['email']) ?></td></tr>
                                            <tr><th>Telefon:</th><td><?= clean($request['phone'] ?: '-') ?></td></tr>
                                            <tr><th>Yaş:</th><td><?= $request['age'] ?: '-' ?></td></tr>
                                            <tr><th>Cinsiyet:</th><td><?= $request['gender'] ? ['male' => 'Erkek', 'female' => 'Kadın', 'other' => 'Diğer'][$request['gender']] : '-' ?></td></tr>
                                            <tr><th>Boy:</th><td><?= $request['height'] ? $request['height'] . ' cm' : '-' ?></td></tr>
                                            <tr><th>Kilo:</th><td><?= $request['weight'] ? $request['weight'] . ' kg' : '-' ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>Talep Bilgileri</h6>
                                        <table class="table table-sm">
                                            <tr><th width="150">Talep Tarihi:</th><td><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></td></tr>
                                            <tr><th>Aciliyet:</th><td><span class="urgency-badge urgency-<?= $request['urgency_level'] ?>"><?= $urgencyLabels[$request['urgency_level']] ?></span></td></tr>
                                            <tr><th>Durum:</th><td><span class="status-badge status-<?= $request['status'] ?>"><?= $statusLabels[$request['status']] ?></span></td></tr>
                                            <?php if ($request['responded_at']): ?>
                                            <tr><th>Yanıt Tarihi:</th><td><?= date('d.m.Y H:i', strtotime($request['responded_at'])) ?></td></tr>
                                            <tr><th>Yanıtlayan:</th><td><?= clean($request['responder_name'] ?: '-') ?></td></tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                </div>

                                <?php if ($request['health_conditions']): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold"><i class="fas fa-notes-medical me-2"></i>Sağlık Durumu</h6>
                                        <div class="p-3 bg-light rounded"><?= nl2br(clean($request['health_conditions'])) ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($request['medications']): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold"><i class="fas fa-pills me-2"></i>İlaçlar</h6>
                                        <div class="p-3 bg-light rounded"><?= nl2br(clean($request['medications'])) ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-4">
                                    <h6 class="fw-bold"><i class="fas fa-comment me-2"></i>Talep Mesajı</h6>
                                    <div class="p-3 bg-warning bg-opacity-10 rounded border-start border-warning border-4">
                                        <?= nl2br(clean($request['message'])) ?>
                                    </div>
                                </div>

                                <?php if ($request['response_message']): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold"><i class="fas fa-reply me-2"></i>Verilen Yanıt</h6>
                                        <div class="p-3 bg-success bg-opacity-10 rounded border-start border-success border-4">
                                            <?= nl2br(clean($request['response_message'])) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Yanıt Formu -->
                                <?php if ($request['status'] !== 'responded' && $request['status'] !== 'closed'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="respond">
                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Yanıtınız</label>
                                            <textarea name="response_message" class="form-control" rows="5" required></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Yanıt Gönder ve Kullanıcıya Bildir
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Durum Değiştir</label>
                                                <select name="status" class="form-select">
                                                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                                                    <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                                                    <option value="responded" <?= $request['status'] === 'responded' ? 'selected' : '' ?>>Yanıtlandı</option>
                                                    <option value="closed" <?= $request['status'] === 'closed' ? 'selected' : '' ?>>Kapatıldı</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Admin Notları</label>
                                                <textarea name="admin_notes" class="form-control" rows="2"><?= clean($request['admin_notes'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i>Güncelle
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
