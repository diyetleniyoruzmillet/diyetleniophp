<?php
/**
 * Diyetlenio - Danışan Diyet Planları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'active';
$planId = $_GET['id'] ?? null;

// Diyet planlarını çek
$whereClause = "WHERE dp.client_id = ?";
$params = [$userId];

if ($status === 'active') {
    $whereClause .= " AND dp.is_active = 1";
} elseif ($status === 'past') {
    $whereClause .= " AND dp.is_active = 0";
}

try {
    $stmt = $conn->prepare("
        SELECT dp.*, u.full_name as dietitian_name, dpr.title as dietitian_title
        FROM diet_plans dp
        INNER JOIN users u ON dp.dietitian_id = u.id
        INNER JOIN dietitian_profiles dpr ON u.id = dpr.user_id
        {$whereClause}
        ORDER BY dp.created_at DESC
    ");
    $stmt->execute($params);
    $plans = $stmt->fetchAll();
} catch (Exception $e) {
    $plans = [];
}

$pageTitle = 'Diyet Planlarım';
include __DIR__ . '/../../includes/client_header.php';
?>

<style>
    .plan-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border-left: 4px solid #56ab2f;
    }
    .plan-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .plan-card.active {
        border-left-color: #ffc107;
        background: #fffbf0;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Diyet Planlarım</h2>
    <div class="btn-group">
        <a href="?status=active" class="btn btn-sm <?= $status === 'active' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="fas fa-check-circle me-1"></i>Aktif
        </a>
        <a href="?status=past" class="btn btn-sm <?= $status === 'past' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="fas fa-history me-1"></i>Geçmiş
        </a>
    </div>
</div>

<?php if (count($plans) === 0): ?>
    <div class="text-center py-5">
        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">Henüz diyet planı bulunmuyor</h4>
        <p class="text-muted">Diyetisyeniniz size bir plan oluşturduğunda burada görünecek.</p>
    </div>
<?php else: ?>
    <?php foreach ($plans as $plan): ?>
        <div class="plan-card <?= $planId == $plan['id'] ? 'active' : '' ?>">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2"><?= clean($plan['title']) ?></h5>
                    <p class="text-muted mb-2">
                        <i class="fas fa-user-md me-2"></i><?= clean($plan['dietitian_name']) ?>
                        <span class="ms-3">
                            <i class="far fa-calendar me-2"></i>
                            <?= date('d.m.Y', strtotime($plan['start_date'])) ?> -
                            <?= date('d.m.Y', strtotime($plan['end_date'])) ?>
                        </span>
                    </p>
                    <?php if ($plan['description']): ?>
                        <p class="mb-0 small"><?= nl2br(clean(substr($plan['description'], 0, 150))) ?>...</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($plan['is_active']): ?>
                        <span class="badge bg-success mb-2">Aktif Plan</span>
                    <?php else: ?>
                        <span class="badge bg-secondary mb-2">Tamamlandı</span>
                    <?php endif; ?>
                    <br>
                    <?php if ($plan['file_path']): ?>
                        <a href="/<?= clean($plan['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i>İndir
                        </a>
                    <?php endif; ?>
                    <a href="?id=<?= $plan['id'] ?>&status=<?= $status ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-eye me-1"></i>Detay
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
