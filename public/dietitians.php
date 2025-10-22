<?php
/**
 * Diyetlenio - Diyetisyen Listesi
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// Filtreleme parametreleri
$search = trim($_GET['search'] ?? '');
$specialization = trim($_GET['specialization'] ?? '');
$minRating = (float) ($_GET['min_rating'] ?? 0);
$sort = $_GET['sort'] ?? 'rating'; // rating, price_low, price_high, name

// Sayfalama
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// SQL sorgusu oluştur
$sql = "
    SELECT u.id, u.full_name, u.profile_photo,
           dp.title, dp.specialization, dp.about_me,
           dp.consultation_fee, dp.rating_avg, dp.rating_count,
           dp.total_clients, dp.experience_years
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.user_type = 'dietitian' 
    AND u.is_active = 1 
    AND dp.is_approved = 1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR dp.specialization LIKE ? OR dp.about_me LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($specialization)) {
    $sql .= " AND dp.specialization LIKE ?";
    $params[] = "%{$specialization}%";
}

if ($minRating > 0) {
    $sql .= " AND dp.rating_avg >= ?";
    $params[] = $minRating;
}

// Sıralama
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY dp.consultation_fee ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY dp.consultation_fee DESC";
        break;
    case 'name':
        $sql .= " ORDER BY u.full_name ASC";
        break;
    case 'rating':
    default:
        $sql .= " ORDER BY dp.rating_avg DESC, dp.rating_count DESC";
        break;
}

// Toplam sayıyı al
$countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalCount = $countStmt->fetch()['total'];
$totalPages = ceil($totalCount / $limit);

// Sayfalama ekle
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Diyetisyenleri getir
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$dietitians = $stmt->fetchAll();

$pageTitle = 'Diyetisyenlerimiz';
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
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .navbar.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 0 60px; }
        .hero-title { font-size: 3rem; font-weight: 800; margin-bottom: 15px; }
        .hero-subtitle { font-size: 1.2rem; opacity: 0.95; }
        .filter-section { background: white; padding: 30px; border-radius: 15px; margin-top: -40px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .dietitian-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 3px 15px rgba(0,0,0,0.1); transition: all 0.3s; height: 100%; }
        .dietitian-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.15); }
        .dietitian-image { height: 250px; background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem; }
        .dietitian-image img { width: 100%; height: 100%; object-fit: cover; }
        .dietitian-body { padding: 25px; }
        .dietitian-name { font-size: 1.4rem; font-weight: 700; color: #2d3748; margin-bottom: 5px; }
        .dietitian-title { color: #718096; font-size: 0.95rem; margin-bottom: 15px; }
        .rating { color: #fbbf24; margin-bottom: 10px; }
        .badge-custom { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; margin: 3px; display: inline-block; border: 1px solid #0ea5e9; }
        .price { font-size: 1.8rem; font-weight: 800; color: #0ea5e9; margin: 15px 0; }
        .btn-view { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; width: 100%; padding: 12px; border-radius: 10px; border: none; font-weight: 600; transition: all 0.3s; }
        .btn-view:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(14, 165, 233, 0.3); color: white; }
        .pagination { margin-top: 40px; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-heartbeat me-2 text-success"></i>Diyetlenio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/dietitians.php">Diyetisyenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/recipes.php">Tarifler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact.php">İletişim</a>
                    </li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="btn btn-success ms-2" href="/<?= $auth->user()->getUserType() ?>/dashboard.php">
                                <i class="fas fa-user-circle me-1"></i><?= clean($auth->user()->getFullName()) ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-success ms-2" href="/login.php">Giriş Yap</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="hero-title">Uzman Diyetisyenlerimiz</h1>
            <p class="hero-subtitle">Size en uygun diyetisyeni bulun ve sağlıklı yaşam yolculuğunuza başlayın</p>
        </div>
    </section>

    <!-- Filter Section -->
    <div class="container">
        <div class="filter-section">
            <form method="GET" action="/dietitians.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="İsim, uzmanlık ara..." value="<?= clean($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="min_rating" class="form-select">
                            <option value="">Tüm Puanlar</option>
                            <option value="4" <?= $minRating == 4 ? 'selected' : '' ?>>4+ Yıldız</option>
                            <option value="4.5" <?= $minRating == 4.5 ? 'selected' : '' ?>>4.5+ Yıldız</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-select">
                            <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>En Yüksek Puan</option>
                            <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Fiyat (Düşük-Yüksek)</option>
                            <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Fiyat (Yüksek-Düşük)</option>
                            <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>İsme Göre</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-search me-2"></i>Filtrele
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Dietitian List -->
    <div class="container my-5">
        <?php if (count($dietitians) > 0): ?>
            <div class="row g-4">
                <?php foreach ($dietitians as $d): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="dietitian-card">
                            <div class="dietitian-image">
                                <?php if ($d['profile_photo']): ?>
                                    <img src="/assets/uploads/<?= clean($d['profile_photo']) ?>" alt="<?= clean($d['full_name']) ?>">
                                <?php else: ?>
                                    <i class="fas fa-user-md"></i>
                                <?php endif; ?>
                            </div>
                            <div class="dietitian-body">
                                <h3 class="dietitian-name"><?= clean($d['full_name']) ?></h3>
                                <p class="dietitian-title"><?= clean($d['title'] ?? 'Diyetisyen') ?></p>
                                
                                <div class="rating mb-3">
                                    <?php
                                    $rating = $d['rating_avg'] ?? 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                    <span class="text-muted ms-2">(<?= number_format($rating, 1) ?>)</span>
                                </div>

                                <?php if ($d['specialization']): ?>
                                    <?php foreach (array_slice(explode(',', $d['specialization']), 0, 3) as $spec): ?>
                                        <span class="badge-custom"><?= clean(trim($spec)) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <div class="price"><?= number_format($d['consultation_fee'], 0) ?> ₺</div>

                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i><?= $d['total_clients'] ?> Danışan
                                        <?php if ($d['experience_years']): ?>
                                            • <i class="fas fa-briefcase me-1"></i><?= $d['experience_years'] ?> Yıl Deneyim
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <a href="/dietitian-profile.php?id=<?= $d['id'] ?>" class="btn btn-view">
                                    Profili Görüntüle <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination justify-content-center">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&min_rating=<?= $minRating ?>&sort=<?= $sort ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h3>Diyetisyen Bulunamadı</h3>
                <p class="text-muted">Arama kriterlerinizi değiştirip tekrar deneyin.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
