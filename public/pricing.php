<?php
/**
 * Diyetlenio - Fiyatlandırma
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Fiyatlandırma';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="Diyetlenio fiyatlandırma planları. Size en uygun paketi seçin ve sağlıklı yaşam yolculuğunuza başlayın.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero {
            padding: 100px 0 60px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.3rem;
            color: #718096;
            max-width: 700px;
            margin: 0 auto;
        }

        .pricing-section {
            padding: 60px 0 100px;
        }

        .pricing-card {
            background: white;
            border-radius: 25px;
            padding: 45px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.4s;
            height: 100%;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border-color: #0ea5e9;
        }

        .pricing-card:hover::before {
            transform: scaleX(1);
        }

        .pricing-card.featured {
            border-color: #10b981;
            position: relative;
        }

        .pricing-card.featured::before {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: scaleX(1);
        }

        .pricing-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pricing-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .pricing-card .description {
            color: #718096;
            margin-bottom: 30px;
            min-height: 48px;
        }

        .price {
            font-size: 3.5rem;
            font-weight: 800;
            color: #0ea5e9;
            margin-bottom: 10px;
        }

        .pricing-card.featured .price {
            color: #10b981;
        }

        .price small {
            font-size: 1.2rem;
            color: #718096;
            font-weight: 400;
        }

        .features {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .features li {
            padding: 12px 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .features li i {
            color: #10b981;
            font-size: 1.2rem;
        }

        .btn-pricing {
            width: 100%;
            padding: 15px;
            font-weight: 600;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-pricing-outline {
            background: transparent;
            border: 2px solid #0ea5e9;
            color: #0ea5e9;
        }

        .btn-pricing-outline:hover {
            background: #0ea5e9;
            color: white;
        }

        .btn-pricing-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
        }

        .btn-pricing-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .faq-section {
            padding: 80px 0;
            background: white;
        }

        .faq-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 50px;
            color: #2d3748;
        }

        .accordion-item {
            border: none;
            margin-bottom: 15px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .accordion-button {
            font-weight: 600;
            font-size: 1.1rem;
            padding: 20px 25px;
            background: white;
        }

        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #0ea5e920 0%, #06b6d420 100%);
            color: #0ea5e9;
        }

        .accordion-body {
            padding: 20px 25px;
            color: #718096;
            line-height: 1.7;
        }

        footer {
            background: #1a202c;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            .hero p {
                font-size: 1.1rem;
            }
            .price {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
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
                        <a class="nav-link" href="/">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dietitians.php">Diyetisyenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/pricing">Fiyatlar</a>
                    </li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $auth->user()->getUserType() === 'admin' ? '/admin/dashboard.php' : ($auth->user()->getUserType() === 'dietitian' ? '/dietitian/dashboard.php' : '/client/dashboard.php') ?>">
                                Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Sağlıklı Yaşam İçin En Uygun Planı Seçin</h1>
            <p>Danışanlar için ücretsiz kayıt. Diyetisyenler kendi fiyatlarını belirler.</p>
        </div>
    </section>

    <!-- Pricing -->
    <section class="pricing-section">
        <div class="container">
            <div class="row g-4">
                <!-- Danışan Ücretsiz -->
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <h3>Danışan</h3>
                        <p class="description">Platform kullanımı tamamen ücretsiz</p>
                        <div class="price">
                            ₺0
                            <small>/ay</small>
                        </div>
                        <ul class="features">
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Ücretsiz kayıt ve profil oluşturma</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Tüm diyetisyenleri görüntüleme</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Randevu alma sistemi</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Kilo ve ilerleme takibi</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Mesajlaşma sistemi</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Diyet planlarınıza erişim</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Video görüşme desteği</span>
                            </li>
                        </ul>
                        <a href="/register-client.php" class="btn btn-pricing btn-pricing-outline">Ücretsiz Başla</a>
                    </div>
                </div>

                <!-- Diyetisyen Standard -->
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <h3>Diyetisyen - Standart</h3>
                        <p class="description">Komisyon bazlı, ön ödeme yok</p>
                        <div class="price">
                            %15
                            <small>komisyon</small>
                        </div>
                        <ul class="features">
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Sınırsız danışan kabul etme</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Kendi fiyatınızı belirleme</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Müsaitlik takvimi yönetimi</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Diyet planı oluşturma araçları</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Video görüşme sistemi</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Ödeme yönetimi</span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Temel analitik raporlar</span>
                            </li>
                        </ul>
                        <a href="/register-dietitian.php" class="btn btn-pricing btn-pricing-outline">Başvur</a>
                    </div>
                </div>

                <!-- Diyetisyen Premium -->
                <div class="col-lg-4">
                    <div class="pricing-card featured">
                        <span class="pricing-badge">Önerilen</span>
                        <h3>Diyetisyen - Premium</h3>
                        <p class="description">Daha düşük komisyon, öne çıkarılma</p>
                        <div class="price">
                            %10
                            <small>komisyon</small>
                        </div>
                        <ul class="features">
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Tüm Standart özellikler</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Ana sayfada öne çıkarılma</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Öncelikli sıralama</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Gelişmiş analitik ve raporlar</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Özel rozet (Premium üye)</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Blog yazısı yayınlama</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span><strong>Öncelikli destek</strong></span>
                            </li>
                        </ul>
                        <a href="/register-dietitian.php" class="btn btn-pricing btn-pricing-primary">Premium'a Başvur</a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    Diyetisyen ödemeleri, danışan seansları tamamladıktan sonra haftalık olarak hesabınıza aktarılır.
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="faq-section">
        <div class="container">
            <h2>Sıkça Sorulan Sorular</h2>
            <div class="accordion" id="pricingFaq">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Danışan olarak platform kullanımı gerçekten ücretsiz mi?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFaq">
                        <div class="accordion-body">
                            Evet, danışanlar için platform kullanımı tamamen ücretsizdir. Sadece diyetisyenle yaptığınız seanslara ait ücretleri ödersiniz.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Diyetisyen olarak fiyatlarımı kendim belirleyebilir miyim?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body">
                            Evet, her diyetisyen kendi seans ücretini belirler. Platform sadece her tamamlanan seanstan komisyon alır.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Ödemeler nasıl yapılıyor?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body">
                            Danışanlar randevu alırken ödemeyi yaparlar. Diyetisyenlere ödemeler, seans tamamlandıktan sonra haftalık periyotlarla banka hesaplarına aktarılır.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Premium üyelik nasıl alınır?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body">
                            Diyetisyen olarak kayıt olduktan sonra, panel ayarlarınızdan Premium üyeliğe geçiş yapabilirsiniz. Premium üyelik aylık abonelik şeklinde çalışır.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            İptal ve iade politikası nedir?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                        <div class="accordion-body">
                            Randevunuzdan 24 saat öncesine kadar ücretsiz iptal edebilirsiniz. 24 saatten sonraki iptallerde iade yapılmaz. Diyetisyen tarafından iptal edilen randevularda tam iade yapılır.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-2"><strong><i class="fas fa-heartbeat me-2"></i>Diyetlenio</strong></p>
            <p class="text-white-50 mb-0">&copy; <?= date('Y') ?> Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
