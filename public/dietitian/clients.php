<?php
/**
 * Diyetlenio - Diyetisyen Danışanlarım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece diyetisyen erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$search = trim($_GET['search'] ?? '');

// Danışanları çek (randevusu olan)
$whereClause = "WHERE a.dietitian_id = ?";
$params = [$userId];

if (!empty($search)) {
    $whereClause .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email, u.phone, u.created_at,
           cp.date_of_birth, cp.gender, cp.height, cp.target_weight,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ? AND status = 'completed') as completed_sessions,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ? AND status = 'scheduled') as upcoming_sessions,
           (SELECT weight FROM weight_tracking WHERE client_id = u.id ORDER BY measurement_date DESC LIMIT 1) as current_weight
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    {$whereClause}
    ORDER BY u.full_name ASC
");
$stmt->execute(array_merge([$userId, $userId], $params));
$clients = $stmt->fetchAll();

$pageTitle = 'Danışanlarım';
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .client-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s;
    }
    .client-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .client-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    .stat-badge {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 10px 15px;
        text-align: center;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Danışanlarım (<?= count($clients) ?>)</h2>
    <form method="GET" class="d-flex">
        <input type="text" name="search" class="form-control me-2"
               placeholder="Danışan ara..." value="<?= clean($search) ?>">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<?php if (count($clients) === 0): ?>
    <div class="text-center py-5">
        <i class="fas fa-users fa-4x text-muted mb-3"></i>
        <h4 class="text-muted">Henüz danışan bulunmuyor</h4>
        <p class="text-muted">Randevu alan danışanlarınız burada görünecek.</p>
    </div>
<?php else: ?>
    <?php foreach ($clients as $client): ?>
        <div class="client-card">
            <div class="row align-items-center">
                <div class="col-md-1">
                    <div class="client-avatar">
                        <?= strtoupper(mb_substr($client['full_name'], 0, 2)) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-1"><?= clean($client['full_name']) ?></h5>
                    <small class="text-muted">
                        <i class="fas fa-envelope me-1"></i><?= clean($client['email']) ?>
                    </small><br>
                    <small class="text-muted">
                        <i class="fas fa-phone me-1"></i><?= clean($client['phone']) ?>
                    </small>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="stat-badge">
                                <div class="text-muted small">Tamamlanan</div>
                                <strong><?= $client['completed_sessions'] ?></strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-badge">
                                <div class="text-muted small">Yaklaşan</div>
                                <strong><?= $client['upcoming_sessions'] ?></strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-badge">
                                <div class="text-muted small">Kilo</div>
                                <strong><?= $client['current_weight'] ? number_format($client['current_weight'], 1) . ' kg' : '-' ?></strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-badge">
                                <div class="text-muted small">Hedef</div>
                                <strong><?= $client['target_weight'] ? number_format($client['target_weight'], 1) . ' kg' : '-' ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <a href="/dietitian/client-detail.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-primary">
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
