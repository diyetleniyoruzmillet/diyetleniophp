<?php
/**
 * Diyetlenio - Hakkımızda Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Hakkımızda';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0ea5e9 !important;
        }

        .hero {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.95;
        }

        .section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #718096;
            text-align: center;
            max-width: 800px;
            margin: 0 auto 60px;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #718096;
            line-height: 1.8;
        }

        .stats {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 80px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .team-image {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .team-image i {
            font-size: 6rem;
            color: white;
            opacity: 0.8;
        }

        .team-content {
            padding: 30px;
        }

        .team-content h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .team-content .role {
            color: #0ea5e9;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .team-content p {
            color: #718096;
            line-height: 1.7;
        }

        .footer {
            background: #1e293b;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        .footer a {
            color: #0ea5e9;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <div class="ms-auto">
                <a href="/" class="btn btn-outline-primary me-2">Ana Sayfa</a>
                <a href="/login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Hakkımızda</h1>
            <p>Sağlıklı yaşam için profesyonel diyetisyen desteğini herkes için erişilebilir kılıyoruz</p>
        </div>
    </section>

    <!-- Mission -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Misyonumuz</h2>
            <p class="section-subtitle">
                Diyetlenio, insanların sağlıklı yaşam hedeflerine ulaşmaları için profesyonel diyetisyen
                desteğini kolay, erişilebilir ve etkili bir şekilde sunmayı amaçlamaktadır. Modern teknoloji
                ile geleneksel diyetisyenlik hizmetlerini birleştirerek, her yerden, her zaman kaliteli
                danışmanlık hizmeti sunuyoruz.
            </p>

            <div class="row g-4 mt-5">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Vizyonumuz</h3>
                        <p>
                            Türkiye'nin en güvenilir online diyetisyen platformu olmak ve milyonlarca
                            insanın sağlıklı yaşam yolculuğuna rehberlik etmek.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Değerlerimiz</h3>
                        <p>
                            Profesyonellik, güvenilirlik, yenilikçilik ve müşteri memnuniyeti
                            temel değerlerimizdir. Her danışanımızı önemsiyoruz.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Topluluk</h3>
                        <p>
                            Binlerce mutlu danışan ve uzman diyetisyenlerimizle birlikte büyüyen
                            bir sağlıklı yaşam topluluğu oluşturuyoruz.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">1000+</div>
                        <div class="stat-label">Mutlu Danışan</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Uzman Diyetisyen</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">5000+</div>
                        <div class="stat-label">Tamamlanan Randevu</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Memnuniyet Oranı</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Nasıl Çalışır?</h2>
            <p class="section-subtitle">
                Sağlıklı yaşam hedeflerinize ulaşmak için sadece birkaç adım uzaktasınız
            </p>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>1. Kayıt Ol</h3>
                        <p>Hızlı ve kolay kayıt işlemi ile hesabınızı oluşturun</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>2. Diyetisyen Seç</h3>
                        <p>Size en uygun diyetisyeni bulun ve randevu alın</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3>3. Online Görüşme</h3>
                        <p>Video görüşme ile diyetisyeninizle tanışın</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>4. İlerleme Kaydet</h3>
                        <p>Hedeflerinize adım adım ilerleyin</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
            <div class="mt-3">
                <a href="/privacy-policy.php" class="me-3">Gizlilik Politikası</a>
                <a href="/terms.php" class="me-3">Kullanım Şartları</a>
                <a href="/contact.php">İletişim</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
