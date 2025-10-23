<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Arama ve filtre parametreleri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$difficulty = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Arama varsa filtrele
$conn = $db->getConnection();

// Build query with filters
$whereConditions = ["status = 'published'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ? OR ingredients LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($category)) {
    $whereConditions[] = "category = ?";
    $params[] = $category;
}

if (!empty($difficulty)) {
    $whereConditions[] = "difficulty = ?";
    $params[] = $difficulty;
}

$whereClause = implode(' AND ', $whereConditions);

$stmt = $conn->prepare("SELECT * FROM recipes WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);

// Get total count
$countParams = array_slice($params, 0, -2); // Remove limit and offset
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM recipes WHERE $whereClause");
$totalStmt->execute($countParams);
$totalRecipes = $totalStmt->fetchColumn();

$recipes = $stmt->fetchAll();
$totalPages = ceil($totalRecipes / $perPage);

// Available categories
$categories = ['Kahvaltı', 'Öğle Yemeği', 'Akşam Yemeği', 'Tatlı', 'Atıştırmalık', 'Smoothie', 'Salata', 'Çorba'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifler - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fef5e7 0%, #fff8f0 50%, #f9fafb 100%);
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.06);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .navbar.scrolled { box-shadow: 0 4px 30px rgba(0,0,0,0.12); }
        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nav-link {
            font-weight: 500;
            color: #4a5568 !important;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-link:hover { color: #ff6b35 !important; }
        .nav-link.active {
            color: #ff6b35 !important;
            font-weight: 600;
        }
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            border-radius: 10px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 50%, #ffa726 100%);
            color: white;
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 2px 4px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        .hero p {
            font-size: 1.3rem;
            font-weight: 300;
            margin-bottom: 30px;
            opacity: 0.95;
            position: relative;
        }

        /* Search Bar */
        .search-container {
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }
        .search-wrapper {
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-radius: 60px;
            overflow: hidden;
        }
        .search-input {
            border: none;
            border-radius: 60px;
            padding: 18px 60px 18px 30px;
            font-size: 1.05rem;
            background: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
        }
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            border: none;
            border-radius: 50px;
            padding: 12px 28px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .search-btn:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 5px 20px rgba(255,107,53,0.4);
        }

        /* Filter Section */
        .filters-section {
            background: white;
            padding: 30px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border-radius: 20px;
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        .filter-pills {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-label {
            font-weight: 600;
            color: #2d3748;
            margin-right: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-pill {
            padding: 10px 24px;
            border-radius: 50px;
            background: #f7fafc;
            color: #4a5568;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .filter-pill:hover {
            background: #fff5f0;
            color: #ff6b35;
            border-color: #ff6b35;
            transform: translateY(-2px);
        }
        .filter-pill.active {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255,107,53,0.3);
        }
        .clear-filters {
            padding: 10px 20px;
            border-radius: 50px;
            background: #fee2e2;
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .clear-filters:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
        }

        /* Masonry Grid */
        .masonry-grid {
            column-count: 4;
            column-gap: 25px;
        }
        @media (max-width: 1200px) { .masonry-grid { column-count: 3; } }
        @media (max-width: 768px) { .masonry-grid { column-count: 2; } }
        @media (max-width: 480px) { .masonry-grid { column-count: 1; } }

        .masonry-item {
            break-inside: avoid;
            margin-bottom: 25px;
            display: inline-block;
            width: 100%;
        }

        /* Recipe Cards */
        .recipe-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            cursor: pointer;
        }
        .recipe-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 50px rgba(255,107,53,0.2);
        }

        /* Recipe Image */
        .recipe-image-wrapper {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
        }
        .recipe-image {
            width: 100%;
            height: auto;
            min-height: 220px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .recipe-card:hover .recipe-image {
            transform: scale(1.1);
        }
        .recipe-image-placeholder {
            width: 100%;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
        }
        .recipe-image-placeholder i {
            font-size: 5rem;
            color: rgba(255,255,255,0.6);
        }

        /* Badges */
        .recipe-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            z-index: 5;
        }
        .difficulty-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .difficulty-easy { background: rgba(52, 211, 153, 0.95); color: white; }
        .difficulty-medium { background: rgba(251, 191, 36, 0.95); color: white; }
        .difficulty-hard { background: rgba(239, 68, 68, 0.95); color: white; }

        .save-recipe-btn {
            background: rgba(255,255,255,0.95);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .save-recipe-btn:hover {
            background: #ff6b35;
            color: white;
            transform: scale(1.1);
        }
        .save-recipe-btn i {
            font-size: 1.1rem;
        }

        /* Recipe Content */
        .recipe-content {
            padding: 20px;
        }
        .recipe-category {
            display: inline-block;
            padding: 6px 14px;
            background: linear-gradient(135deg, #fef3e7, #fff5eb);
            color: #f7931e;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
            border: 1px solid #ffe9d0;
        }
        .recipe-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .recipe-description {
            color: #718096;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Recipe Meta */
        .recipe-meta-info {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding: 12px 0;
            border-top: 2px solid #f7fafc;
            border-bottom: 2px solid #f7fafc;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .meta-item i {
            font-size: 1.2rem;
            color: #ff6b35;
        }
        .meta-label {
            font-size: 0.75rem;
            color: #a0aec0;
            font-weight: 500;
        }
        .meta-value {
            font-size: 0.9rem;
            color: #2d3748;
            font-weight: 600;
        }

        /* Nutrition Info */
        .nutrition-info {
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-radius: 12px;
        }
        .nutrition-item {
            text-align: center;
        }
        .nutrition-label {
            font-size: 0.7rem;
            color: #718096;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nutrition-value {
            font-size: 1rem;
            color: #2d3748;
            font-weight: 700;
        }

        /* Ingredient Preview (on hover) */
        .ingredient-preview {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: rgba(26, 32, 44, 0.98);
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
            pointer-events: none;
            z-index: 10;
        }
        .recipe-card:hover .ingredient-preview {
            opacity: 1;
            transform: translateY(0);
        }
        .ingredient-preview h6 {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #ffa726;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .ingredient-preview ul {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 150px;
            overflow-y: auto;
        }
        .ingredient-preview li {
            padding: 5px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .ingredient-preview li:last-child {
            border-bottom: none;
        }

        /* Action Buttons */
        .recipe-actions {
            display: flex;
            gap: 10px;
        }
        .btn-view-recipe {
            flex: 1;
            padding: 12px 20px;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(255,107,53,0.3);
        }
        .btn-view-recipe:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255,107,53,0.4);
            color: white;
        }

        /* Results Info */
        .results-info {
            background: white;
            padding: 20px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .results-text {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
        }
        .results-text strong {
            color: #ff6b35;
        }

        /* Pagination */
        .pagination {
            margin-top: 50px;
        }
        .pagination .page-link {
            border: 2px solid #f7fafc;
            color: #4a5568;
            font-weight: 600;
            padding: 12px 20px;
            margin: 0 5px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .pagination .page-link:hover {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            border-color: #ff6b35;
            transform: translateY(-2px);
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            border-color: #ff6b35;
            box-shadow: 0 4px 15px rgba(255,107,53,0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .empty-state i {
            font-size: 5rem;
            color: #e2e8f0;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #718096;
            font-size: 1.1rem;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            color: white;
            padding: 50px 0;
            margin-top: 80px;
        }
        .footer p {
            margin: 0;
            opacity: 0.9;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .recipe-card {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dietitians.php">Diyetisyenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/recipes.php">Tarifler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="/emergency.php">
                            <i class="fas fa-ambulance me-1"></i>Acil Nöbetçi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact.php">İletişim</a>
                    </li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="/<?= $auth->user()->getUserType() ?>/dashboard.php">
                                Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="/login.php">Giriş Yap</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container text-center">
            <h1><i class="fas fa-utensils me-3"></i>Sağlıklı Tarifler</h1>
            <p>Lezzetli ve besleyici tariflerle sağlıklı yaşamın tadını çıkarın</p>
            <div class="search-container">
                <form method="GET" action="/recipes.php" class="search-wrapper">
                    <input type="text" name="search" class="search-input" placeholder="Tarif ara... (örn: smoothie, salata, çorba)" value="<?= clean($search) ?>">
                    <?php if (!empty($category)): ?>
                        <input type="hidden" name="category" value="<?= clean($category) ?>">
                    <?php endif; ?>
                    <?php if (!empty($difficulty)): ?>
                        <input type="hidden" name="difficulty" value="<?= clean($difficulty) ?>">
                    <?php endif; ?>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search me-2"></i>Ara
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <!-- Filter Section -->
            <div class="filters-section">
                <div class="container">
                    <div class="row">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <div class="filter-pills">
                                <span class="filter-label"><i class="fas fa-layer-group"></i> Kategoriler:</span>
                                <?php foreach ($categories as $cat): ?>
                                    <?php
                                    $params = $_GET;
                                    $params['category'] = $cat;
                                    unset($params['page']);
                                    $url = '/recipes.php?' . http_build_query($params);
                                    ?>
                                    <a href="<?= $url ?>" class="filter-pill <?= $category === $cat ? 'active' : '' ?>">
                                        <?= clean($cat) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="filter-pills">
                                <span class="filter-label"><i class="fas fa-signal"></i> Zorluk:</span>
                                <?php
                                $difficulties = [
                                    'easy' => 'Kolay',
                                    'medium' => 'Orta',
                                    'hard' => 'Zor'
                                ];
                                foreach ($difficulties as $key => $label):
                                    $params = $_GET;
                                    $params['difficulty'] = $key;
                                    unset($params['page']);
                                    $url = '/recipes.php?' . http_build_query($params);
                                ?>
                                    <a href="<?= $url ?>" class="filter-pill <?= $difficulty === $key ? 'active' : '' ?>">
                                        <?= $label ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($search) || !empty($category) || !empty($difficulty)): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <a href="/recipes.php" class="clear-filters">
                                    <i class="fas fa-times-circle"></i> Tüm Filtreleri Temizle
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Results Info -->
            <?php if (!empty($search) || !empty($category) || !empty($difficulty)): ?>
                <div class="results-info">
                    <div class="results-text">
                        <strong><?= $totalRecipes ?></strong> tarif bulundu
                        <?php if (!empty($search)): ?>
                            - "<strong><?= clean($search) ?></strong>"
                        <?php endif; ?>
                        <?php if (!empty($category)): ?>
                            - <strong><?= clean($category) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($difficulty)): ?>
                            - <strong><?= $difficulties[$difficulty] ?? '' ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recipe Cards - Masonry Grid -->
            <?php if (empty($recipes)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Tarif Bulunamadı</h3>
                    <p>Aradığınız kriterlere uygun tarif bulunamadı. Lütfen farklı bir arama yapın.</p>
                    <a href="/recipes.php" class="btn-view-recipe mt-3 d-inline-block" style="width: auto; padding: 12px 40px;">
                        Tüm Tariflere Dön
                    </a>
                </div>
            <?php else: ?>
                <div class="masonry-grid">
                    <?php
                    $difficultyMap = [
                        'easy' => ['class' => 'difficulty-easy', 'label' => 'Kolay'],
                        'medium' => ['class' => 'difficulty-medium', 'label' => 'Orta'],
                        'hard' => ['class' => 'difficulty-hard', 'label' => 'Zor']
                    ];

                    foreach ($recipes as $index => $recipe):
                        $difficultyInfo = $difficultyMap[$recipe['difficulty'] ?? 'easy'] ?? $difficultyMap['easy'];
                        $ingredientsList = !empty($recipe['ingredients']) ? explode("\n", $recipe['ingredients']) : [];
                        $ingredientsList = array_filter(array_slice($ingredientsList, 0, 5)); // First 5 ingredients
                    ?>
                        <div class="masonry-item" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="recipe-card">
                                <!-- Ingredient Preview (shows on hover) -->
                                <?php if (!empty($ingredientsList)): ?>
                                    <div class="ingredient-preview">
                                        <h6><i class="fas fa-list-ul me-2"></i>Malzemeler</h6>
                                        <ul>
                                            <?php foreach ($ingredientsList as $ingredient): ?>
                                                <li><?= clean(trim($ingredient)) ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count(explode("\n", $recipe['ingredients'])) > 5): ?>
                                                <li><em>+ daha fazla...</em></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <!-- Image -->
                                <div class="recipe-image-wrapper">
                                    <?php if (!empty($recipe['image'])): ?>
                                        <img src="<?= upload($recipe['image']) ?>" alt="<?= clean($recipe['title']) ?>" class="recipe-image">
                                    <?php else: ?>
                                        <div class="recipe-image-placeholder">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Badges -->
                                    <div class="recipe-badges">
                                        <span class="difficulty-badge <?= $difficultyInfo['class'] ?>">
                                            <?= $difficultyInfo['label'] ?>
                                        </span>
                                        <button class="save-recipe-btn" onclick="event.preventDefault(); alert('Kaydetme özelliği yakında!');">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="recipe-content">
                                    <?php if (!empty($recipe['category'])): ?>
                                        <span class="recipe-category">
                                            <i class="fas fa-tag me-1"></i><?= clean($recipe['category']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <h3 class="recipe-title"><?= clean($recipe['title']) ?></h3>

                                    <?php if (!empty($recipe['description'])): ?>
                                        <p class="recipe-description"><?= clean($recipe['description']) ?></p>
                                    <?php endif; ?>

                                    <!-- Meta Info -->
                                    <div class="recipe-meta-info">
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span class="meta-label">Süre</span>
                                            <span class="meta-value"><?= $recipe['prep_time'] ?? '30' ?> dk</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-fire"></i>
                                            <span class="meta-label">Kalori</span>
                                            <span class="meta-value"><?= $recipe['calories'] ?? '0' ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <span class="meta-label">Porsiyon</span>
                                            <span class="meta-value"><?= $recipe['servings'] ?? '2' ?></span>
                                        </div>
                                    </div>

                                    <!-- Nutrition -->
                                    <div class="nutrition-info">
                                        <div class="nutrition-item">
                                            <div class="nutrition-label">Protein</div>
                                            <div class="nutrition-value"><?= $recipe['protein'] ?? '0' ?>g</div>
                                        </div>
                                        <div class="nutrition-item">
                                            <div class="nutrition-label">Karb</div>
                                            <div class="nutrition-value"><?= $recipe['carbs'] ?? '0' ?>g</div>
                                        </div>
                                        <div class="nutrition-item">
                                            <div class="nutrition-label">Yağ</div>
                                            <div class="nutrition-value"><?= $recipe['fat'] ?? '0' ?>g</div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="recipe-actions">
                                        <a href="/recipe-detail.php?id=<?= $recipe['id'] ?>" class="btn-view-recipe">
                                            <i class="fas fa-arrow-right me-2"></i>Tarifi Görüntüle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php
                            $params = $_GET;
                            for ($i = 1; $i <= $totalPages; $i++):
                                $params['page'] = $i;
                                $url = '/recipes.php?' . http_build_query($params);
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $url ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer text-center">
        <div class="container">
            <p><i class="fas fa-heartbeat me-2"></i> &copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
        </div>
    </footer>

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

        // Prevent default on save buttons (for demo)
        document.querySelectorAll('.save-recipe-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.innerHTML = '<i class="fas fa-heart"></i>';
                this.style.background = '#ff6b35';
                this.style.color = 'white';
            });
        });
    </script>
</body>
</html>
