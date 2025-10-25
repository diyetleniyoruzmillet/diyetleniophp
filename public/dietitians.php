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
$showNavbar = true;
$metaDescription = 'Uzman diyetisyenlerimiz arasından size en uygun olanı bulun. İlk randevu ücretsiz!';
$extraHead = <<<'EOD'
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
        :root {
            --primary-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --hero-gradient: linear-gradient(-45deg, #10b981, #3b82f6, #06b6d4, #8b5cf6);
            --card-gradient: linear-gradient(135deg, #10b981, #3b82f6, transparent);
            --primary-color: #10b981;
            --primary-dark: #059669;
            --secondary-color: #3b82f6;
            --accent-color: #8b5cf6;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-light);
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Hero Section with Animated Gradient */
        .hero-section {
            background: var(--hero-gradient);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            padding: 140px 0 100px;
            position: relative;
            overflow: hidden;
            margin-top: -1px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(255,255,255,0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.8rem;
            font-weight: 900;
            margin-bottom: 24px;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: white;
            text-shadow: 0 2px 20px rgba(0,0,0,0.1);
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-subtitle {
            font-size: 1.35rem;
            color: rgba(255,255,255,0.95);
            font-weight: 400;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
            text-shadow: 0 1px 10px rgba(0,0,0,0.1);
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Top Horizontal Filters */
        .top-filters-wrapper {
            margin-bottom: 48px;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .filter-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 28px;
            padding: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.4s;
        }

        .filter-card:hover::before {
            opacity: 0.5;
        }

        .top-filter-form {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 24px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-label i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .filter-actions {
            display: flex;
            gap: 12px;
        }

        .filter-actions .btn {
            padding: 14px 28px;
            white-space: nowrap;
        }

        /* Specializations Row */
        .specializations-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-top: 28px;
            border-top: 2px solid rgba(226, 232, 240, 0.6);
            flex-wrap: wrap;
        }

        .spec-label {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .spec-label i {
            color: #f59e0b;
            font-size: 1.1rem;
        }

        .spec-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(248, 250, 252, 0.5);
            font-weight: 500;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08);
            background: white;
            transform: translateY(-1px);
        }

        .search-box {
            position: relative;
            margin-bottom: 28px;
        }

        .search-box input {
            padding-left: 50px;
            font-size: 0.95rem;
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.2rem;
        }

        /* Modern Category Buttons (Horizontal) */
        .category-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(248, 250, 252, 0.8);
            border: 2px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            color: var(--text-dark);
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }

        .category-btn:hover {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.2);
            color: var(--primary-dark);
        }

        .category-btn.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, #059669 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
        }

        .category-btn.active:hover {
            transform: translateY(-2px) scale(1.05);
        }

        .category-btn i {
            font-size: 1rem;
        }

        /* Ultra-Modern Dietitian Cards */
        .dietitian-card {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
        }

        .dietitian-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 28px;
            padding: 2px;
            background: var(--card-gradient);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.5s;
        }

        .dietitian-card:hover::before {
            opacity: 1;
        }

        .dietitian-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.15);
        }

        .card-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 0.8rem;
            font-weight: 700;
            z-index: 10;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            animation: badgePulse 2s ease-in-out infinite;
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .dietitian-image {
            height: 380px;
            background: var(--hero-gradient);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.4);
            font-size: 6rem;
            position: relative;
            overflow: hidden;
        }

        .dietitian-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.4) 100%);
            z-index: 1;
        }

        .dietitian-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dietitian-card:hover .dietitian-image img {
            transform: scale(1.15) rotate(2deg);
        }

        .dietitian-body {
            padding: 36px;
        }

        .dietitian-name {
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 10px;
            line-height: 1.3;
            letter-spacing: -0.02em;
        }

        .dietitian-title {
            color: var(--text-light);
            font-size: 1.05rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .dietitian-title i {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        /* Modern Rating Stars */
        .rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        .stars {
            display: flex;
            gap: 4px;
        }

        .stars i {
            color: #fbbf24;
            font-size: 1.05rem;
            filter: drop-shadow(0 1px 2px rgba(251, 191, 36, 0.3));
        }

        .stars i.far {
            color: #d1d5db;
        }

        .rating-text {
            margin-left: 4px;
            color: var(--text-dark);
            font-size: 1rem;
            font-weight: 700;
        }

        .rating-count {
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Modern Specialization Badges */
        .specializations {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
            min-height: 70px;
        }

        .badge-custom {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(59, 130, 246, 0.12));
            color: var(--primary-dark);
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 0.8rem;
            font-weight: 700;
            border: 1.5px solid rgba(16, 185, 129, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .badge-custom:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .badge-custom i {
            font-size: 0.75rem;
        }

        /* Modern Price Display */
        .price-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(59, 130, 246, 0.08));
            padding: 18px 22px;
            border-radius: 16px;
            margin-bottom: 22px;
            border: 1.5px solid rgba(16, 185, 129, 0.2);
        }

        .free-badge {
            font-size: 0.9rem !important;
            padding: 8px 16px !important;
            border-radius: 14px !important;
            font-weight: 800 !important;
            letter-spacing: 0.3px;
        }

        .price-label {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 600;
        }

        /* Modern Info Row */
        .info-row {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 24px;
            padding: 16px 0;
            border-top: 1px solid rgba(226, 232, 240, 0.6);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .info-item i {
            color: var(--secondary-color);
            font-size: 1rem;
        }

        .info-value {
            font-weight: 800;
            color: var(--text-dark);
        }

        /* Ultra-Modern Action Buttons */
        .card-actions {
            display: flex;
            gap: 12px;
        }

        .btn-view {
            flex: 1;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn-view::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-view:hover::before {
            left: 100%;
        }

        .btn-view:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(59, 130, 246, 0.35);
            color: white;
        }

        .btn-book {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .btn-book:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.4);
            color: white;
        }

        /* Dietitians Grid */
        .dietitians-grid {
            margin-top: 24px;
        }

        /* Modern Pagination */
        .pagination {
            margin-top: 72px;
            display: flex;
            justify-content: center;
        }

        .pagination .page-link {
            border: 2px solid rgba(226, 232, 240, 0.8);
            color: var(--text-dark);
            padding: 14px 22px;
            margin: 0 6px;
            border-radius: 14px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .pagination .page-link:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border-color: transparent;
            color: white;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
        }

        .pagination .page-item.disabled .page-link {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Modern Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
            border: 1.5px solid rgba(226, 232, 240, 0.8);
        }

        .empty-state i {
            font-size: 6rem;
            color: var(--text-light);
            margin-bottom: 28px;
            opacity: 0.3;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        .empty-state h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--text-dark);
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.15rem;
            font-weight: 500;
            line-height: 1.7;
            max-width: 500px;
            margin: 0 auto 32px;
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

        /* Modern Responsive Design */
        @media (max-width: 991px) {
            .hero-section {
                padding: 100px 0 70px;
            }

            .hero-title {
                font-size: 2.8rem;
            }

            .hero-subtitle {
                font-size: 1.15rem;
            }

            .filter-card {
                padding: 32px;
            }

            .filter-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .filter-actions {
                flex-direction: column;
            }

            .filter-actions .btn {
                width: 100%;
            }

            .specializations-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .dietitian-name {
                font-size: 1.5rem;
            }

            .dietitian-image {
                height: 320px;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0 60px;
            }

            .hero-title {
                font-size: 2.2rem;
            }

            .hero-subtitle {
                font-size: 1.05rem;
            }

            .filter-card {
                padding: 24px;
                border-radius: 24px;
            }

            .spec-buttons {
                justify-content: center;
            }

            .dietitian-name {
                font-size: 1.4rem;
            }

            .dietitian-image {
                height: 300px;
            }

            .dietitian-body {
                padding: 28px;
            }

            .pagination .page-link {
                padding: 12px 18px;
                margin: 0 3px;
            }

            .empty-state {
                padding: 60px 24px;
            }

            .empty-state i {
                font-size: 4.5rem;
            }

            .empty-state h3 {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 1.8rem;
            }

            .hero-subtitle {
                font-size: 0.95rem;
            }

            .dietitian-body {
                padding: 20px;
            }

            .btn-view, .btn-book {
                padding: 14px 20px;
                font-size: 0.9rem;
            }

            .card-actions {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
EOD;
include __DIR__ . '/../includes/partials/header.php';
?>

    <!-- Hero Section (stats removed for cleaner design) -->
    <section class="hero-section">
        <div class="container text-center hero-content">
            <h1 class="hero-title">Uzman Diyetisyenlerimiz</h1>
            <p class="hero-subtitle">Size en uygun diyetisyeni bulun ve sağlıklı yaşam yolculuğunuza başlayın</p>
        </div>
    </section>

    <!-- Top Filters -->
    <div class="container my-5">
        <div class="top-filters-wrapper">
            <div class="filter-card">
                <form method="GET" action="/dietitians.php" id="filterForm" class="top-filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-search"></i>
                                Arama
                            </label>
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="search" class="form-control" placeholder="İsim, uzmanlık ara..." value="<?= clean($search) ?>">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-star text-warning"></i>
                                Minimum Puan
                            </label>
                            <select name="min_rating" class="form-select">
                                <option value="">Tüm Puanlar</option>
                                <option value="3" <?= $minRating == 3 ? 'selected' : '' ?>>3+ Yıldız</option>
                                <option value="4" <?= $minRating == 4 ? 'selected' : '' ?>>4+ Yıldız</option>
                                <option value="4.5" <?= $minRating == 4.5 ? 'selected' : '' ?>>4.5+ Yıldız</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-sort text-primary"></i>
                                Sıralama
                            </label>
                            <select name="sort" class="form-select">
                                <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>En Yüksek Puan</option>
                                <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Fiyat: Düşük-Yüksek</option>
                                <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Fiyat: Yüksek-Düşük</option>
                                <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>İsme Göre (A-Z)</option>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-book">
                                <i class="fas fa-filter me-2"></i>Filtrele
                            </button>
                            <a href="/dietitians.php" class="btn btn-view">
                                <i class="fas fa-redo me-2"></i>Sıfırla
                            </a>
                        </div>
                    </div>

                    <!-- Popular Specializations -->
                    <div class="specializations-row">
                        <span class="spec-label">
                            <i class="fas fa-fire"></i>
                            Popüler Uzmanlıklar:
                        </span>
                        <div class="spec-buttons">
                            <a href="?specialization=Zayıflama" class="category-btn <?= $specialization === 'Zayıflama' ? 'active' : '' ?>">
                                <i class="fas fa-weight"></i> Zayıflama
                            </a>
                            <a href="?specialization=Spor" class="category-btn <?= $specialization === 'Spor' ? 'active' : '' ?>">
                                <i class="fas fa-dumbbell"></i> Spor Diyeti
                            </a>
                            <a href="?specialization=Diyabet" class="category-btn <?= $specialization === 'Diyabet' ? 'active' : '' ?>">
                                <i class="fas fa-heartbeat"></i> Diyabet
                            </a>
                            <a href="?specialization=Çocuk" class="category-btn <?= $specialization === 'Çocuk' ? 'active' : '' ?>">
                                <i class="fas fa-child"></i> Çocuk
                            </a>
                            <a href="?specialization=Hamilelik" class="category-btn <?= $specialization === 'Hamilelik' ? 'active' : '' ?>">
                                <i class="fas fa-baby"></i> Hamilelik
                            </a>
                            <a href="?specialization=Vegan" class="category-btn <?= $specialization === 'Vegan' ? 'active' : '' ?>">
                                <i class="fas fa-leaf"></i> Vegan
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dietitian List -->
        <div class="dietitians-grid">
            <?php if (count($dietitians) > 0): ?>
                <div class="row g-5">
                    <?php foreach ($dietitians as $d): ?>
                        <div class="col-lg-6">
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
                                                <div class="badge free-badge" style="background:#10b981; color:#fff; padding:6px 10px; border-radius:12px; font-weight:700; font-size:.9rem;">İlk Randevu Ücretsiz</div>
                                                <div class="price-label" style="margin-top:6px;">Ücret görüşmeden sonra</div>
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
                </div>
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

        // Smooth animation for category button clicks
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Add subtle loading effect
                document.body.style.opacity = '0.95';
                setTimeout(() => {
                    document.body.style.opacity = '1';
                }, 200);
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
