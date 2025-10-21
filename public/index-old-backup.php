<?php
/**
 * Diyetlenio - Ana Sayfa
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// İstatistikleri çek
try {
    $conn = $db->getConnection();

    // Toplam sayılar
    $stmt = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM users WHERE user_type = 'dietitian' AND is_active = 1) as total_dietitians,
            (SELECT COUNT(*) FROM users WHERE user_type = 'client') as total_clients,
            (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_sessions,
            (SELECT COUNT(*) FROM articles WHERE status = 'approved') as total_articles
    ");
    $stats = $stmt->fetch();

    // Tüm aktif diyetisyenler (12 tane)
    $stmt = $conn->query("
        SELECT u.id, u.full_name, dp.title, dp.specialization, dp.rating_avg,
               dp.total_clients, dp.consultation_fee, u.profile_photo, dp.about_me,
               dp.experience_years
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE dp.is_approved = 1 AND u.is_active = 1
        ORDER BY dp.rating_avg DESC, dp.total_clients DESC
        LIMIT 12
    ");
    $allDietitians = $stmt->fetchAll();

    // Acil nöbetçi diyetisyen (is_on_call = 1 olan varsa)
    $stmt = $conn->query("
        SELECT u.id, u.full_name, dp.title, dp.specialization, u.profile_photo, u.phone
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE dp.is_approved = 1 AND u.is_active = 1 AND dp.is_on_call = 1
        ORDER BY RAND()
        LIMIT 1
    ");
    $emergencyDietitian = $stmt->fetch();

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
    error_log('Homepage stats error: ' . $e->getMessage());
    $stats = ['total_dietitians' => 0, 'total_clients' => 0, 'completed_sessions' => 0, 'total_articles' => 0];
    $allDietitians = [];
    $emergencyDietitian = null;
    $recentArticles = [];
}

$pageTitle = 'Sağlıklı Yaşam İçin Profesyonel Destek';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="Uzman diyetisyenlerle online görüşme, kişisel diyet programı ve beslenme danışmanlığı. Sağlıklı yaşam yolculuğunuza hemen başlayın!">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            --secondary-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --accent-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 30px rgba(0,0,0,0.08);
            padding: 1rem 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(14, 165, 233, 0.1);
        }

        .navbar.scrolled {
            padding: 0.6rem 0;
            box-shadow: 0 4px 40px rgba(0,0,0,0.15);
            background: rgba(255, 255, 255, 1);
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.3s;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: #2d3748 !important;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            padding: 8px 16px !important;
            border-radius: 8px;
            margin: 0 4px;
        }

        .nav-link i {
            opacity: 0.7;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: #0ea5e9 !important;
            background: rgba(14, 165, 233, 0.08);
        }

        .nav-link:hover i {
            opacity: 1;
            transform: translateY(-2px);
        }

        /* Emergency Link */
        .emergency-link {
            color: #dc2626 !important;
            font-weight: 600;
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .emergency-link:hover {
            background: rgba(220, 38, 38, 0.15);
            color: #b91c1c !important;
        }

        .pulse-icon {
            animation: pulse-emergency 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-emergency {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 12px;
            margin-top: 12px;
            min-width: 220px;
        }

        .dropdown-item {
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
            color: #2d3748;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            color: #0ea5e9;
            transform: translateX(5px);
        }

        .dropdown-item i {
            opacity: 0.7;
            transition: all 0.3s;
        }

        .dropdown-item:hover i {
            opacity: 1;
        }

        .dropdown-divider {
            margin: 8px 0;
            opacity: 0.5;
        }

        /* User Menu */
        .user-menu {
            background: rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(14, 165, 233, 0.2);
            font-weight: 600;
            color: #0ea5e9 !important;
        }

        .user-menu:hover {
            background: rgba(14, 165, 233, 0.15);
        }

        /* Buttons */
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 28px;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(14, 165, 233, 0.5);
            color: white;
        }

        .btn-dietitian {
            border-color: #10b981;
            color: #10b981;
            font-weight: 600;
            padding: 10px 28px;
            border-radius: 50px;
            transition: all 0.3s;
            border-width: 2px;
        }

        .btn-dietitian:hover {
            background: #10b981;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-login {
            font-weight: 600;
            color: #0ea5e9 !important;
        }

        .btn-login:hover {
            background: rgba(14, 165, 233, 0.1);
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate.slideIn {
            animation: slideIn 0.3s ease-out;
        }

        /* Mobile Responsive */
        @media (max-width: 991px) {
            .navbar-nav {
                padding: 15px 0;
            }

            .nav-link {
                margin: 5px 0;
            }

            .dropdown-menu {
                border: none;
                box-shadow: none;
                background: transparent;
                padding-left: 15px;
            }

            .btn-gradient, .btn-dietitian {
                width: 100%;
                margin-top: 10px;
            }
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            background: var(--primary-gradient);
            overflow: hidden;
            padding: 100px 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -300px;
            right: -200px;
            animation: float 20s ease-in-out infinite;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            bottom: -200px;
            left: -100px;
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, 30px) rotate(180deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            color: white;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            opacity: 0.95;
            margin-bottom: 2.5rem;
            font-weight: 300;
            animation: fadeInUp 1s ease 0.2s both;
        }

        .hero-buttons {
            animation: fadeInUp 1s ease 0.4s both;
        }

        .hero-image {
            animation: fadeInRight 1s ease 0.3s both;
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

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .btn-hero {
            padding: 18px 45px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid white;
        }

        .btn-hero:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .btn-hero-primary {
            background: white;
            color: #0ea5e9;
            border-color: white;
        }

        .btn-hero-outline {
            background: transparent;
            color: white;
            border-color: white;
        }

        .btn-hero-outline:hover {
            background: white;
            color: #0ea5e9;
        }

        /* Feature Cards */
        .features-section {
            padding: 120px 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #718096;
            font-weight: 300;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 45px 35px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #e2e8f0;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            border-color: transparent;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.5rem;
            transition: all 0.4s;
        }

        .feature-card:hover .feature-icon {
            transform: rotateY(360deg) scale(1.1);
        }

        .feature-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .feature-desc {
            color: #718096;
            line-height: 1.7;
        }

        /* Stats Section */
        .stats-section {
            padding: 80px 0;
            background: var(--primary-gradient);
            color: white;
            position: relative;
        }

        .stat-item {
            text-align: center;
            padding: 30px 20px;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            display: block;
        }

        .stat-label {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Dietitians Section */
        .dietitians-section {
            padding: 120px 0;
            background: white;
        }

        .dietitian-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.4s;
            height: 100%;
            border: 1px solid #e2e8f0;
        }

        .dietitian-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .dietitian-header {
            height: 280px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
            position: relative;
            overflow: hidden;
        }

        .dietitian-header::after {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            top: -50%;
            left: -50%;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .dietitian-body {
            padding: 30px;
        }

        .dietitian-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .dietitian-title {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 15px;
        }

        .rating {
            color: #fbbf24;
            margin-bottom: 15px;
        }

        .dietitian-badge {
            display: inline-block;
            padding: 6px 15px;
            background: linear-gradient(135deg, #10b98120 0%, #05966920 100%);
            border-radius: 20px;
            color: #10b981;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .dietitian-price {
            font-size: 1.8rem;
            font-weight: 800;
            color: #10b981;
            margin-bottom: 20px;
        }

        /* How It Works */
        .how-section {
            padding: 120px 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }

        .step-card {
            text-align: center;
            padding: 40px 20px;
            position: relative;
        }

        .step-number {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--secondary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 auto 25px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            transition: all 0.4s;
        }

        .step-card:hover .step-number {
            transform: scale(1.15) rotate(360deg);
        }

        .step-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .step-desc {
            color: #718096;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            background: var(--accent-gradient);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -250px;
            right: -100px;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .cta-desc {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 40px;
        }

        /* Footer */
        footer {
            background: #1a202c;
            color: white;
            padding: 80px 0 30px;
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .footer-desc {
            color: #a0aec0;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .footer-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #a0aec0;
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary-gradient);
            transform: translateY(-5px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 50px;
            padding-top: 30px;
            text-align: center;
            color: #a0aec0;
        }

        /* Scroll to top button */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(14, 165, 233, 0.4);
            z-index: 1000;
        }

        .scroll-top.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-5px);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .hero-title { font-size: 3.5rem; }
            .stat-number { font-size: 3rem; }
        }

        @media (max-width: 992px) {
            .hero-title { font-size: 3rem; }
            .hero-subtitle { font-size: 1.2rem; }
            .section-title { font-size: 2.5rem; }
            .features-section { padding: 80px 0; }
            .dietitians-section { padding: 80px 0; }
            .how-section { padding: 80px 0; }
        }

        @media (max-width: 768px) {
            .hero-section { padding: 60px 0; min-height: auto; }
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1.1rem; margin-bottom: 2rem; }
            .btn-hero { padding: 15px 35px; font-size: 1rem; }
            .section-title { font-size: 2rem; }
            .section-subtitle { font-size: 1rem; }
            .features-section { padding: 60px 0; }
            .dietitians-section { padding: 60px 0; }
            .how-section { padding: 60px 0; }
            .stats-section { padding: 60px 0; }
            .cta-section { padding: 60px 0; }
            .cta-title { font-size: 2rem; }
            .cta-desc { font-size: 1.1rem; }
            .feature-card { padding: 35px 25px; }
            .stat-number { font-size: 2.5rem; }
            .stat-label { font-size: 1rem; }
            footer { padding: 60px 0 30px; }
        }

        @media (max-width: 576px) {
            .hero-title { font-size: 2rem; line-height: 1.3; }
            .hero-subtitle { font-size: 1rem; }
            .btn-hero { padding: 12px 25px; font-size: 0.95rem; }
            .section-title { font-size: 1.75rem; }
            .feature-card { padding: 30px 20px; }
            .feature-icon { width: 70px; height: 70px; font-size: 2rem; }
            .feature-title { font-size: 1.2rem; }
            .dietitian-header { height: 200px; font-size: 4rem; }
            .step-number { width: 70px; height: 70px; font-size: 1.75rem; }
            .stat-item { padding: 20px 10px; }
            .stat-number { font-size: 2rem; }
            .navbar-brand { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">
                            <i class="fas fa-star me-1"></i>Özellikler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#dietitians">
                            <i class="fas fa-user-md me-1"></i>Diyetisyenler
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-book me-1"></i>Kaynaklar
                        </a>
                        <ul class="dropdown-menu animate slideIn" aria-labelledby="resourcesDropdown">
                            <li><a class="dropdown-item" href="/blog"><i class="fas fa-newspaper me-2"></i>Blog</a></li>
                            <li><a class="dropdown-item" href="/recipes"><i class="fas fa-utensils me-2"></i>Tarifler</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/faq"><i class="fas fa-question-circle me-2"></i>SSS</a></li>
                            <li><a class="dropdown-item" href="/help"><i class="fas fa-life-ring me-2"></i>Yardım</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pricing">
                            <i class="fas fa-tag me-1"></i>Fiyatlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link emergency-link" href="#emergency">
                            <i class="fas fa-ambulance me-1 pulse-icon"></i>Acil Nöbetçi
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <?php if ($auth->check()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-menu" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i><?= clean($auth->user()->getFullName()) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end animate slideIn" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?= $auth->user()->getUserType() === 'admin' ? '/admin/dashboard.php' : ($auth->user()->getUserType() === 'dietitian' ? '/dietitian/dashboard.php' : '/client/dashboard.php') ?>">
                                        <i class="fas fa-tachometer-alt me-2"></i>Panel
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn-login" href="/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-gradient" href="/register-client.php">
                                <i class="fas fa-rocket me-1"></i>Ücretsiz Başla
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-primary btn-dietitian" href="/register-dietitian.php">
                                <i class="fas fa-user-md me-1"></i>Diyetisyen Ol
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (hasFlash()): ?>
        <div class="container mt-3">
            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= clean($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= clean($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">Sağlıklı Yaşam Yolculuğunuz Burada Başlıyor</h1>
                    <p class="hero-subtitle">
                        Uzman diyetisyenlerle online görüşme yapın, size özel diyet programı alın ve
                        hedeflerinize profesyonel destekle ulaşın.
                    </p>
                    <div class="d-flex gap-3 flex-wrap hero-buttons">
                        <a href="/register-client.php" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-rocket me-2"></i>Hemen Başla
                        </a>
                        <a href="/register-dietitian.php" class="btn btn-hero btn-hero-outline">
                            <i class="fas fa-user-md me-2"></i>Diyetisyen Katıl
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center hero-image d-none d-lg-block">
                    <i class="fas fa-apple-alt" style="font-size: 20rem; opacity: 0.15;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Neden Diyetlenio?</h2>
                <p class="section-subtitle">Size sunduğumuz eşsiz özellikler</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #0ea5e920 0%, #06b6d420 100%); color: #0ea5e9;">
                            <i class="fas fa-video"></i>
                        </div>
                        <h4 class="feature-title">Online Video Görüşme</h4>
                        <p class="feature-desc">
                            Diyetisyeninizle HD kalitesinde video konferans üzerinden yüz yüze görüşme yapın.
                            Evinizin konforunda profesyonel destek alın.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #10b98120 0%, #05966920 100%); color: #10b981;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="feature-title">Kolay Randevu Sistemi</h4>
                        <p class="feature-desc">
                            Diyetisyenlerin müsaitlik takvimini görün, size uygun saatte hemen randevu alın.
                            Otomatik hatırlatma bildirimleri.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #3b82f620 0%, #2563eb20 100%); color: #3b82f6;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="feature-title">İlerleme Takibi</h4>
                        <p class="feature-desc">
                            Kilo, ölçü ve beslenme takibinizi detaylı yapın. Gelişiminizi interaktif
                            grafiklerle anlık görüntüleyin.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #f9731620 0%, #ea580c20 100%); color: #f97316;">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h4 class="feature-title">Kişisel Diyet Planı</h4>
                        <p class="feature-desc">
                            Size özel hazırlanan günlük diyet programlarına erişin. Besin değerleri ve
                            sağlıklı tariflerle desteklenmiş içerik.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #ec489920 0%, #d9465120 100%); color: #ec4899;">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4 class="feature-title">Güvenli Mesajlaşma</h4>
                        <p class="feature-desc">
                            Diyetisyeninizle platform üzerinden güvenli, şifreli mesajlaşma yapın.
                            Sorularınıza hızlı cevap alın.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: linear-gradient(135deg, #6366f120 0%, #818cf820 100%); color: #6366f1;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="feature-title">%100 Güvenli</h4>
                        <p class="feature-desc">
                            Kişisel ve sağlık verileriniz SSL ile şifrelenir. KVKK uyumlu,
                            ISO sertifikalı güvenli altyapı.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number counter"><?= $stats['total_dietitians'] ?? 0 ?></span>+
                        <div class="stat-label">Uzman Diyetisyen</div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number counter"><?= $stats['total_clients'] ?? 0 ?></span>+
                        <div class="stat-label">Mutlu Danışan</div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number counter"><?= $stats['completed_sessions'] ?? 0 ?></span>+
                        <div class="stat-label">Tamamlanan Seans</div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="stat-item">
                        <span class="stat-number">4.9</span>/5
                        <div class="stat-label">Ortalama Memnuniyet</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Emergency Dietitian Section -->
    <?php if ($emergencyDietitian): ?>
    <section id="emergency" class="emergency-section py-5" style="background: linear-gradient(135deg, #fee140 0%, #fa709a 100%);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-white">
                    <h2 class="display-5 fw-bold mb-3">
                        <i class="fas fa-ambulance me-3"></i>Acil Nöbetçi Diyetisyen
                    </h2>
                    <p class="lead mb-4">
                        Acil durumlarda 7/24 hizmetinizdeyiz! Anında destek alın.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-lg border-0" style="border-radius: 20px;">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($emergencyDietitian['profile_photo']): ?>
                                    <img src="/assets/uploads/<?= clean($emergencyDietitian['profile_photo']) ?>"
                                         alt="<?= clean($emergencyDietitian['full_name']) ?>"
                                         class="rounded-circle me-3"
                                         style="width: 80px; height: 80px; object-fit: cover; border: 4px solid #fa709a;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-danger text-white me-3 d-flex align-items-center justify-content-center"
                                         style="width: 80px; height: 80px; font-size: 2rem;">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="mb-1"><?= clean($emergencyDietitian['full_name']) ?></h4>
                                    <p class="text-muted mb-0"><?= clean($emergencyDietitian['title']) ?></p>
                                    <span class="badge bg-danger"><i class="fas fa-circle me-1" style="font-size: 0.6rem; animation: pulse 2s infinite;"></i>Şu an müsait</span>
                                </div>
                            </div>
                            <a href="/book-appointment.php?dietitian_id=<?= $emergencyDietitian['id'] ?>&emergency=1"
                               class="btn btn-danger w-100 py-3 fw-bold" style="border-radius: 12px;">
                                <i class="fas fa-phone-alt me-2"></i>Hemen Ara veya Randevu Al
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Dietitians Section -->
    <?php if (count($allDietitians) > 0): ?>
    <section id="dietitians" class="dietitians-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Uzman Diyetisyenlerimiz</h2>
                <p class="section-subtitle">Size en uygun diyetisyeni seçin ve sağlıklı yaşama başlayın</p>
            </div>

            <div class="row g-4">
                <?php foreach ($allDietitians as $dietitian): ?>
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
                            <h5 class="dietitian-name"><?= clean($dietitian['full_name']) ?></h5>
                            <p class="dietitian-title"><?= clean($dietitian['title']) ?></p>

                            <div class="rating mb-3">
                                <?php
                                $rating = $dietitian['rating_avg'] ?? 0;
                                for($i = 1; $i <= 5; $i++):
                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                endfor; ?>
                                <span class="text-muted ms-2">(<?= number_format($rating, 1) ?>)</span>
                            </div>

                            <span class="dietitian-badge">
                                <i class="fas fa-users me-1"></i><?= $dietitian['total_clients'] ?> Danışan
                            </span>

                            <span class="badge bg-light text-dark mb-2">
                                <i class="fas fa-briefcase me-1"></i><?= $dietitian['experience_years'] ?? 0 ?> Yıl Deneyim
                            </span>

                            <p class="text-muted small mb-3" style="line-height: 1.6;">
                                <?= clean(mb_substr($dietitian['about_me'] ?? $dietitian['specialization'], 0, 80)) ?>...
                            </p>

                            <div class="dietitian-price"><?= number_format($dietitian['consultation_fee'], 0) ?> ₺
                                <span class="fs-6 text-muted">/seans</span>
                            </div>

                            <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn btn-gradient w-100">
                                Profili Görüntüle <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($allDietitians) >= 12): ?>
            <div class="text-center mt-5">
                <a href="/dietitians.php" class="btn btn-gradient" style="padding: 15px 50px; font-size: 1.1rem;">
                    Daha Fazla Diyetisyen Göster <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- How It Works -->
    <section id="how-it-works" class="how-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Nasıl Çalışır?</h2>
                <p class="section-subtitle">4 basit adımda sağlıklı yaşama başlayın</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5 class="step-title">Ücretsiz Kayıt Olun</h5>
                        <p class="step-desc">
                            Hızlı ve kolay kayıt formu ile hesap oluşturun. Sağlık bilgilerinizi ekleyin.
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5 class="step-title">Diyetisyen Seçin</h5>
                        <p class="step-desc">
                            Uzmanlık alanlarına göre filtreleyin, size en uygun diyetisyeni bulun.
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5 class="step-title">Randevu Alın</h5>
                        <p class="step-desc">
                            Müsait saatlerden size uygun olanı seçin, online görüşme yapın.
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h5 class="step-title">Hedefinize Ulaşın</h5>
                        <p class="step-desc">
                            Özel programınızla, profesyonel destekle sağlıklı yaşam yolculuğunuza başlayın.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Sağlıklı Yaşam Yolculuğunuza Bugün Başlayın!</h2>
            <p class="cta-desc">
                Binlerce kişi Diyetlenio ile hedeflerine ulaştı. Şimdi sıra sizde!
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/register-client.php" class="btn btn-hero btn-hero-primary">
                    <i class="fas fa-user-plus me-2"></i>Ücretsiz Kayıt Ol
                </a>
                <a href="/register-dietitian.php" class="btn btn-hero btn-hero-outline">
                    <i class="fas fa-user-md me-2"></i>Diyetisyen Olarak Katıl
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </div>
                    <p class="footer-desc">
                        Sağlıklı yaşam için profesyonel diyetisyen desteği.
                        Online görüşme, kişisel diyet programları ve daha fazlası ile
                        hedeflerinize ulaşın.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="footer-title">Platform</h5>
                    <ul class="footer-links">
                        <li><a href="/dietitians.php">Diyetisyenler</a></li>
                        <li><a href="/blog.php">Blog</a></li>
                        <li><a href="/recipes.php">Tarifler</a></li>
                        <li><a href="/about.php">Hakkımızda</a></li>
                        <li><a href="/pricing.php">Fiyatlar</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="footer-title">Destek</h5>
                    <ul class="footer-links">
                        <li><a href="/help.php">Yardım Merkezi</a></li>
                        <li><a href="/contact.php">İletişim</a></li>
                        <li><a href="/faq.php">SSS</a></li>
                        <li><a href="/feedback.php">Geri Bildirim</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="footer-title">Yasal</h5>
                    <ul class="footer-links">
                        <li><a href="/terms.php">Kullanım Şartları</a></li>
                        <li><a href="/privacy-policy.php">Gizlilik Politikası</a></li>
                        <li><a href="/kvkk.php">KVKK</a></li>
                        <li><a href="/cookies.php">Çerez Politikası</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="footer-title">İletişim</h5>
                    <ul class="footer-links">
                        <li>
                            <i class="fas fa-envelope me-2"></i>
                            info@diyetlenio.com
                        </li>
                        <li>
                            <i class="fas fa-phone me-2"></i>
                            0850 123 4567
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            İstanbul, Türkiye
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="mb-0">
                    &copy; <?= date('Y') ?> Diyetlenio. Tüm hakları saklıdır. | v<?= APP_VERSION ?>
                </p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top -->
    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Scroll to top button
        const scrollTop = document.getElementById('scrollTop');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                scrollTop.classList.add('show');
            } else {
                scrollTop.classList.remove('show');
            }
        });

        scrollTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Counter animation
        const counters = document.querySelectorAll('.counter');
        const speed = 200;

        const animateCounters = () => {
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.innerText;
                    const count = +counter.getAttribute('data-count') || 0;
                    const inc = target / speed;

                    if (count < target) {
                        counter.setAttribute('data-count', Math.ceil(count + inc));
                        counter.innerText = Math.ceil(count + inc);
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            });
        };

        // Trigger animation when stats section is in view
        const statsSection = document.querySelector('.stats-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>
</html>
