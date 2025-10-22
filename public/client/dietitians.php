<?php
/**
 * Diyetlenio - Diyetisyen Listesi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece client erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();

// Filtreleme
$specialization = $_GET['specialization'] ?? '';
$search = trim($_GET['search'] ?? '');

// Diyetisyenleri çek
$whereClause = "WHERE dp.is_approved = 1 AND u.is_active = 1";
$params = [];

if (!empty($specialization)) {
    $whereClause .= " AND dp.specialization = ?";
    $params[] = $specialization;
}

if (!empty($search)) {
    $whereClause .= " AND (u.full_name LIKE ? OR dp.title LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.profile_photo, dp.title, dp.specialization,
           dp.about_me, dp.rating_avg, dp.total_clients, dp.consultation_fee,
           dp.experience_years, dp.accepts_online_sessions, dp.accepts_in_person
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    {$whereClause}
    ORDER BY dp.rating_avg DESC, dp.total_clients DESC
");
$stmt->execute($params);
$dietitians = $stmt->fetchAll();

$pageTitle = 'Diyetisyen Ara';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .navbar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        .search-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .dietitian-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            margin-bottom: 30px;
        }
        .dietitian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .dietitian-header {
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }
        .dietitian-body {
            padding: 25px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <div class="navbar-nav ms-auto">
                <?php if ($auth->check()): ?>
                    <a class="nav-link" href="<?= $auth->user()->getUserType() === 'client' ? '/client/dashboard.php' : '/dietitian/dashboard.php' ?>">
                        <i class="fas fa-user-circle me-1"></i><?= clean($auth->user()->getFullName()) ?>
                    </a>
                <?php else: ?>
                    <a class="btn btn-light text-success" href="/login.php">Giriş Yap</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <h1 class="text-center mb-4">Uzman Diyetisyenimizi Bulun</h1>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-lg"
                           placeholder="Diyetisyen ismi veya ünvan ara..."
                           value="<?= clean($search) ?>">
                </div>
                <div class="col-md-5">
                    <select name="specialization" class="form-select form-select-lg">
                        <option value="">Tüm Uzmanlıklar</option>
                        <option value="Kilo Yönetimi" <?= $specialization === 'Kilo Yönetimi' ? 'selected' : '' ?>>Kilo Yönetimi</option>
                        <option value="Spor Beslenmesi" <?= $specialization === 'Spor Beslenmesi' ? 'selected' : '' ?>>Spor Beslenmesi</option>
                        <option value="Çocuk Beslenmesi" <?= $specialization === 'Çocuk Beslenmesi' ? 'selected' : '' ?>>Çocuk Beslenmesi</option>
                        <option value="Hamilelik Diyeti" <?= $specialization === 'Hamilelik Diyeti' ? 'selected' : '' ?>>Hamilelik Diyeti</option>
                        <option value="Hastalık Diyeti" <?= $specialization === 'Hastalık Diyeti' ? 'selected' : '' ?>>Hastalık Diyeti</option>
                        <option value="Vejetaryen/Vegan" <?= $specialization === 'Vejetaryen/Vegan' ? 'selected' : '' ?>>Vejetaryen/Vegan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-search me-2"></i>Ara
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Results -->
    <section class="py-5">
        <div class="container">
            <div class="mb-4">
                <h3><?= count($dietitians) ?> Diyetisyen Bulundu</h3>
            </div>

            <?php if (count($dietitians) === 0): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Diyetisyen bulunamadı</h4>
                    <p class="text-muted">Lütfen farklı filtreler deneyin.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($dietitians as $dietitian): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="dietitian-card">
                                <div class="dietitian-header">
                                    <?php if ($dietitian['profile_photo']): ?>
                                        <img src="/assets/uploads/<?= clean($dietitian['profile_photo']) ?>"
                                             alt="<?= clean($dietitian['full_name']) ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user-md"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="dietitian-body">
                                    <h5 class="mb-1"><?= clean($dietitian['full_name']) ?></h5>
                                    <p class="text-muted small mb-2"><?= clean($dietitian['title']) ?></p>

                                    <div class="mb-2">
                                        <?php
                                        $rating = $dietitian['rating_avg'] ?? 0;
                                        for($i = 1; $i <= 5; $i++):
                                            echo '<i class="fas fa-star' . ($i > $rating ? ' text-muted' : ' text-warning') . '"></i>';
                                        endfor; ?>
                                        <span class="text-muted ms-2">(<?= number_format($rating, 1) ?>)</span>
                                    </div>

                                    <span class="badge bg-success-subtle text-success mb-2">
                                        <?= clean($dietitian['specialization']) ?>
                                    </span>
                                    <span class="badge bg-info-subtle text-info mb-2">
                                        <?= $dietitian['experience_years'] ?> yıl deneyim
                                    </span>

                                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                                        <?= clean(mb_substr($dietitian['about_me'], 0, 100)) ?>...
                                    </p>

                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span class="h4 mb-0 text-success"><?= number_format($dietitian['consultation_fee'], 0) ?> ₺</span>
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i><?= $dietitian['total_clients'] ?> danışan
                                        </small>
                                    </div>

                                    <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn btn-success w-100">
                                        Profili Görüntüle <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
