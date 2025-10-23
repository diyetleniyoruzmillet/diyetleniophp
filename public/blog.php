<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Arama ve kategori parametreleri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Blog yazılarını çek
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Kategorileri çek (filtreleme için)
$conn = $db->getConnection();
$categoriesStmt = $conn->query("
    SELECT DISTINCT category
    FROM articles
    WHERE status = 'approved' AND category IS NOT NULL AND category != ''
    ORDER BY category
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

// Featured article (en son yayınlanan)
$featuredStmt = $conn->prepare("
    SELECT a.*, u.full_name as author_name,
           0 as comment_count
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.status = 'approved'
    ORDER BY a.created_at DESC
    LIMIT 1
");
$featuredStmt->execute();
$featuredArticle = $featuredStmt->fetch();

// Ana sorgu - arama ve kategori filtreleri ile
$conditions = ["a.status = 'approved'"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(a.title LIKE ? OR a.content LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($category)) {
    $conditions[] = "a.category = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $conditions);

// Featured article'ı çıkar
if ($featuredArticle) {
    $conditions[] = "a.id != ?";
    $params[] = $featuredArticle['id'];
    $whereClause = implode(' AND ', $conditions);
}

$stmt = $conn->prepare("
    SELECT a.*, u.full_name as author_name,
           0 as comment_count
    FROM articles a
    LEFT JOIN users u ON a.author_id = u.id
    WHERE $whereClause
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");

$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Total count
$countParams = array_slice($params, 0, -2); // Remove limit and offset
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM articles a WHERE $whereClause");
$totalStmt->execute($countParams);
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);

// Okuma süresi hesaplama fonksiyonu
function calculateReadTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200); // Ortalama okuma hızı: 200 kelime/dakika
    return max(1, $minutes);
}

$pageTitle = 'Blog';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0ea5e9;
            --secondary-color: #06b6d4;
            --dark-color: #0f172a;
            --light-gray: #f1f5f9;
            --medium-gray: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            color: var(--dark-color);
            line-height: 1.7;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
            border-bottom: 1px solid var(--border-color);
        }

        .navbar.scrolled {
            box-shadow: var(--shadow-md);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color) !important;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            transition: var(--transition);
            position: relative;
            padding: 0.5rem 1rem !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 60%;
        }

        .nav-link.active {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #cbd5e1;
            font-weight: 400;
            margin-bottom: 2.5rem;
        }

        /* Search and Filter Section */
        .search-filter-section {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 73px;
            z-index: 999;
            box-shadow: var(--shadow-sm);
        }

        .search-box {
            max-width: 600px;
            margin: 0 auto 1.5rem;
        }

        .search-input {
            border: 2px solid var(--border-color);
            border-radius: 50px;
            padding: 14px 24px;
            font-size: 1rem;
            transition: var(--transition);
            box-shadow: none;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            outline: none;
        }

        .search-btn {
            border-radius: 50px;
            padding: 14px 32px;
            font-weight: 600;
            background: var(--primary-color);
            border: none;
            transition: var(--transition);
        }

        .search-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Category Filter */
        .category-filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .category-chip {
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--dark-color);
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }

        .category-chip:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .category-chip.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Featured Article */
        .featured-section {
            padding: 4rem 0;
            background: var(--light-gray);
        }

        .featured-article {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            min-height: 500px;
        }

        .featured-article:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .featured-image {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .featured-article:hover .featured-image img {
            transform: scale(1.1);
        }

        .featured-badge {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(255, 255, 255, 0.95);
            color: var(--dark-color);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: var(--shadow-md);
        }

        .featured-content {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .featured-category {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }

        .featured-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.25rem;
            color: var(--dark-color);
        }

        .featured-excerpt {
            color: var(--medium-gray);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .featured-meta {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            color: var(--medium-gray);
            font-size: 0.95rem;
        }

        .featured-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .featured-meta-item i {
            color: var(--primary-color);
        }

        .featured-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            align-self: flex-start;
        }

        .featured-cta:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateX(4px);
        }

        /* Blog Grid */
        .blog-section {
            padding: 4rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .section-subtitle {
            color: var(--medium-gray);
            font-size: 1.1rem;
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        /* Blog Cards */
        .blog-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
        }

        .blog-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .blog-image {
            position: relative;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .blog-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .blog-card:hover .blog-image img {
            transform: scale(1.1);
        }

        .blog-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .blog-image-placeholder i {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .blog-category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: white;
            color: var(--primary-color);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .blog-content {
            padding: 1.75rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .blog-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .blog-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .blog-title a:hover {
            color: var(--primary-color);
        }

        .blog-excerpt {
            color: var(--medium-gray);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            flex: 1;
        }

        .blog-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
        }

        .blog-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .author-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark-color);
        }

        .author-meta {
            font-size: 0.8rem;
            color: var(--medium-gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .blog-stats {
            display: flex;
            gap: 1rem;
            color: var(--medium-gray);
            font-size: 0.85rem;
        }

        .blog-stat {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .blog-stat i {
            color: var(--primary-color);
        }

        .read-more-btn {
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: var(--light-gray);
            color: var(--dark-color);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .read-more-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(4px);
        }

        /* Pagination */
        .pagination-wrapper {
            margin-top: 4rem;
            display: flex;
            justify-content: center;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .page-item {
            margin: 0;
        }

        .page-link {
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--dark-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: block;
        }

        .page-link:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 0;
        }

        .empty-state-icon {
            font-size: 5rem;
            color: var(--border-color);
            margin-bottom: 1.5rem;
        }

        .empty-state-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
        }

        .empty-state-text {
            color: var(--medium-gray);
            font-size: 1.1rem;
        }

        /* Search Results Header */
        .search-results-header {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-results-text {
            font-weight: 600;
            color: var(--dark-color);
        }

        .search-results-text span {
            color: var(--primary-color);
        }

        .clear-search-btn {
            padding: 0.5rem 1.25rem;
            background: white;
            color: var(--dark-color);
            border: 2px solid var(--border-color);
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-search-btn:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-top: 4rem;
        }

        .footer a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--secondary-color);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .featured-article {
                grid-template-columns: 1fr;
            }

            .featured-image {
                min-height: 350px;
            }

            .hero-title {
                font-size: 3rem;
            }

            .featured-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .blog-grid {
                grid-template-columns: 1fr;
            }

            .search-filter-section {
                top: 72px;
            }

            .category-filters {
                justify-content: flex-start;
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 0.5rem;
            }

            .featured-content {
                padding: 2rem;
            }
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .blog-card {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .blog-card:nth-child(1) { animation-delay: 0.1s; }
        .blog-card:nth-child(2) { animation-delay: 0.2s; }
        .blog-card:nth-child(3) { animation-delay: 0.3s; }
        .blog-card:nth-child(4) { animation-delay: 0.4s; }
        .blog-card:nth-child(5) { animation-delay: 0.5s; }
        .blog-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                        <a class="nav-link active" href="/blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/recipes.php">Tarifler</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="container">
                <h1 class="hero-title">Sağlık ve Beslenme Blogu</h1>
                <p class="hero-subtitle">Uzman diyetisyenlerden en güncel beslenme önerileri ve sağlıklı yaşam rehberi</p>
            </div>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <section class="search-filter-section">
        <div class="container">
            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" action="/blog.php" class="d-flex gap-2">
                    <input type="text"
                           name="search"
                           class="form-control search-input"
                           placeholder="Blog yazılarında ara..."
                           value="<?= clean($search) ?>">
                    <?php if (!empty($category)): ?>
                        <input type="hidden" name="category" value="<?= clean($category) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary search-btn">
                        <i class="fas fa-search me-2"></i>Ara
                    </button>
                </form>
            </div>

            <!-- Category Filters -->
            <?php if (!empty($categories)): ?>
            <div class="category-filters">
                <a href="/blog.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>"
                   class="category-chip <?= empty($category) ? 'active' : '' ?>">
                    <i class="fas fa-globe me-2"></i>Tümü
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="/blog.php?category=<?= urlencode($cat) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                       class="category-chip <?= $category === $cat ? 'active' : '' ?>">
                        <?= clean($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Article -->
    <?php if ($featuredArticle && empty($search) && empty($category) && $page === 1): ?>
    <section class="featured-section">
        <div class="container">
            <div class="featured-article">
                <div class="featured-image">
                    <span class="featured-badge">
                        <i class="fas fa-star me-2"></i>Öne Çıkan
                    </span>
                    <?php if ($featuredArticle['image']): ?>
                        <img src="<?= upload($featuredArticle['image']) ?>" alt="<?= clean($featuredArticle['title']) ?>">
                    <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-newspaper" style="font-size:6rem;color:rgba(255,255,255,0.6);"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="featured-content">
                    <?php if ($featuredArticle['category']): ?>
                        <div class="featured-category"><?= clean($featuredArticle['category']) ?></div>
                    <?php endif; ?>
                    <h2 class="featured-title"><?= clean($featuredArticle['title']) ?></h2>
                    <p class="featured-excerpt"><?= clean(truncate($featuredArticle['content'], 200)) ?></p>
                    <div class="featured-meta">
                        <div class="featured-meta-item">
                            <i class="fas fa-user"></i>
                            <span><?= clean($featuredArticle['author_name'] ?? 'Anonim') ?></span>
                        </div>
                        <div class="featured-meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?= calculateReadTime($featuredArticle['content']) ?> dk okuma</span>
                        </div>
                        <div class="featured-meta-item">
                            <i class="fas fa-comment"></i>
                            <span><?= $featuredArticle['comment_count'] ?> yorum</span>
                        </div>
                    </div>
                    <a href="/blog-detail.php?id=<?= $featuredArticle['id'] ?>" class="featured-cta">
                        Yazıyı Oku
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Blog Grid Section -->
    <section class="blog-section">
        <div class="container">
            <!-- Search Results Header -->
            <?php if (!empty($search) || !empty($category)): ?>
                <div class="search-results-header">
                    <div class="search-results-text">
                        <?php if (!empty($search)): ?>
                            <span>"<?= clean($search) ?>"</span> için <?= $totalArticles ?> sonuç bulundu
                        <?php elseif (!empty($category)): ?>
                            <span><?= clean($category) ?></span> kategorisinde <?= $totalArticles ?> yazı
                        <?php endif; ?>
                    </div>
                    <a href="/blog.php" class="clear-search-btn">
                        <i class="fas fa-times"></i>
                        Filtreleri Temizle
                    </a>
                </div>
            <?php endif; ?>

            <!-- Section Header -->
            <?php if (!empty($articles) && (empty($search) && empty($category))): ?>
            <div class="section-header">
                <h2 class="section-title">Son Yazılar</h2>
                <p class="section-subtitle">Sağlıklı yaşam için bilmeniz gereken her şey</p>
            </div>
            <?php endif; ?>

            <!-- Blog Grid -->
            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-state-title">Sonuç Bulunamadı</h3>
                    <p class="empty-state-text">
                        <?php if (!empty($search)): ?>
                            Aradığınız kriterlere uygun yazı bulunamadı. Farklı anahtar kelimeler deneyebilirsiniz.
                        <?php else: ?>
                            Henüz yayınlanmış yazı bulunmuyor.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="blog-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="blog-card">
                            <div class="blog-image">
                                <?php if ($article['category']): ?>
                                    <span class="blog-category-badge"><?= clean($article['category']) ?></span>
                                <?php endif; ?>
                                <?php if ($article['image']): ?>
                                    <img src="<?= upload($article['image']) ?>" alt="<?= clean($article['title']) ?>">
                                <?php else: ?>
                                    <div class="blog-image-placeholder">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="blog-content">
                                <h3 class="blog-title">
                                    <a href="/blog-detail.php?id=<?= $article['id'] ?>">
                                        <?= clean($article['title']) ?>
                                    </a>
                                </h3>
                                <p class="blog-excerpt"><?= clean(truncate($article['content'], 120)) ?></p>

                                <div class="blog-footer">
                                    <div class="blog-author">
                                        <div class="author-avatar">
                                            <?= strtoupper(substr($article['author_name'] ?? 'A', 0, 1)) ?>
                                        </div>
                                        <div class="author-info">
                                            <div class="author-name"><?= clean($article['author_name'] ?? 'Anonim') ?></div>
                                            <div class="author-meta">
                                                <span><i class="fas fa-clock me-1"></i><?= calculateReadTime($article['content']) ?> dk</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="blog-stats">
                                        <div class="blog-stat">
                                            <i class="fas fa-comment"></i>
                                            <span><?= $article['comment_count'] ?></span>
                                        </div>
                                    </div>
                                </div>

                                <a href="/blog-detail.php?id=<?= $article['id'] ?>" class="read-more-btn">
                                    Devamını Oku
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);

                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($category) ? '&category=' . urlencode($category) : '') . '">1</a></li>';
                                if ($start > 2) {
                                    echo '<li class="page-item"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php
                            endfor;

                            if ($end < $totalPages) {
                                if ($end < $totalPages - 1) {
                                    echo '<li class="page-item"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($category) ? '&category=' . urlencode($category) : '') . '">' . $totalPages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
            <p>Sağlıklı yaşam için en güvenilir kaynak</p>
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

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.blog-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                    card.style.opacity = '1';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
