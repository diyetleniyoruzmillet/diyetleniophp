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
include __DIR__ . '/../includes/partials/header.php';
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --secondary-color: #3b82f6;
            --accent-color: #f59e0b;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px rgba(0,0,0,0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-bottom-color: transparent;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color);
            transition: transform 0.2s;
        }

        .navbar-brand:hover { transform: scale(1.05); }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            padding: 100px 0 80px;
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
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            line-height: 1.1;
            letter-spacing: -0.02em;
            animation: fadeInUp 0.6s ease-out;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            opacity: 0.95;
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .stats-row {
            margin-top: 50px;
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }

        .stat-item {
            text-align: center;
            padding: 0 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-top: 5px;
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

        /* Sidebar Filter */
        .sidebar-container {
            position: sticky;
            top: 100px;
        }

        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .filter-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-title i {
            color: var(--primary-color);
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .search-box {
            position: relative;
            margin-bottom: 25px;
        }

        .search-box input {
            padding-left: 45px;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Category Filters */
        .category-filter {
            margin-bottom: 15px;
        }

        .category-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 14px 18px;
            background: var(--bg-light);
            border: 2px solid transparent;
            border-radius: 12px;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            text-align: left;
        }

        .category-btn:hover {
            background: rgba(16, 185, 129, 0.05);
            border-color: var(--primary-color);
            transform: translateX(4px);
        }

        .category-btn.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-color: var(--primary-color);
        }

        .category-icon {
            width: 24px;
            text-align: center;
        }

        /* Dietitian Cards */
        .dietitian-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
        }

        .dietitian-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .card-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(16, 185, 129, 0.95);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 10;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dietitian-image {
            height: 280px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
            position: relative;
            overflow: hidden;
        }

        .dietitian-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.3) 100%);
            z-index: 1;
        }

        .dietitian-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .dietitian-card:hover .dietitian-image img {
            transform: scale(1.1);
        }

        .dietitian-body {
            padding: 25px;
        }

        .dietitian-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .dietitian-title {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dietitian-title i {
            color: var(--primary-color);
        }

        /* Rating Stars */
        .rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 15px;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .stars i {
            color: #fbbf24;
            font-size: 0.95rem;
        }

        .stars i.far {
            color: #d1d5db;
        }

        .rating-text {
            margin-left: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .rating-count {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        /* Specialization Badges */
        .specializations {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            min-height: 65px;
        }

        .badge-custom {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.15));
            color: var(--primary-dark);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .badge-custom:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .badge-custom i {
            font-size: 0.75rem;
        }

        /* Price Display */
        .price-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.1));
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
        }

        .price-label {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Info Row */
        .info-row {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding: 12px 0;
            border-top: 1px solid var(--border-color);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .info-item i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Action Buttons */
        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-book {
            flex: 1;
            background: white;
            color: var(--primary-color);
            padding: 14px 24px;
            border-radius: 12px;
            border: 2px solid var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-book:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        /* Results Header */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px 0;
        }

        .results-info {
            font-size: 1.1rem;
            color: var(--text-dark);
            font-weight: 600;
        }

        .results-count {
            color: var(--primary-color);
        }

        .view-toggle {
            display: flex;
            gap: 8px;
            background: white;
            padding: 6px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 8px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.2s;
        }

        .view-btn.active {
            background: var(--primary-color);
            color: white;
        }

        /* Pagination */
        .pagination {
            margin-top: 60px;
            display: flex;
            justify-content: center;
        }

        .pagination .page-link {
            border: 2px solid var(--border-color);
            color: var(--text-dark);
            padding: 12px 20px;
            margin: 0 4px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .pagination .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-color: var(--primary-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--text-light);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 30px;
        }

        .modal-body {
            padding: 30px;
        }

        /* Loading Animation */
        .loading-skeleton {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar-container {
                position: static;
                margin-bottom: 30px;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .filter-card {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .stats-row {
                margin-top: 30px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .card-actions {
                flex-direction: column;
            }

            .results-header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center hero-content">
            <h1 class="hero-title">Uzman Diyetisyenlerimiz</h1>
            <p class="hero-subtitle">Size en uygun diyetisyeni bulun ve sağlıklı yaşam yolculuğunuza başlayın</p>

            <div class="row stats-row justify-content-center">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number"><?= $totalCount ?>+</span>
                        <span class="stat-label">Uzman Diyetisyen</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">5000+</span>
                        <span class="stat-label">Mutlu Danışan</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">4.8</span>
                        <span class="stat-label">Ortalama Puan</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="sidebar-container">
                    <div class="filter-card">
                        <h3 class="filter-title">
                            <i class="fas fa-search"></i>
                            Arama
                        </h3>
                        <form method="GET" action="/dietitians.php" id="filterForm">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="search" class="form-control" placeholder="İsim, uzmanlık ara..." value="<?= clean($search) ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3">
                                    <i class="fas fa-star text-warning me-2"></i>Minimum Puan
                                </label>
                                <select name="min_rating" class="form-select">
                                    <option value="">Tüm Puanlar</option>
                                    <option value="3" <?= $minRating == 3 ? 'selected' : '' ?>>3+ Yıldız</option>
                                    <option value="4" <?= $minRating == 4 ? 'selected' : '' ?>>4+ Yıldız</option>
                                    <option value="4.5" <?= $minRating == 4.5 ? 'selected' : '' ?>>4.5+ Yıldız</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3">
                                    <i class="fas fa-sort text-primary me-2"></i>Sıralama
                                </label>
                                <select name="sort" class="form-select">
                                    <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>En Yüksek Puan</option>
                                    <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Fiyat: Düşük-Yüksek</option>
                                    <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Fiyat: Yüksek-Düşük</option>
                                    <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>İsme Göre (A-Z)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-view w-100 mb-2">
                                <i class="fas fa-filter me-2"></i>Filtrele
                            </button>
                            <a href="/dietitians.php" class="btn btn-book w-100">
                                <i class="fas fa-redo me-2"></i>Sıfırla
                            </a>
                        </form>
                    </div>

                    <!-- Popular Specializations -->
                    <div class="filter-card">
                        <h3 class="filter-title">
                            <i class="fas fa-fire"></i>
                            Popüler Uzmanlıklar
                        </h3>
                        <div class="category-filter">
                            <a href="?specialization=Zayıflama" class="category-btn <?= $specialization === 'Zayıflama' ? 'active' : '' ?>">
                                <span class="category-icon"><i class="fas fa-weight"></i></span>
                                <span>Zayıflama</span>
                            </a>
                        </div>
                        <div class="category-filter">
                            <a href="?specialization=Spor" class="category-btn <?= $specialization === 'Spor' ? 'active' : '' ?>">
                                <span class="category-icon"><i class="fas fa-dumbbell"></i></span>
                                <span>Spor Diyeti</span>
                            </a>
                        </div>
                        <div class="category-filter">
                            <a href="?specialization=Diyabet" class="category-btn <?= $specialization === 'Diyabet' ? 'active' : '' ?>">
                                <span class="category-icon"><i class="fas fa-heartbeat"></i></span>
                                <span>Diyabet</span>
                            </a>
                        </div>
                        <div class="category-filter">
                            <a href="?specialization=Çocuk" class="category-btn <?= $specialization === 'Çocuk' ? 'active' : '' ?>">
                                <span class="category-icon"><i class="fas fa-child"></i></span>
                                <span>Çocuk Beslenmesi</span>
                            </a>
                        </div>
                        <div class="category-filter">
                            <a href="?specialization=Hamilelik" class="category-btn <?= $specialization === 'Hamilelik' ? 'active' : '' ?>">
                                <span class="category-icon"><i class="fas fa-baby"></i></span>
                                <span>Hamilelik</span>
                            </a>
                        </div>
                        <div class="category-filter">
                            <a href="?specialization=Vegan" class="category-btn <?= $specialization === 'Vegan' ? 'active' : '' ?>">
                                <span class="category-icon"><i class="fas fa-leaf"></i></span>
                                <span>Vegan Beslenme</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dietitian List -->
            <div class="col-lg-9">
                <?php if (count($dietitians) > 0): ?>
                    <div class="results-header">
                        <div class="results-info">
                            <span class="results-count"><?= $totalCount ?></span> diyetisyen bulundu
                        </div>
                        <div class="view-toggle">
                            <button class="view-btn active" title="Izgara Görünümü">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="view-btn" title="Liste Görünümü">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($dietitians as $d): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="dietitian-card">
                                    <?php if ($d['rating_avg'] >= 4.5): ?>
                                        <div class="card-badge">
                                            <i class="fas fa-award"></i> Top Rated
                                        </div>
                                    <?php endif; ?>

                                    <div class="dietitian-image">
                                        <?php if ($d['profile_photo']): ?>
                                            <?php $p=$d['profile_photo']; $photoUrl='/assets/uploads/' . ltrim($p,'/'); ?>
                                            <img src="<?= clean($photoUrl) ?>" alt="<?= clean($d['full_name']) ?>">
                                        <?php else: ?>
                                            <i class="fas fa-user-md"></i>
                                        <?php endif; ?>
                                    </div>

                                    <div class="dietitian-body">
                                        <h3 class="dietitian-name"><?= clean($d['full_name']) ?></h3>
                                        <p class="dietitian-title">
                                            <i class="fas fa-graduation-cap"></i>
                                            <?= clean($d['title'] ?? 'Diyetisyen') ?>
                                        </p>

                                        <div class="rating">
                                            <div class="stars">
                                                <?php
                                                $rating = $d['rating_avg'] ?? 0;
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                }
                                                ?>
                                            </div>
                                            <span class="rating-text"><?= number_format($rating, 1) ?></span>
                                            <span class="rating-count">(<?= $d['rating_count'] ?> değerlendirme)</span>
                                        </div>

                                        <div class="specializations">
                                            <?php if ($d['specialization']): ?>
                                                <?php foreach (array_slice(explode(',', $d['specialization']), 0, 3) as $spec): ?>
                                                    <span class="badge-custom">
                                                        <i class="fas fa-check-circle"></i>
                                                        <?= clean(trim($spec)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="price-section">
                                            <div>
                                                <div class="price"><?= number_format($d['consultation_fee'], 0) ?> ₺</div>
                                                <div class="price-label">Konsültasyon ücreti</div>
                                            </div>
                                        </div>

                                        <div class="info-row">
                                            <div class="info-item">
                                                <i class="fas fa-users"></i>
                                                <span class="info-value"><?= $d['total_clients'] ?></span> Danışan
                                            </div>
                                            <?php if ($d['experience_years']): ?>
                                                <div class="info-item">
                                                    <i class="fas fa-briefcase"></i>
                                                    <span class="info-value"><?= $d['experience_years'] ?></span> Yıl
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-actions">
                                            <a href="/dietitian-profile.php?id=<?= $d['id'] ?>" class="btn btn-view">
                                                <i class="fas fa-eye"></i>
                                                Profil
                                            </a>
                                            <a href="/dietitian-profile.php?id=<?= $d['id'] ?>#book" class="btn btn-book">
                                                <i class="fas fa-calendar-check"></i>
                                                Randevu Al
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&specialization=<?= urlencode($specialization) ?>&min_rating=<?= $minRating ?>&sort=<?= $sort ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&specialization=<?= urlencode($specialization) ?>&min_rating=<?= $minRating ?>&sort=<?= $sort ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&specialization=<?= urlencode($specialization) ?>&min_rating=<?= $minRating ?>&sort=<?= $sort ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Diyetisyen Bulunamadı</h3>
                        <p>Arama kriterlerinize uygun diyetisyen bulunamadı. Filtreleri değiştirip tekrar deneyin.</p>
                        <a href="/dietitians.php" class="btn btn-view mt-3" style="width: auto;">
                            <i class="fas fa-redo me-2"></i>Tüm Diyetisyenleri Göster
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect with smooth transition
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const currentScroll = window.scrollY;

            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        });

        // View toggle functionality
        const viewBtns = document.querySelectorAll('.view-btn');
        const dietitianGrid = document.querySelector('.row.g-4');

        viewBtns.forEach((btn, index) => {
            btn.addEventListener('click', function() {
                viewBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                if (index === 1) {
                    // List view
                    if (dietitianGrid) {
                        dietitianGrid.classList.add('list-view');
                        const cards = dietitianGrid.querySelectorAll('.col-lg-4');
                        cards.forEach(card => {
                            card.className = 'col-12';
                        });
                    }
                } else {
                    // Grid view
                    if (dietitianGrid) {
                        dietitianGrid.classList.remove('list-view');
                        const cards = dietitianGrid.querySelectorAll('.col-12');
                        cards.forEach(card => {
                            card.className = 'col-lg-4 col-md-6';
                        });
                    }
                }
            });
        });

        // Smooth scroll for category buttons
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Add loading animation
                const card = this.closest('.dietitian-card');
                if (card) {
                    card.style.opacity = '0.6';
                    setTimeout(() => {
                        card.style.opacity = '1';
                    }, 300);
                }
            });
        });

        // Card entrance animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(30px)';

                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);

                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all dietitian cards
        document.querySelectorAll('.dietitian-card').forEach(card => {
            observer.observe(card);
        });

        // Auto-submit filter form on change for better UX
        const filterSelects = document.querySelectorAll('#filterForm select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                // Optional: Auto-submit on change
                // document.getElementById('filterForm').submit();
            });
        });

        // Search input debounce
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                // Optional: Add search suggestions or auto-filter
                // searchTimeout = setTimeout(() => {
                //     // Implement search suggestions
                // }, 300);
            });
        }

        // Add hover effect sound/haptic feedback simulation
        document.querySelectorAll('.dietitian-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.willChange = 'transform';
            });

            card.addEventListener('mouseleave', function() {
                this.style.willChange = 'auto';
            });
        });

        // Smooth scroll to top button (optional)
        let scrollTopBtn = document.createElement('button');
        scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollTopBtn.className = 'scroll-top-btn';
        scrollTopBtn.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            font-size: 1.2rem;
        `;

        document.body.appendChild(scrollTopBtn);

        window.addEventListener('scroll', function() {
            if (window.scrollY > 500) {
                scrollTopBtn.style.opacity = '1';
                scrollTopBtn.style.visibility = 'visible';
            } else {
                scrollTopBtn.style.opacity = '0';
                scrollTopBtn.style.visibility = 'hidden';
            }
        });

        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        scrollTopBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 20px rgba(16, 185, 129, 0.4)';
        });

        scrollTopBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 15px rgba(16, 185, 129, 0.3)';
        });

        // Stats counter animation
        function animateCounter(element, target, duration = 2000) {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target + '+';
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start) + '+';
                }
            }, 16);
        }

        // Trigger counter animation when hero is visible
        const heroObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = document.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const text = stat.textContent;
                        const number = parseFloat(text.replace('+', ''));
                        if (!isNaN(number) && number > 10) {
                            stat.textContent = '0+';
                            animateCounter(stat, number);
                        }
                    });
                    heroObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroObserver.observe(heroSection);
        }

        // Add loading state to buttons
        document.querySelectorAll('a.btn-view, a.btn-book').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!this.classList.contains('loading')) {
                    this.classList.add('loading');
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    // Reset after navigation (in case it's prevented)
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.remove('loading');
                    }, 3000);
                }
            });
        });

        // Prevent double-click on filter button
        const filterBtn = document.querySelector('button[type="submit"]');
        if (filterBtn) {
            filterBtn.addEventListener('click', function() {
                this.disabled = true;
                setTimeout(() => {
                    this.disabled = false;
                }, 1000);
            });
        }
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
