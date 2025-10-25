<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Arama parametresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Blog yazılarını çek
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Database connection
$conn = $db->getConnection();
$categories = []; // Kategori sistemi şu an aktif değil

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

// Ana sorgu - arama filtresi ile
$conditions = ["a.status = 'approved'"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(a.title LIKE ? OR a.content LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
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
$countParams = array_slice($params, 0, -2);
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM articles a WHERE $whereClause");
$totalStmt->execute($countParams);
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);

// Okuma süresi hesaplama fonksiyonu
function calculateReadTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes);
}

$pageTitle = 'Blog - Sağlık ve Beslenme';
$showNavbar = true;
$extraHead = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --dark: #0f172a;
        --light: #f8fafc;
        --border: rgba(99, 102, 241, 0.1);
    }

    body {
        font-family: "Inter", sans-serif;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
        overflow-x: hidden;
    }

    /* Animated Gradient Hero */
    .hero-section {
        background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
        padding: 120px 0 80px;
        position: relative;
        overflow: hidden;
    }

    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .hero-section::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url(\'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>\') bottom center no-repeat;
        background-size: cover;
        opacity: 0.8;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        text-align: center;
        color: white;
    }

    .hero-title {
        font-family: "Playfair Display", serif;
        font-size: 4.5rem;
        font-weight: 900;
        margin-bottom: 1.5rem;
        letter-spacing: -0.03em;
        text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        animation: fadeInUp 0.8s ease-out;
    }

    .hero-subtitle {
        font-size: 1.4rem;
        opacity: 0.95;
        font-weight: 400;
        max-width: 700px;
        margin: 0 auto;
        animation: fadeInUp 0.8s ease-out 0.2s backwards;
    }

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

    /* Glassmorphic Search */
    .search-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 24px;
        padding: 2rem;
        max-width: 800px;
        margin: -60px auto 4rem;
        position: relative;
        z-index: 10;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .search-input {
        border: 2px solid transparent;
        border-radius: 16px;
        padding: 18px 24px;
        font-size: 1.05rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--light);
    }

    .search-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        background: white;
    }

    .search-btn {
        border-radius: 16px;
        padding: 18px 36px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        transition: all 0.3s;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4);
    }

    /* Featured Article - Magazine Style */
    .featured-article {
        background: white;
        border-radius: 32px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 0;
        margin-bottom: 5rem;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .featured-article:hover {
        transform: translateY(-12px) scale(1.01);
        box-shadow: 0 30px 80px rgba(102, 126, 234, 0.2);
    }

    .featured-image {
        position: relative;
        height: 600px;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .featured-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .featured-article:hover .featured-image img {
        transform: scale(1.15) rotate(2deg);
    }

    .featured-badge {
        position: absolute;
        top: 2.5rem;
        left: 2.5rem;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        padding: 0.75rem 1.75rem;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .featured-badge i {
        color: #f59e0b;
        font-size: 1rem;
    }

    .featured-content {
        padding: 4rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .featured-category {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        margin-bottom: 1.5rem;
    }

    .featured-title {
        font-family: "Playfair Display", serif;
        font-size: 3rem;
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        color: var(--dark);
    }

    .featured-excerpt {
        color: #64748b;
        font-size: 1.15rem;
        margin-bottom: 2.5rem;
        line-height: 1.8;
    }

    .featured-meta {
        display: flex;
        gap: 2rem;
        margin-bottom: 2.5rem;
        color: #94a3b8;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .featured-meta-item i {
        color: #667eea;
        margin-right: 0.5rem;
    }

    .featured-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.25rem 2.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 16px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
        align-self: flex-start;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .featured-cta:hover {
        color: white;
        transform: translateX(8px);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
    }

    /* Modern Blog Grid */
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 2.5rem;
        margin-bottom: 4rem;
    }

    /* Ultra-Modern Blog Cards */
    .blog-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0,0,0,0.04);
        position: relative;
    }

    .blog-card::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 24px;
        padding: 2px;
        background: linear-gradient(135deg, #667eea, #764ba2, transparent);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .blog-card:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
    }

    .blog-card:hover::before {
        opacity: 1;
    }

    .blog-image {
        position: relative;
        height: 260px;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .blog-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .blog-card:hover .blog-image img {
        transform: scale(1.15) rotate(-2deg);
    }

    .blog-category-badge {
        position: absolute;
        top: 1.25rem;
        left: 1.25rem;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .blog-content {
        padding: 2rem;
    }

    .blog-title {
        font-family: "Playfair Display", serif;
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.25;
        margin-bottom: 1rem;
        color: var(--dark);
    }

    .blog-title a {
        color: inherit;
        text-decoration: none;
        transition: all 0.3s;
        background: linear-gradient(to right, #667eea, #764ba2);
        background-size: 0% 2px;
        background-repeat: no-repeat;
        background-position: left bottom;
    }

    .blog-title a:hover {
        background-size: 100% 2px;
        color: #667eea;
    }

    .blog-excerpt {
        color: #64748b;
        margin-bottom: 1.5rem;
        line-height: 1.7;
        font-size: 0.95rem;
    }

    .blog-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1.5rem;
        border-top: 2px solid #f1f5f9;
    }

    .blog-author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .author-avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .author-name {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--dark);
    }

    .author-meta {
        font-size: 0.85rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .author-meta i {
        color: #667eea;
    }

    .blog-stats {
        display: flex;
        gap: 1.25rem;
        color: #94a3b8;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .blog-stat i {
        color: #667eea;
        margin-right: 0.35rem;
    }

    .read-more-btn {
        margin-top: 1.5rem;
        padding: 1rem 1.75rem;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: var(--dark);
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        text-align: center;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .read-more-btn:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateX(4px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    /* Modern Pagination */
    .pagination-wrapper {
        margin-top: 5rem;
        display: flex;
        justify-content: center;
    }

    .pagination {
        display: flex;
        gap: 0.75rem;
        list-style: none;
        padding: 0;
    }

    .page-link {
        padding: 1rem 1.5rem;
        border-radius: 14px;
        border: 2px solid transparent;
        background: white;
        color: var(--dark);
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .page-link:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    /* Search Results */
    .search-results-header {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        backdrop-filter: blur(10px);
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 3rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .search-results-text {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .search-results-text span {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .clear-search-btn {
        padding: 0.75rem 1.5rem;
        background: white;
        color: var(--dark);
        border: 2px solid transparent;
        border-radius: 50px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .clear-search-btn:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 5rem 2rem;
    }

    .empty-state-icon {
        font-size: 6rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 2rem;
    }

    .empty-state-title {
        font-family: "Playfair Display", serif;
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }

    .empty-state-text {
        color: #64748b;
        font-size: 1.2rem;
    }

    /* Section Header */
    .section-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .section-title {
        font-family: "Playfair Display", serif;
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--dark) 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .section-subtitle {
        color: #64748b;
        font-size: 1.25rem;
        font-weight: 400;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .featured-article {
            grid-template-columns: 1fr;
        }

        .featured-image {
            height: 400px;
        }

        .featured-content {
            padding: 2.5rem;
        }

        .featured-title {
            font-size: 2.25rem;
        }

        .hero-title {
            font-size: 3rem;
        }

        .blog-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
        }

        .search-section {
            margin: -40px 1rem 3rem;
            padding: 1.5rem;
        }

        .featured-title {
            font-size: 1.85rem;
        }

        .section-title {
            font-size: 2.25rem;
        }

        .search-results-header {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>';

include __DIR__ . '/../includes/partials/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <div class="container">
            <h1 class="hero-title">Sağlık & Beslenme Blogu</h1>
            <p class="hero-subtitle">Uzman diyetisyenlerden en güncel beslenme önerileri ve sağlıklı yaşam rehberi</p>
        </div>
    </div>
</section>

<!-- Glassmorphic Search -->
<div class="container">
    <div class="search-section">
        <form method="GET" action="/blog.php" class="d-flex gap-2">
            <input type="text"
                   name="search"
                   class="form-control search-input"
                   placeholder="Blog yazılarında ara..."
                   value="<?= clean($search) ?>">
            <button type="submit" class="btn btn-primary search-btn">
                <i class="fas fa-search me-2"></i>Ara
            </button>
        </form>
    </div>
</div>

<!-- Featured Article -->
<?php if ($featuredArticle && empty($search) && $page === 1): ?>
<section class="container mb-5">
    <div class="featured-article">
        <div class="featured-image">
            <span class="featured-badge">
                <i class="fas fa-star"></i> ÖNE ÇIKAN
            </span>
            <?php if ($featuredArticle['image']): ?>
                <img src="<?= upload($featuredArticle['image']) ?>" alt="<?= clean($featuredArticle['title']) ?>">
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-newspaper" style="font-size:8rem;color:rgba(255,255,255,0.6);"></i>
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
</section>
<?php endif; ?>

<!-- Blog Grid Section -->
<section class="container py-5">
    <!-- Search Results Header -->
    <?php if (!empty($search)): ?>
        <div class="search-results-header">
            <div class="search-results-text">
                <span>"<?= clean($search) ?>"</span> için <?= $totalArticles ?> sonuç bulundu
            </div>
            <a href="/blog.php" class="clear-search-btn">
                <i class="fas fa-times me-2"></i>Filtreleri Temizle
            </a>
        </div>
    <?php endif; ?>

    <!-- Section Header -->
    <?php if (!empty($articles) && empty($search)): ?>
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
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-newspaper" style="font-size:5rem;color:rgba(255,255,255,0.6);"></i>
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
                                        <i class="fas fa-clock"></i>
                                        <span><?= calculateReadTime($article['content']) ?> dk</span>
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
                            <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Card entrance animation
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.blog-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            observer.observe(card);
        });
    });
</script>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
