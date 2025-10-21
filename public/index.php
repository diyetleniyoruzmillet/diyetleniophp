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

    // Popüler diyetisyenler (en yüksek puanlı, onaylı)
    $stmt = $conn->query("
        SELECT u.id, u.full_name, dp.title, dp.specialization, dp.rating_avg,
               dp.total_clients, dp.consultation_fee, u.profile_photo
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE dp.is_approved = 1 AND u.is_active = 1
        ORDER BY dp.rating_avg DESC, dp.total_clients DESC
        LIMIT 3
    ");
    $topDietitians = $stmt->fetchAll();

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
    $topDietitians = [];
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
    <style>
        :root {
            --primary-color: #28a745;
            --secondary-color: #20c997;
            --dark-color: #2d3748;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }

        /* How It Works */
        .how-it-works {
            padding: 80px 0;
        }

        .step-card {
            text-align: center;
            padding: 30px 20px;
        }

        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Dietitians Section */
        .dietitians-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .dietitian-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .dietitian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .dietitian-avatar {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }

        .dietitian-info {
            padding: 25px;
        }

        .rating {
            color: #ffc107;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        /* Footer */
        footer {
            background: var(--dark-color);
            color: white;
            padding: 50px 0 20px;
        }

        footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: white;
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            line-height: 40px;
            text-align: center;
            margin: 0 5px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Özellikler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#dietitians">Diyetisyenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">Nasıl Çalışır</a>
                    </li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $auth->user()->getUserType() === 'admin' ? '/admin/dashboard.php' : ($auth->user()->getUserType() === 'dietitian' ? '/dietitian/dashboard.php' : '/client/dashboard.php') ?>">
                                <i class="fas fa-user-circle me-1"></i><?= clean($auth->user()->getFullName()) ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">Çıkış</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-light text-success ms-2" href="/register-client.php">Ücretsiz Başla</a>
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
            <div class="row align-items-center hero-content">
                <div class="col-lg-6">
                    <h1 class="hero-title">Sağlıklı Yaşam İçin Profesyonel Destek</h1>
                    <p class="hero-subtitle">
                        Uzman diyetisyenlerle online görüşme yapın, kişisel diyet programınızı alın ve
                        hedeflerinize ulaşın.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="/register-client.php" class="btn btn-light btn-hero text-success">
                            <i class="fas fa-rocket me-2"></i>Hemen Başla
                        </a>
                        <a href="/register-dietitian.php" class="btn btn-outline-light btn-hero">
                            <i class="fas fa-user-md me-2"></i>Diyetisyen Olarak Katıl
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <i class="fas fa-apple-alt" style="font-size: 15rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Neden Diyetlenio?</h2>
                <p class="lead text-muted">Platform özelliklerimizi keşfedin</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-video"></i>
                        </div>
                        <h4 class="mb-3">Online Görüşme</h4>
                        <p class="text-muted">
                            Diyetisyeninizle video konferans üzerinden yüz yüze görüşme yapın.
                            Evden çıkmadan profesyonel destek alın.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="mb-3">Kolay Randevu</h4>
                        <p class="text-muted">
                            Diyetisyenlerin müsaitlik durumlarını görün ve kolayca randevu alın.
                            Hatırlatma bildirimleri alın.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="mb-3">İlerleme Takibi</h4>
                        <p class="text-muted">
                            Kilo, ölçü ve beslenme takibinizi yapın. Gelişiminizi grafiklerle görüntüleyin.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h4 class="mb-3">Diyet Planı</h4>
                        <p class="text-muted">
                            Size özel hazırlanan diyet programlarına erişin. Sağlıklı tarifler keşfedin.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4 class="mb-3">Mesajlaşma</h4>
                        <p class="text-muted">
                            Diyetisyeninizle platform üzerinden güvenli mesajlaşma yapın.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="feature-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="mb-3">Güvenli Platform</h4>
                        <p class="text-muted">
                            Kişisel verileriniz SSL ile şifrelenir. KVKK uyumlu güvenli altyapı.
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
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['total_dietitians'] ?? 0) ?>+</div>
                        <div class="stat-label">Uzman Diyetisyen</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['total_clients'] ?? 0) ?>+</div>
                        <div class="stat-label">Mutlu Danışan</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['completed_sessions'] ?? 0) ?>+</div>
                        <div class="stat-label">Tamamlanan Seans</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($stats['total_articles'] ?? 0) ?>+</div>
                        <div class="stat-label">Blog Yazısı</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dietitians Section -->
    <?php if (count($topDietitians) > 0): ?>
    <section id="dietitians" class="dietitians-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Popüler Diyetisyenlerimiz</h2>
                <p class="lead text-muted">En çok tercih edilen uzmanlarımız</p>
            </div>

            <div class="row g-4">
                <?php foreach ($topDietitians as $dietitian): ?>
                <div class="col-md-4">
                    <div class="card dietitian-card">
                        <div class="dietitian-avatar">
                            <?php if ($dietitian['profile_photo']): ?>
                                <img src="/assets/uploads/<?= clean($dietitian['profile_photo']) ?>" alt="<?= clean($dietitian['full_name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-user-md"></i>
                            <?php endif; ?>
                        </div>
                        <div class="dietitian-info">
                            <h5 class="mb-1"><?= clean($dietitian['full_name']) ?></h5>
                            <p class="text-muted small mb-2"><?= clean($dietitian['title']) ?></p>

                            <div class="rating mb-2">
                                <?php
                                $rating = $dietitian['rating_avg'] ?? 0;
                                for($i = 1; $i <= 5; $i++):
                                    if ($i <= $rating): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif;
                                endfor; ?>
                                <span class="text-muted ms-2">(<?= number_format($rating, 1) ?>)</span>
                            </div>

                            <p class="small text-muted mb-3">
                                <i class="fas fa-users me-1"></i><?= $dietitian['total_clients'] ?> danışan
                            </p>

                            <p class="small mb-3"><?= clean(substr($dietitian['specialization'], 0, 100)) ?>...</p>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success"><?= number_format($dietitian['consultation_fee'], 0) ?> TL</span>
                                <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn btn-sm btn-outline-success">
                                    Profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <a href="/dietitians.php" class="btn btn-success btn-lg">
                    Tüm Diyetisyenleri Gör <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- How It Works -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Nasıl Çalışır?</h2>
                <p class="lead text-muted">4 basit adımda sağlıklı yaşam</p>
            </div>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5 class="mb-3">Kayıt Olun</h5>
                        <p class="text-muted">Ücretsiz hesap oluşturun ve profilinizi tamamlayın.</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5 class="mb-3">Diyetisyen Seçin</h5>
                        <p class="text-muted">Size uygun diyetisyeni bulun ve randevu alın.</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5 class="mb-3">Online Görüşme</h5>
                        <p class="text-muted">Video görüşme ile diyetisyeninizle tanışın.</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h5 class="mb-3">Hedeflerinize Ulaşın</h5>
                        <p class="text-muted">Özel programınızla sağlıklı yaşam yolculuğunuza başlayın.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="display-5 fw-bold mb-4">Sağlıklı Yaşam Yolculuğunuza Bugün Başlayın!</h2>
            <p class="lead mb-4">Binlerce kişi Diyetlenio ile hedeflerine ulaştı. Sırada siz varsınız!</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/register-client.php" class="btn btn-light btn-lg btn-hero text-success">
                    <i class="fas fa-user-plus me-2"></i>Ücretsiz Kayıt Ol
                </a>
                <a href="/register-dietitian.php" class="btn btn-outline-light btn-lg btn-hero">
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
                    <h4 class="mb-3">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <p class="text-muted">
                        Sağlıklı yaşam için profesyonel diyetisyen desteği.
                        Online görüşme, kişisel diyet programları ve daha fazlası.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="mb-3">Platform</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/dietitians.php">Diyetisyenler</a></li>
                        <li class="mb-2"><a href="/blog">Blog</a></li>
                        <li class="mb-2"><a href="/recipes">Tarifler</a></li>
                        <li class="mb-2"><a href="/about">Hakkımızda</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="mb-3">Destek</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/help">Yardım Merkezi</a></li>
                        <li class="mb-2"><a href="/contact">İletişim</a></li>
                        <li class="mb-2"><a href="/faq">SSS</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="mb-3">Yasal</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/terms">Kullanım Şartları</a></li>
                        <li class="mb-2"><a href="/privacy">Gizlilik Politikası</a></li>
                        <li class="mb-2"><a href="/kvkk">KVKK</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5 class="mb-3">İletişim</h5>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            info@diyetlenio.com
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            0850 123 4567
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">

            <div class="text-center text-muted">
                <p class="mb-0">
                    &copy; <?= date('Y') ?> Diyetlenio. Tüm hakları saklıdır. | v<?= APP_VERSION ?>
                </p>
            </div>
        </div>
    </footer>

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
    </script>
</body>
</html>
