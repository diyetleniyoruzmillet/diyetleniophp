<?php
/**
 * Diyetlenio - Hakkımızda Sayfası
 * Single footer only - uses footer.php partial
 * Last updated: 2025-11-04 - Force cache clear
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Hakkımızda';
ob_start();
?>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            overflow-x: hidden;
        }

        /* Scroll Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .slide-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-right {
            opacity: 0;
            transform: translateX(50px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        .slide-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

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
            font-size: 1.5rem;
            font-weight: 700;
            color: #56ab2f !important;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            padding: 120px 0 100px;
            text-align: center;
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
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.2;
        }

        .hero .lead {
            font-size: 1.4rem;
            max-width: 800px;
            margin: 0 auto 20px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .hero .subtitle {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.85;
        }

        /* Mission & Vision Cards */
        .mission-vision-section {
            padding: 100px 0;
            background: white;
        }

        .mission-vision-card {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 2px solid #e2e8f0;
            border-radius: 24px;
            padding: 50px 40px;
            height: 100%;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .mission-vision-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #56ab2f, #a8e063);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .mission-vision-card:hover::before {
            transform: scaleX(1);
        }

        .mission-vision-card:hover {
            transform: translateY(-10px);
            border-color: #56ab2f;
            box-shadow: 0 20px 40px rgba(86, 171, 47, 0.15);
        }

        .mission-vision-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(86, 171, 47, 0.3);
        }

        .mission-vision-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .mission-vision-card p {
            color: #4a5568;
            font-size: 1.05rem;
            line-height: 1.8;
        }

        /* Values Section */
        .values-section {
            padding: 100px 0;
            background: #f8fafc;
        }

        .section {
            padding: 100px 0;
        }

        .section-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 16px;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.15rem;
            color: #718096;
            text-align: center;
            max-width: 800px;
            margin: 0 auto 70px;
            line-height: 1.7;
        }

        .value-card {
            background: white;
            border-radius: 24px;
            padding: 45px 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .value-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: #56ab2f;
        }

        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(86, 171, 47, 0.1) 0%, rgba(168, 224, 99, 0.1) 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #56ab2f;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .value-card:hover .value-icon {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            transform: rotate(5deg) scale(1.1);
        }

        .value-card h4 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
        }

        .value-card p {
            color: #718096;
            line-height: 1.8;
            font-size: 1rem;
        }

        /* Timeline Section */
        .timeline-section {
            padding: 100px 0;
            background: white;
        }

        .timeline {
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #0ea5e9, #06b6d4);
            border-radius: 2px;
        }

        .timeline-item {
            margin-bottom: 60px;
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
            margin-right: 0;
        }

        .timeline-content {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 35px;
            width: calc(50% - 40px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .timeline-content:hover {
            transform: scale(1.05);
            border-color: #0ea5e9;
            box-shadow: 0 8px 30px rgba(14, 165, 233, 0.2);
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 20px;
            transform: translateX(-50%);
            width: 24px;
            height: 24px;
            background: white;
            border: 4px solid #0ea5e9;
            border-radius: 50%;
            z-index: 1;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.2);
        }

        .timeline-year {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0ea5e9;
            margin-bottom: 12px;
        }

        .timeline-content h4 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .timeline-content p {
            color: #718096;
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .timeline::before {
                left: 20px;
            }

            .timeline-dot {
                left: 20px;
            }

            .timeline-content {
                width: calc(100% - 60px);
                margin-left: 60px !important;
            }
        }

        /* Stats Section */
        .stats {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 60%),
                radial-gradient(circle at 70% 80%, rgba(255,255,255,0.1) 0%, transparent 60%);
        }

        .stat-item {
            text-align: center;
            padding: 30px 20px;
            position: relative;
            z-index: 1;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 12px;
            line-height: 1;
        }

        .stat-label {
            font-size: 1.2rem;
            opacity: 0.95;
            font-weight: 500;
        }

        /* Testimonials Section */
        .testimonials-section {
            padding: 100px 0;
            background: #f8fafc;
        }

        .testimonial-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }

        .testimonial-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
            border-color: #0ea5e9;
        }

        .testimonial-quote {
            font-size: 3rem;
            color: #0ea5e9;
            line-height: 1;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .testimonial-text {
            font-size: 1.05rem;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 24px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .testimonial-info h5 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .testimonial-info p {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }

        .stars {
            color: #fbbf24;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        /* Trust Badges */
        .trust-badges {
            padding: 80px 0;
            background: white;
        }

        .badge-item {
            text-align: center;
            padding: 30px 20px;
            transition: all 0.3s ease;
        }

        .badge-item:hover {
            transform: scale(1.05);
        }

        .badge-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            color: #0ea5e9;
        }

        .badge-item h5 {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .badge-item p {
            color: #718096;
            font-size: 0.95rem;
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            text-align: center;
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
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-section h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.3rem;
            max-width: 700px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 18px 50px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(14, 165, 233, 0.4);
            color: white;
        }

        .btn-cta-secondary {
            background: white;
            color: #0ea5e9;
            padding: 18px 50px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid white;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cta-secondary:hover {
            background: transparent;
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero .lead {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .cta-section h2 {
                font-size: 2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-cta-primary, .btn-cta-secondary {
                width: 100%;
                max-width: 300px;
            }
        }
</style>
<?php
$extraHead = ob_get_clean();
include __DIR__ . '/../includes/partials/header.php';
?>

<!-- Hero -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Sağlıklı Yaşam Yolculuğunuzda Yanınızdayız</h1>
            <p class="lead">Profesyonel diyetisyen desteğini herkes için erişilebilir, kolay ve etkili kılıyoruz</p>
            <p class="subtitle">Modern teknoloji ile geleneksel diyetisyenlik hizmetlerini birleştirerek, her yerden, her zaman kaliteli danışmanlık sunuyoruz</p>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="mission-vision-section">
        <div class="container">
            <div class="row g-4 fade-in">
                <div class="col-lg-6">
                    <div class="mission-vision-card">
                        <div class="mission-vision-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Misyonumuz</h3>
                        <p>
                            İnsanların sağlıklı yaşam hedeflerine ulaşmaları için profesyonel diyetisyen
                            desteğini kolay, erişilebilir ve etkili bir şekilde sunmak. Modern teknoloji
                            ile geleneksel diyetisyenlik hizmetlerini birleştirerek, her yerden, her zaman
                            kaliteli danışmanlık hizmeti sunuyoruz. Sağlıklı beslenmeyi yaşam tarzı haline
                            getirmek ve bu değişimi sürdürülebilir kılmak temel odağımızdır.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mission-vision-card">
                        <div class="mission-vision-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3>Vizyonumuz</h3>
                        <p>
                            Türkiye'nin en güvenilir ve tercih edilen online diyetisyen platformu olmak.
                            Milyonlarca insanın sağlıklı yaşam yolculuğuna rehberlik etmek ve beslenme
                            danışmanlığında dijital dönüşümün öncüsü olmak. Sağlıklı yaşamı herkes için
                            ulaşılabilir kılarak, toplumun genel sağlık düzeyine katkıda bulunmayı hedefliyoruz.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <h2 class="section-title fade-in">Değerlerimiz</h2>
            <p class="section-subtitle fade-in">
                Çalışmalarımızı şekillendiren ve her gün rehberlik eden temel değerlerimiz
            </p>

            <div class="row g-4">
                <div class="col-md-4 fade-in">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Güvenilirlik</h4>
                        <p>
                            Sadece lisanslı ve deneyimli diyetisyenlerle çalışıyoruz. Her danışanımızın
                            verilerini koruyuyor ve güvenli bir ortam sağlıyoruz.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Kalite</h4>
                        <p>
                            Profesyonellik ve yüksek hizmet kalitesi standardımızdır. Her danışanımıza
                            özel, kişiselleştirilmiş beslenme planları sunuyoruz.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4>Yenilikçilik</h4>
                        <p>
                            Teknolojik gelişmeleri takip ediyor, hizmetlerimizi sürekli geliştiriyor ve
                            danışanlarımıza en iyi deneyimi sunmak için yenilik yapıyoruz.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Empati</h4>
                        <p>
                            Her danışanımızın benzersiz olduğunu biliyor, ihtiyaçlarını anlıyor ve
                            kişisel hedeflerine saygı gösteriyoruz.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>Şeffaflık</h4>
                        <p>
                            Açık ve dürüst iletişim kuruyor, süreçlerimizi şeffaf bir şekilde yönetiyor
                            ve danışanlarımızla güven ilişkisi oluşturuyoruz.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 fade-in">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Topluluk</h4>
                        <p>
                            Sağlıklı yaşam için motive eden, destekleyen ve birlikte büyüyen bir
                            topluluk oluşturuyoruz.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section">
        <div class="container">
            <h2 class="section-title fade-in">Yolculuğumuz</h2>
            <p class="section-subtitle fade-in">
                Diyetlenio'nun gelişim sürecinde önemli dönüm noktaları
            </p>

            <div class="timeline">
                <div class="timeline-item fade-in">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2023</div>
                        <h4>Platform Lansmanı</h4>
                        <p>
                            Diyetlenio resmi olarak hayata geçirildi. İlk diyetisyenlerimiz ve
                            danışanlarımızla birlikte sağlıklı yaşam yolculuğuna başladık.
                        </p>
                    </div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2023 Q2</div>
                        <h4>İlk 1000 Danışan</h4>
                        <p>
                            Kısa sürede 1000+ mutlu danışana ulaştık ve platformumuza olan güveni
                            teyit ettik. İlk başarı hikayelerimiz gelmeye başladı.
                        </p>
                    </div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2023 Q3</div>
                        <h4>Mobil Uygulama</h4>
                        <p>
                            Mobil uygulamamızı piyasaya sürdük. Danışanlarımız artık istedikleri
                            yerden hizmet alabiliyorlar.
                        </p>
                    </div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2024</div>
                        <h4>Yeni Özellikler</h4>
                        <p>
                            Video görüşme, beslenme takibi, tarif önerileri ve blog içerikleriyle
                            platformumuzu zenginleştirdik.
                        </p>
                    </div>
                </div>

                <div class="timeline-item fade-in">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2024 Q4</div>
                        <h4>50+ Uzman Diyetisyen</h4>
                        <p>
                            Türkiye'nin farklı şehirlerinden 50'den fazla uzman diyetisyenle
                            ailemizi büyüttük. Her alanda uzman kadromuz var.
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
                <div class="col-md-3 col-6 fade-in">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number" data-target="1000">0</div>
                        <div class="stat-label">Mutlu Danışan</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 fade-in">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fas fa-user-md"></i></div>
                        <div class="stat-number" data-target="50">0</div>
                        <div class="stat-label">Uzman Diyetisyen</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 fade-in">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-number" data-target="5000">0</div>
                        <div class="stat-label">Tamamlanan Randevu</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 fade-in">
                    <div class="stat-item">
                        <div class="stat-icon"><i class="fas fa-smile"></i></div>
                        <div class="stat-number" data-target="98">0</div>
                        <div class="stat-label">Memnuniyet Oranı</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials-section">
        <div class="container">
            <h2 class="section-title fade-in">Danışanlarımız Ne Diyor?</h2>
            <p class="section-subtitle fade-in">
                Binlerce mutlu danışanımızdan gelen gerçek deneyimler
            </p>

            <div class="row g-4">
                <div class="col-lg-4 fade-in">
                    <div class="testimonial-card">
                        <div class="testimonial-quote">"</div>
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">
                            Diyetlenio sayesinde 3 ayda 12 kilo verdim! Diyetisyeninizle online görüşmek
                            çok pratik. Her an ulaşabiliyorum ve sorularıma hızlı cevap alıyorum.
                        </p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">AY</div>
                            <div class="testimonial-info">
                                <h5>Ayşe Y.</h5>
                                <p>İstanbul</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 fade-in">
                    <div class="testimonial-card">
                        <div class="testimonial-quote">"</div>
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">
                            Yıllardır diyete başlayamıyordum. Diyetlenio'daki uzman diyetisyenim sayesinde
                            artık sağlıklı besleniyorum ve kendimi çok daha iyi hissediyorum!
                        </p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">MK</div>
                            <div class="testimonial-info">
                                <h5>Mehmet K.</h5>
                                <p>Ankara</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 fade-in">
                    <div class="testimonial-card">
                        <div class="testimonial-quote">"</div>
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">
                            Hamilelik dönemimde beslenme konusunda çok endişeliydim. Diyetlenio'daki
                            uzmanım her adımda yanımda oldu. Teşekkürler!
                        </p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">ZT</div>
                            <div class="testimonial-info">
                                <h5>Zeynep T.</h5>
                                <p>İzmir</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Badges -->
    <section class="trust-badges">
        <div class="container">
            <h2 class="section-title fade-in">Neden Diyetlenio?</h2>
            <p class="section-subtitle fade-in">
                Güvenilir, profesyonel ve sonuç odaklı hizmet anlayışımız
            </p>

            <div class="row">
                <div class="col-lg-3 col-md-6 fade-in">
                    <div class="badge-item">
                        <div class="badge-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h5>Lisanslı Uzmanlar</h5>
                        <p>Tüm diyetisyenlerimiz lisanslı ve deneyimli</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 fade-in">
                    <div class="badge-item">
                        <div class="badge-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h5>Güvenli Platform</h5>
                        <p>Verileriniz şifrelenmiş ve güvende</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 fade-in">
                    <div class="badge-item">
                        <div class="badge-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h5>7/24 Destek</h5>
                        <p>Her zaman yanınızdayız</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 fade-in">
                    <div class="badge-item">
                        <div class="badge-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h5>Uygun Fiyatlar</h5>
                        <p>Herkes için erişilebilir paketler</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container cta-content fade-in">
            <h2>Sağlıklı Yaşama Bugün Başlayın!</h2>
            <p>Uzman diyetisyenlerimizle tanışın ve hedefinize ulaşmanız için kişiselleştirilmiş plan alın</p>
            <div class="cta-buttons">
                <a href="/register.php" class="btn-cta-primary">Hemen Başla</a>
                <a href="/dietitians.php" class="btn-cta-secondary">Diyetisyenleri Keşfet</a>
            </div>
        </div>
    </section>


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

        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all elements with animation classes
        document.querySelectorAll('.fade-in, .slide-left, .slide-right').forEach(el => {
            observer.observe(el);
        });

        // Counter animation for stats
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    element.textContent = Math.floor(current) + (target >= 1000 ? '+' : target === 98 ? '%' : '');
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target + (target >= 1000 ? '+' : target === 98 ? '%' : '');
                }
            };

            updateCounter();
        }

        // Trigger counter animation when stats section is visible
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-number');
                    counters.forEach(counter => {
                        const target = parseInt(counter.getAttribute('data-target'));
                        animateCounter(counter, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats');
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
    </script>
<!-- Deploy timestamp: 2025-11-04 16:20 - Single footer only -->
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
