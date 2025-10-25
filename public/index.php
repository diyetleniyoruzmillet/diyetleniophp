<?php
/**
 * Diyetlenio - Modern Ana Sayfa
 * Ultra-modern, profesyonel tasarım
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// İstatistikleri çek
try {
    $conn = $db->getConnection();

    $stmt = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM users WHERE user_type = 'dietitian' AND is_active = 1) as total_dietitians,
            (SELECT COUNT(*) FROM users WHERE user_type = 'client') as total_clients,
            (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_sessions,
            (SELECT COUNT(*) FROM articles WHERE status = 'approved') as total_articles
    ");
    $stats = $stmt->fetch();

    // Öne çıkan diyetisyenler
    $stmt = $conn->query("
        SELECT u.id, u.full_name, dp.title, dp.specialization, dp.rating_avg,
               dp.total_clients, dp.consultation_fee, u.profile_photo, dp.experience_years
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE dp.is_approved = 1 AND u.is_active = 1
        ORDER BY dp.rating_avg DESC, dp.total_clients DESC
        LIMIT 6
    ");
    $featuredDietitians = $stmt->fetchAll();

    // Son blog yazıları
    $stmt = $conn->query("
        SELECT a.id, a.title, a.excerpt, a.featured_image, a.published_at,
               u.full_name as author_name
        FROM articles a
        INNER JOIN users u ON a.author_id = u.id
        WHERE a.status = 'approved'
        ORDER BY a.published_at DESC
        LIMIT 3
    ");
    $recentArticles = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Homepage error: ' . $e->getMessage());
    $stats = ['total_dietitians' => 0, 'total_clients' => 0, 'completed_sessions' => 0, 'total_articles' => 0];
    $featuredDietitians = [];
    $recentArticles = [];
}
?>
<?php
$pageTitle = 'Sağlıklı Yaşam İçin Profesyonel Beslenme Danışmanlığı';
$metaDescription = 'Uzman diyetisyenlerle online görüşme, kişisel diyet programı ve beslenme danışmanlığı. Sağlıklı yaşam yolculuğunuza hemen başlayın!';
$extraHead = '<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">';
$showNavbar = true;
include __DIR__ . '/../includes/partials/header.php';
?>
    
    <style>
        :root {
            --primary: #56ab2f;
            --primary-dark: #4a9428;
            --secondary: #a8e063;
            --secondary-dark: #8cc653;
            --accent: #56ab2f;
            --accent-light: #a8e063;
            --dark: #0f172a;
            --dark-800: #1e293b;
            --light: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
            background: var(--light);
        }

        /* Glassmorphism Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-size: 1.9rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s;
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 80%;
            height: 2px;
            background: var(--primary);
            transition: transform 0.3s;
        }

        .nav-link:hover::after {
            transform: translateX(-50%) scaleX(1);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: white;
            padding: 0.8rem 2.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.35);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(6, 182, 212, 0.5);
            color: white;
        }

        .btn-primary-custom:active {
            transform: translateY(-1px);
        }

        .btn-outline-custom {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            background: transparent;
        }

        .btn-outline-custom:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Ultra Modern Hero Section with Glassmorphism */
        .hero {
            min-height: 95vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
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
            background:
                radial-gradient(circle at 20% 50%, rgba(86, 171, 47, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 224, 99, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(86, 171, 47, 0.3) 0%, transparent 50%);
            animation: gradient-shift 15s ease infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            opacity: 0.4;
        }

        @keyframes gradient-shift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 900;
            color: white;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 20px rgba(0,0,0,0.15);
            letter-spacing: -1.5px;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero p {
            font-size: 1.4rem;
            color: rgba(255,255,255,0.95);
            margin-bottom: 3rem;
            font-weight: 400;
            line-height: 1.7;
            max-width: 90%;
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        .hero-buttons {
            animation: fadeInUp 0.8s ease-out 0.4s backwards;
        }

        .hero-buttons .btn {
            margin: 0.5rem;
            padding: 1.1rem 2.8rem;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hero-buttons .btn-light {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            color: var(--primary);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .hero-buttons .btn-light:hover {
            background: white;
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        }

        .hero-buttons .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.8);
            color: white;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .hero-buttons .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            transform: translateY(-3px);
        }

        .hero-image {
            position: relative;
            animation: float 6s ease-in-out infinite;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(2deg); }
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

        /* Glassmorphic Stats Section */
        .stats-section {
            background: white;
            padding: 5rem 0;
            margin-top: -80px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            text-align: center;
            padding: 2.5rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px) saturate(180%);
            -webkit-backdrop-filter: blur(10px) saturate(180%);
            border-radius: 24px;
            border: 1px solid rgba(6, 182, 212, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            transform: scaleX(0);
            transition: transform 0.5s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 60px rgba(6, 182, 212, 0.2);
            border-color: rgba(6, 182, 212, 0.3);
        }

        .stat-number {
            font-size: 4rem;
            font-weight: 900;
            letter-spacing: -2px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .stat-label {
            font-size: 1.05rem;
            color: #64748b;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Features Section */
        .features-section {
            padding: 7rem 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
            position: relative;
        }

        .section-title {
            font-size: 3.5rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 1.2rem;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.3rem;
            color: #64748b;
            margin-bottom: 5rem;
            font-weight: 400;
            line-height: 1.6;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px) saturate(180%);
            -webkit-backdrop-filter: blur(10px) saturate(180%);
            border-radius: 28px;
            padding: 3rem 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(226, 232, 240, 0.8);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .feature-card:hover::before {
            opacity: 0.03;
        }

        .feature-card:hover {
            transform: translateY(-20px) scale(1.02);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15);
            border-color: rgba(6, 182, 212, 0.3);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 2rem;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.15) rotate(-5deg);
        }

        .feature-icon.icon-1 {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4);
        }

        .feature-icon.icon-2 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .feature-icon.icon-3 {
            background: linear-gradient(135deg, #7ac74f 0%, #6bb03f 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(122, 199, 79, 0.4);
        }

        .feature-icon.icon-4 {
            background: linear-gradient(135deg, #56ab2f 0%, #4a9428 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(86, 171, 47, 0.4);
        }

        .feature-icon.icon-5 {
            background: linear-gradient(135deg, #a8e063 0%, #8cc653 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(168, 224, 99, 0.4);
        }

        .feature-icon.icon-6 {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(86, 171, 47, 0.4);
        }

        .feature-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--dark);
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }

        .feature-text {
            color: #64748b;
            line-height: 1.8;
            font-size: 1.05rem;
            position: relative;
            z-index: 1;
        }

        /* Modern Dietitian Cards */
        .dietitian-card {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
        }

        .dietitian-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            opacity: 0;
            transition: opacity 0.5s ease;
            z-index: 0;
        }

        .dietitian-card:hover::before {
            opacity: 0.02;
        }

        .dietitian-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.15);
            border-color: rgba(6, 182, 212, 0.3);
        }

        .dietitian-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            transition: transform 0.5s ease;
        }

        .dietitian-card:hover .dietitian-image {
            transform: scale(1.05);
        }

        .dietitian-body {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .dietitian-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .dietitian-title {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            line-height: 1.6;
        }

        .dietitian-rating {
            color: #f59e0b;
            margin-bottom: 1.2rem;
            font-size: 1.05rem;
        }

        .dietitian-price {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.2rem;
        }

        /* Modern CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            padding: 7rem 0;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 30% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 50%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            animation: gradient-shift 10s ease infinite;
        }

        .cta-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
        }

        .cta-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }

        .cta-content h2 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            letter-spacing: -1.5px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .cta-content p {
            font-size: 1.4rem;
            margin-bottom: 3rem;
            opacity: 0.95;
            line-height: 1.6;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer h5 {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
            display: block;
            margin-bottom: 0.75rem;
        }

        .footer a:hover {
            color: white;
            padding-left: 5px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 3rem;
            padding-top: 2rem;
            text-align: center;
            color: rgba(255,255,255,0.5);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3.5rem;
            }
            .section-title {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 768px) {
            .hero {
                min-height: 85vh;
                padding: 2rem 0;
            }
            .hero h1 {
                font-size: 2.8rem;
                letter-spacing: -1px;
            }
            .hero p {
                font-size: 1.15rem;
                max-width: 100%;
            }
            .hero-buttons .btn {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
            .section-title {
                font-size: 2.2rem;
                letter-spacing: -1px;
            }
            .section-subtitle {
                font-size: 1.1rem;
            }
            .stat-number {
                font-size: 3rem;
            }
            .cta-content h2 {
                font-size: 2.5rem;
            }
            .cta-content p {
                font-size: 1.15rem;
            }
            .feature-card,
            .dietitian-card {
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            .hero h1 {
                font-size: 2.2rem;
            }
            .hero p {
                font-size: 1rem;
            }
            .hero-buttons .btn {
                padding: 0.9rem 1.8rem;
                font-size: 0.95rem;
                display: block;
                margin: 0.5rem auto;
            }
            .stats-section {
                padding: 3rem 0;
            }
            .stat-number {
                font-size: 2.5rem;
            }
            .section-title {
                font-size: 1.8rem;
            }
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1>Sağlıklı Yaşamın <br>Başlangıcı</h1>
                    <p>Uzman diyetisyenlerle online görüşme yapın, kişisel diyet programınızı alın ve hedeflerinize ulaşın.</p>
                    <div class="hero-buttons">
                        <a href="/register-client.php" class="btn btn-light btn-lg">
                            <i class="fas fa-rocket me-2"></i>Ücretsiz Başla
                        </a>
                        <a href="/dietitians.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenleri Keşfet
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <div class="hero-image">
                        <i class="fas fa-heartbeat" style="font-size: 20rem; color: rgba(255,255,255,0.2);"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="0">
                    <div class="stat-card">
                        <div class="stat-number" data-counter="<?= $stats['total_dietitians'] ?>"><?= $stats['total_dietitians'] ?>+</div>
                        <div class="stat-label">Uzman Diyetisyen</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-number" data-counter="<?= $stats['total_clients'] ?>"><?= $stats['total_clients'] ?>+</div>
                        <div class="stat-label">Mutlu Kullanıcı</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-number" data-counter="<?= $stats['completed_sessions'] ?>"><?= $stats['completed_sessions'] ?>+</div>
                        <div class="stat-label">Tamamlanan Seans</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Memnuniyet Oranı</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Neden Diyetlenio?</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Sağlıklı yaşam yolculuğunuzda ihtiyacınız olan her şey</p>

            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="feature-card">
                        <div class="feature-icon icon-1">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="feature-title">Online Görüşme</h3>
                        <p class="feature-text">Evinizin konforunda, uzman diyetisyenlerle video görüşme yapın. Zaman ve mekan sınırı olmadan destek alın.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon icon-2">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 class="feature-title">Kişisel Diyet Programı</h3>
                        <p class="feature-text">Size özel hazırlanan diyet programları ile hedeflerinize daha hızlı ulaşın. Bilimsel ve sürdürülebilir çözümler.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon icon-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">İlerleme Takibi</h3>
                        <p class="feature-text">Kilo, ölçüler ve sağlık verilerinizi takip edin. Gelişiminizi grafiklerle görselleştirin.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="feature-card">
                        <div class="feature-icon icon-4">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="feature-title">Sürekli Destek</h3>
                        <p class="feature-text">Diyetisyeninizle mesajlaşma özelliği ile 7/24 iletişimde kalın. Sorularınıza anında yanıt alın.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon icon-5">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3 class="feature-title">Yemek Tarifleri</h3>
                        <p class="feature-text">Sağlıklı ve lezzetli tariflerle beslenme alışkanlıklarınızı kolayca değiştirin.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon icon-6">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobil Erişim</h3>
                        <p class="feature-text">Her yerden, her cihazdan erişim. Mobil uyumlu platform ile her zaman yanınızda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Dietitians -->
    <?php if (count($featuredDietitians) > 0): ?>
    <section class="py-5" style="background: white;">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Öne Çıkan Diyetisyenler</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">En çok tercih edilen uzman diyetisyenlerimiz</p>

            <div class="row g-4">
                <?php
                $delay = 0;
                foreach ($featuredDietitians as $dietitian):
                ?>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="<?= $delay ?>">
                    <div class="dietitian-card">
                        <?php $p=$dietitian['profile_photo'] ?? ''; $photoUrl = $p ? ('/assets/uploads/' . ltrim($p,'/')) : '/images/default-avatar.png'; ?>
                        <img src="<?= $photoUrl ?>"
                             alt="<?= clean($dietitian['full_name']) ?>"
                             class="dietitian-image">
                        <div class="dietitian-body">
                            <h3 class="dietitian-name"><?= clean($dietitian['full_name']) ?></h3>
                            <p class="dietitian-title">
                                <?= clean($dietitian['title']) ?>
                                <?php if ($dietitian['specialization']): ?>
                                    <br><small><?= clean($dietitian['specialization']) ?></small>
                                <?php endif; ?>
                            </p>
                            <div class="dietitian-rating">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star<?= $i < round($dietitian['rating_avg']) ? '' : '-o' ?>"></i>
                                <?php endfor; ?>
                                <span class="ms-2"><?= number_format($dietitian['rating_avg'], 1) ?></span>
                            </div>
                            <div class="dietitian-price">
                                <?= number_format($dietitian['consultation_fee']) ?> ₺
                                <small style="font-size: 0.9rem; color: #64748b;">/seans</small>
                            </div>
                            <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn btn-primary-custom w-100">
                                Profili Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
                <?php
                $delay += 100;
                endforeach;
                ?>
            </div>

            <div class="text-center mt-5" data-aos="fade-up">
                <a href="/dietitians.php" class="btn btn-outline-custom btn-lg">
                    Tüm Diyetisyenleri Gör <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 data-aos="zoom-in">Sağlıklı Yaşam Yolculuğunuza Bugün Başlayın!</h2>
                <p data-aos="zoom-in" data-aos-delay="100">Hemen kayıt olun, uzman diyetisyeninizi bulun ve hedeflerinize ulaşın.</p>
                <div data-aos="zoom-in" data-aos-delay="200">
                    <a href="/register-client.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-user-plus me-2"></i>Ücretsiz Kayıt Ol
                    </a>
                    <a href="/dietitians.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-search me-2"></i>Diyetisyen Bul
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-heartbeat me-2"></i>Diyetlenio</h5>
                    <p class="text-white-50">Sağlıklı yaşam için profesyonel beslenme danışmanlığı platformu.</p>
                    <div class="mt-3">
                        <a href="#" class="btn btn-sm btn-outline-light me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-light"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Hızlı Linkler</h5>
                    <a href="/about.php">Hakkımızda</a>
                    <a href="/dietitians.php">Diyetisyenler</a>
                    <a href="/blog.php">Blog</a>
                    <a href="/recipes.php">Tarifler</a>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Destek</h5>
                    <a href="/faq.php">SSS</a>
                    <a href="/contact.php">İletişim</a>
                    <a href="/help.php">Yardım</a>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Yasal</h5>
                    <a href="/privacy-policy.php">Gizlilik Politikası</a>
                    <a href="/terms.php">Kullanım Şartları</a>
                    <a href="/kvkk.php">KVKK</a>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Kayıt</h5>
                    <a href="/register-client.php">Danışan Kayıt</a>
                    <a href="/register-dietitian.php">Diyetisyen Kayıt</a>
                    <a href="/login.php">Giriş Yap</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>

    <!-- Floating Emergency Dietitian Button -->
    <a href="/dietitians.php" class="emergency-dietitian-btn" id="emergencyBtn" title="Acil Diyetisyen Desteği">
        <div class="emergency-icon">
            <i class="fas fa-user-md"></i>
        </div>
        <span class="emergency-text">Acil Diyetisyen</span>
    </a>

    <style>
        .emergency-dietitian-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 8px 30px rgba(240, 147, 251, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: pulseGlow 2s ease-in-out infinite;
            overflow: hidden;
            max-width: 70px;
            white-space: nowrap;
        }

        .emergency-dietitian-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .emergency-dietitian-btn:hover::before {
            left: 100%;
        }

        .emergency-dietitian-btn:hover {
            color: white;
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 12px 45px rgba(240, 147, 251, 0.7);
            max-width: 250px;
        }

        .emergency-icon {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            animation: pulse 2s ease-in-out infinite;
        }

        .emergency-text {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }

        .emergency-dietitian-btn:hover .emergency-text {
            opacity: 1;
            max-width: 200px;
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 8px 30px rgba(240, 147, 251, 0.5);
            }
            50% {
                box-shadow: 0 8px 40px rgba(240, 147, 251, 0.8), 0 0 0 10px rgba(240, 147, 251, 0.1);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .emergency-dietitian-btn {
                bottom: 20px;
                right: 20px;
                padding: 14px 20px;
                font-size: 0.9rem;
                max-width: 60px;
            }

            .emergency-icon {
                width: 32px;
                height: 32px;
                font-size: 1.1rem;
            }

            .emergency-dietitian-btn:hover {
                max-width: 220px;
            }
        }

        /* Hide on print */
        @media print {
            .emergency-dietitian-btn {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS (Animate On Scroll)
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animated counter for statistics
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target + '+';
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current) + '+';
                }
            }, 16);
        }

        // Initialize counters when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('[data-counter]');
                    counters.forEach(counter => {
                        const target = parseInt(counter.getAttribute('data-counter'));
                        animateCounter(counter, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }

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

        // Emergency Dietitian Button functionality
        document.getElementById('emergencyBtn')?.addEventListener('click', function(e) {
            // Optional: Add analytics tracking here
            console.log('Emergency Dietitian button clicked');
        });

        // Add subtle bounce animation on page load for emergency button
        const emergencyBtn = document.getElementById('emergencyBtn');
        if (emergencyBtn) {
            setTimeout(() => {
                emergencyBtn.style.animation = 'pulseGlow 2s ease-in-out infinite, fadeInUp 0.6s ease-out';
            }, 500);
        }
    </script>
</body>
</html>
