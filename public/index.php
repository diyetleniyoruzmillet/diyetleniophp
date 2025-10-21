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
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diyetlenio - Sağlıklı Yaşam İçin Profesyonel Beslenme Danışmanlığı</title>
    <meta name="description" content="Uzman diyetisyenlerle online görüşme, kişisel diyet programı ve beslenme danışmanlığı. Sağlıklı yaşam yolculuğunuza hemen başlayın!">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary: #10b981;
            --accent: #f97316;
            --dark: #0f172a;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* Modern Navbar */
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

        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(14, 165, 233, 0.4);
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

        /* Hero Section - Ultra Modern */
        .hero {
            min-height: 90vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,133.3C960,128,1056,96,1152,90.7C1248,85,1344,107,1392,117.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hero p {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.95);
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .hero-buttons .btn {
            margin: 0.5rem;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
        }

        .hero-image {
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Stats Section */
        .stats-section {
            background: white;
            padding: 4rem 0;
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-radius: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 4rem;
        }

        .feature-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(226, 232, 240, 0.5);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-icon.icon-1 {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }

        .feature-icon.icon-2 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .feature-icon.icon-3 {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .feature-icon.icon-4 {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }

        .feature-icon.icon-5 {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(236, 72, 153, 0.3);
        }

        .feature-icon.icon-6 {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .feature-text {
            color: #64748b;
            line-height: 1.7;
            font-size: 1rem;
        }

        /* Dietitian Cards */
        .dietitian-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.4s;
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .dietitian-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        .dietitian-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .dietitian-body {
            padding: 1.5rem;
        }

        .dietitian-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .dietitian-title {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .dietitian-rating {
            color: #f59e0b;
            margin-bottom: 1rem;
        }

        .dietitian-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
            padding: 6rem 0;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
        }

        .cta-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }

        .cta-content h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .cta-content p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
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

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            .hero p {
                font-size: 1.1rem;
            }
            .section-title {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="/dietitians.php">Diyetisyenler</a>
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
                            <a class="btn btn-primary-custom" href="/<?= $auth->user()->getUserType() ?>/dashboard.php">
                                Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary-custom" href="/register-client.php">
                                Hemen Başla
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

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
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total_dietitians'] ?>+</div>
                        <div class="stat-label">Uzman Diyetisyen</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total_clients'] ?>+</div>
                        <div class="stat-label">Mutlu Kullanıcı</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['completed_sessions'] ?>+</div>
                        <div class="stat-label">Tamamlanan Seans</div>
                    </div>
                </div>
                <div class="col-md-3">
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
            <h2 class="section-title">Neden Diyetlenio?</h2>
            <p class="section-subtitle">Sağlıklı yaşam yolculuğunuzda ihtiyacınız olan her şey</p>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-1">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="feature-title">Online Görüşme</h3>
                        <p class="feature-text">Evinizin konforunda, uzman diyetisyenlerle video görüşme yapın. Zaman ve mekan sınırı olmadan destek alın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-2">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 class="feature-title">Kişisel Diyet Programı</h3>
                        <p class="feature-text">Size özel hazırlanan diyet programları ile hedeflerinize daha hızlı ulaşın. Bilimsel ve sürdürülebilir çözümler.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">İlerleme Takibi</h3>
                        <p class="feature-text">Kilo, ölçüler ve sağlık verilerinizi takip edin. Gelişiminizi grafiklerle görselleştirin.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-4">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="feature-title">Sürekli Destek</h3>
                        <p class="feature-text">Diyetisyeninizle mesajlaşma özelliği ile 7/24 iletişimde kalın. Sorularınıza anında yanıt alın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-5">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3 class="feature-title">Yemek Tarifleri</h3>
                        <p class="feature-text">Sağlıklı ve lezzetli tariflerle beslenme alışkanlıklarınızı kolayca değiştirin.</p>
                    </div>
                </div>
                <div class="col-md-4">
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
            <h2 class="section-title">Öne Çıkan Diyetisyenler</h2>
            <p class="section-subtitle">En çok tercih edilen uzman diyetisyenlerimiz</p>

            <div class="row g-4">
                <?php foreach ($featuredDietitians as $dietitian): ?>
                <div class="col-md-4">
                    <div class="dietitian-card">
                        <img src="<?= $dietitian['profile_photo'] ?: '/images/default-avatar.png' ?>"
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
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
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
                <h2>Sağlıklı Yaşam Yolculuğunuza Bugün Başlayın!</h2>
                <p>Hemen kayıt olun, uzman diyetisyeninizi bulun ve hedeflerinize ulaşın.</p>
                <div>
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
