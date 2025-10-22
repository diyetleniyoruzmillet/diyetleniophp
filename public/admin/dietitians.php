<?php
/**
 * Diyetlenio - Admin Diyetisyen Yönetimi ve Onay
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece admin erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Onaylama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
    } else {
        $dietitianId = (int)$_POST['dietitian_id'];
        $action = $_POST['action'];

        try {
            if ($action === 'approve') {
                $stmt = $conn->prepare("
                    UPDATE dietitian_profiles
                    SET is_approved = 1, approval_date = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$dietitianId]);

                // Kullanıcıyı aktif et
                $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->execute([$dietitianId]);

                setFlash('success', 'Diyetisyen başarıyla onaylandı.');

            } elseif ($action === 'reject') {
                $rejectionReason = trim($_POST['rejection_reason'] ?? '');

                $stmt = $conn->prepare("
                    UPDATE dietitian_profiles
                    SET is_approved = 0, rejection_reason = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$rejectionReason, $dietitianId]);

                setFlash('success', 'Diyetisyen başvurusu reddedildi.');
            }

        } catch (Exception $e) {
            error_log('Dietitian approval error: ' . $e->getMessage());
            setFlash('error', 'İşlem sırasında bir hata oluştu.');
        }

        redirect('/admin/dietitians.php');
    }
}

// Filtreleme
$filter = $_GET['filter'] ?? 'pending';
$search = trim($_GET['search'] ?? '');

// Diyetisyenleri çek
$whereClause = "";
$params = [];

if ($filter === 'pending') {
    $whereClause = "WHERE dp.is_approved = 0";
} elseif ($filter === 'approved') {
    $whereClause = "WHERE dp.is_approved = 1";
}

if (!empty($search)) {
    $whereClause .= ($whereClause ? " AND" : "WHERE") . " (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.is_active, u.created_at,
           dp.title, dp.specialization, dp.experience_years, dp.about_me,
           dp.education, dp.diploma_file, dp.consultation_fee, dp.is_approved,
           dp.approval_date, dp.rejection_reason, dp.rating_avg, dp.total_clients
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    {$whereClause}
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$dietitians = $stmt->fetchAll();

$pageTitle = 'Diyetisyen Yönetimi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include __DIR__ . '/../../includes/admin-styles.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h2 class="mb-3">Diyetisyen Yönetimi</h2>

                        <!-- Filters -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="btn-group w-100" role="group">
                                    <a href="?filter=pending"
                                       class="btn btn-outline-warning <?= $filter === 'pending' ? 'active' : '' ?>">
                                        <i class="fas fa-clock me-2"></i>Bekleyenler
                                    </a>
                                    <a href="?filter=approved"
                                       class="btn btn-outline-success <?= $filter === 'approved' ? 'active' : '' ?>">
                                        <i class="fas fa-check me-2"></i>Onaylananlar
                                    </a>
                                    <a href="?filter=all"
                                       class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                                        <i class="fas fa-list me-2"></i>Tümü
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <form method="GET" class="d-flex">
                                    <input type="hidden" name="filter" value="<?= $filter ?>">
                                    <input type="text"
                                           name="search"
                                           class="form-control me-2"
                                           placeholder="İsim veya email ile ara..."
                                           value="<?= clean($search) ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if (hasFlash()): ?>
                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($msg = getFlash('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Diyetisyen Listesi -->
                    <?php if (count($dietitians) === 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= $filter === 'pending' ? 'Bekleyen diyetisyen başvurusu yok.' : 'Diyetisyen bulunamadı.' ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($dietitians as $dietitian): ?>
                            <div class="dietitian-card">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h4 class="mb-1"><?= clean($dietitian['full_name']) ?></h4>
                                                <p class="text-muted mb-2"><?= clean($dietitian['title']) ?></p>
                                                <?php if ($dietitian['is_approved']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Onaylandı
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i>Onay Bekliyor
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <p class="mb-2">
                                                <i class="fas fa-envelope text-muted me-2"></i>
                                                <?= clean($dietitian['email']) ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-phone text-muted me-2"></i>
                                                <?= clean($dietitian['phone']) ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-briefcase text-muted me-2"></i>
                                                <?= $dietitian['experience_years'] ?> yıl tecrübe
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-tag text-muted me-2"></i>
                                                <?= clean($dietitian['specialization']) ?>
                                            </p>
                                            <p class="mb-0">
                                                <i class="fas fa-money-bill text-muted me-2"></i>
                                                <?= number_format($dietitian['consultation_fee'], 2) ?> TL / seans
                                            </p>
                                        </div>

                                        <div class="mb-3">
                                            <h6>Hakkında:</h6>
                                            <p class="text-muted"><?= nl2br(clean($dietitian['about_me'])) ?></p>
                                        </div>

                                        <div class="mb-3">
                                            <h6>Eğitim:</h6>
                                            <p class="text-muted"><?= nl2br(clean($dietitian['education'])) ?></p>
                                        </div>

                                        <?php if ($dietitian['rejection_reason']): ?>
                                            <div class="alert alert-danger">
                                                <strong>Red Nedeni:</strong> <?= clean($dietitian['rejection_reason']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php if ($dietitian['diploma_file']): ?>
                                            <h6>Diploma:</h6>
                                            <?php
                                            $ext = pathinfo($dietitian['diploma_file'], PATHINFO_EXTENSION);
                                            $diplomaUrl = '/assets/uploads/' . $dietitian['diploma_file'];
                                            ?>
                                            <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                <img src="<?= $diplomaUrl ?>"
                                                     class="diploma-preview mb-3"
                                                     alt="Diploma">
                                            <?php else: ?>
                                                <a href="<?= $diplomaUrl ?>"
                                                   target="_blank"
                                                   class="btn btn-outline-primary btn-sm mb-3">
                                                    <i class="fas fa-file-pdf me-2"></i>Diplomayı Görüntüle
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (!$dietitian['is_approved']): ?>
                                            <div class="d-grid gap-2">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                    <input type="hidden" name="dietitian_id" value="<?= $dietitian['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="fas fa-check me-2"></i>Onayla
                                                    </button>
                                                </form>

                                                <button type="button"
                                                        class="btn btn-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal<?= $dietitian['id'] ?>">
                                                    <i class="fas fa-times me-2"></i>Reddet
                                                </button>
                                            </div>

                                            <!-- Reddetme Modal -->
                                            <div class="modal fade" id="rejectModal<?= $dietitian['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Başvuruyu Reddet</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                                <input type="hidden" name="dietitian_id" value="<?= $dietitian['id'] ?>">
                                                                <input type="hidden" name="action" value="reject">

                                                                <div class="mb-3">
                                                                    <label class="form-label">Red Nedeni:</label>
                                                                    <textarea name="rejection_reason"
                                                                              class="form-control"
                                                                              rows="4"
                                                                              required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    İptal
                                                                </button>
                                                                <button type="submit" class="btn btn-danger">
                                                                    Reddet
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
