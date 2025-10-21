<?php
/**
 * Diyetlenio - Çerez Politikası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Çerez Politikası';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="Diyetlenio çerez politikası. Web sitemizde kullanılan çerezler ve amaçları hakkında detaylı bilgi.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
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
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            padding: 80px 0 60px;
            color: white;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .content-section {
            padding: 80px 0;
        }

        .legal-content {
            background: white;
            border-radius: 20px;
            padding: 60px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            max-width: 900px;
            margin: 0 auto;
        }

        .legal-content h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .legal-content h2:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        .legal-content h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4a5568;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .legal-content p {
            color: #718096;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        .legal-content ul, .legal-content ol {
            color: #718096;
            line-height: 1.8;
            margin-bottom: 20px;
            padding-left: 25px;
        }

        .legal-content li {
            margin-bottom: 10px;
        }

        .legal-content strong {
            color: #2d3748;
            font-weight: 600;
        }

        .cookie-table {
            width: 100%;
            margin: 25px 0;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .cookie-table thead {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
        }

        .cookie-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .cookie-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .cookie-table tbody tr:last-child td {
            border-bottom: none;
        }

        .cookie-table tbody tr:hover {
            background: #f8f9fa;
        }

        .highlight-box {
            background: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 20px 25px;
            margin: 25px 0;
            border-radius: 8px;
        }

        .update-date {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 0.95rem;
            color: #718096;
        }

        .cookie-icon {
            font-size: 4rem;
            color: #f97316;
            margin-bottom: 20px;
        }

        footer {
            background: #1a202c;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            .legal-content {
                padding: 30px 25px;
            }
            .cookie-table {
                font-size: 0.9rem;
            }
            .cookie-table th,
            .cookie-table td {
                padding: 10px;
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
                        <a class="nav-link" href="/privacy-policy">Gizlilik</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/cookies">Çerezler</a>
                    </li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $auth->user()->getUserType() === 'admin' ? '/admin/dashboard.php' : ($auth->user()->getUserType() === 'dietitian' ? '/dietitian/dashboard.php' : '/client/dashboard.php') ?>">
                                Panel
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="cookie-icon">
                <i class="fas fa-cookie-bite"></i>
            </div>
            <h1>Çerez Politikası</h1>
            <p>Web sitemizde kullanılan çerezler hakkında bilgilendirme</p>
        </div>
    </section>

    <!-- Content -->
    <section class="content-section">
        <div class="container">
            <div class="legal-content">
                <div class="update-date">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Son Güncelleme: <?= date('d.m.Y') ?>
                </div>

                <h2>1. Çerez Nedir?</h2>
                <p>
                    Çerezler (cookies), bir web sitesini ziyaret ettiğinizde cihazınıza (bilgisayar, tablet, telefon)
                    kaydedilen küçük metin dosyalarıdır. Çerezler, web sitesinin daha verimli çalışmasını sağlar ve
                    size daha iyi bir kullanıcı deneyimi sunar.
                </p>

                <h2>2. Çerezleri Neden Kullanıyoruz?</h2>
                <p>Diyetlenio platformunda çerezleri aşağıdaki amaçlarla kullanıyoruz:</p>
                <ul>
                    <li>Platformun temel işlevlerini sağlamak</li>
                    <li>Oturum güvenliğini korumak</li>
                    <li>Kullanıcı tercihlerinizi hatırlamak</li>
                    <li>Platform performansını analiz etmek ve iyileştirmek</li>
                    <li>Size özel içerik ve öneriler sunmak</li>
                    <li>Platformun nasıl kullanıldığını anlamak</li>
                </ul>

                <h2>3. Kullandığımız Çerez Türleri</h2>

                <h3>3.1. Zorunlu Çerezler</h3>
                <p>
                    Bu çerezler platformun çalışması için gereklidir ve devre dışı bırakılamazlar.
                    Genellikle yalnızca oturum açma, form doldurma gibi eylemlere yanıt olarak ayarlanırlar.
                </p>

                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>Çerez Adı</th>
                            <th>Amaç</th>
                            <th>Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>PHPSESSID</strong></td>
                            <td>Oturum yönetimi, kullanıcı kimlik doğrulama</td>
                            <td>Oturum süresi</td>
                        </tr>
                        <tr>
                            <td><strong>csrf_token</strong></td>
                            <td>Güvenlik, Cross-Site Request Forgery koruması</td>
                            <td>Oturum süresi</td>
                        </tr>
                        <tr>
                            <td><strong>cookie_consent</strong></td>
                            <td>Çerez onayınızın kaydedilmesi</td>
                            <td>1 yıl</td>
                        </tr>
                    </tbody>
                </table>

                <h3>3.2. Fonksiyonel Çerezler</h3>
                <p>
                    Bu çerezler platformun gelişmiş özelliklerini ve kişiselleştirmeyi sağlar.
                    Tercihlerinizi hatırlar ve size daha iyi bir deneyim sunar.
                </p>

                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>Çerez Adı</th>
                            <th>Amaç</th>
                            <th>Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>user_preferences</strong></td>
                            <td>Dil, tema gibi kullanıcı tercihlerini saklar</td>
                            <td>1 yıl</td>
                        </tr>
                        <tr>
                            <td><strong>remember_me</strong></td>
                            <td>"Beni hatırla" özelliği için kullanılır</td>
                            <td>30 gün</td>
                        </tr>
                    </tbody>
                </table>

                <h3>3.3. Performans Çerezleri</h3>
                <p>
                    Bu çerezler platformun nasıl kullanıldığını anlamamıza yardımcı olur.
                    Anonim olarak veri toplar ve platform performansını iyileştirmemizi sağlar.
                </p>

                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>Çerez Adı</th>
                            <th>Amaç</th>
                            <th>Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>_analytics</strong></td>
                            <td>Sayfa görüntüleme ve kullanıcı davranışı analizi</td>
                            <td>2 yıl</td>
                        </tr>
                        <tr>
                            <td><strong>_performance</strong></td>
                            <td>Sayfa yüklenme süreleri ve performans metrikleri</td>
                            <td>1 yıl</td>
                        </tr>
                    </tbody>
                </table>

                <h3>3.4. Hedefleme/Reklam Çerezleri</h3>
                <p>
                    Bu çerezler size ve ilgi alanlarınıza daha uygun içerik sunmak için kullanılır.
                    Aynı reklamın sürekli gösterilmesini önler.
                </p>

                <div class="highlight-box">
                    <strong>Bilgi:</strong> Şu anda platformumuzda üçüncü taraf reklam çerezleri kullanılmamaktadır.
                    Gelecekte kullanılması durumunda onayınız alınacaktır.
                </div>

                <h2>4. Üçüncü Taraf Çerezler</h2>
                <p>
                    Platformumuzda bazı üçüncü taraf hizmet sağlayıcıların çerezleri kullanılmaktadır:
                </p>
                <ul>
                    <li><strong>Google Analytics:</strong> Platform kullanım istatistikleri</li>
                    <li><strong>Ödeme Sağlayıcıları:</strong> Güvenli ödeme işlemleri</li>
                    <li><strong>Video Konferans:</strong> Online görüşme hizmeti</li>
                </ul>

                <h2>5. Çerezleri Nasıl Kontrol Edebilirsiniz?</h2>
                <p>
                    Çerezleri kabul etmek veya reddetmek tamamen size bağlıdır. Aşağıdaki yöntemlerle
                    çerezleri kontrol edebilirsiniz:
                </p>

                <h3>5.1. Tarayıcı Ayarları</h3>
                <p>Çoğu web tarayıcısı çerezleri otomatik olarak kabul eder, ancak ayarlardan değiştirebilirsiniz:</p>
                <ul>
                    <li><strong>Chrome:</strong> Ayarlar > Gizlilik ve güvenlik > Çerezler ve diğer site verileri</li>
                    <li><strong>Firefox:</strong> Ayarlar > Gizlilik ve Güvenlik > Çerezler ve Site Verileri</li>
                    <li><strong>Safari:</strong> Tercihler > Gizlilik > Çerezler ve web sitesi verileri</li>
                    <li><strong>Edge:</strong> Ayarlar > Gizlilik, arama ve hizmetler > Çerezler</li>
                </ul>

                <h3>5.2. Platform Ayarları</h3>
                <p>
                    Platformumuzda oturum açtıktan sonra, profil ayarlarınızdan çerez tercihlerinizi
                    yönetebilirsiniz. Zorunlu çerezler hariç diğer tüm çerezleri devre dışı bırakabilirsiniz.
                </p>

                <div class="highlight-box">
                    <strong>Uyarı:</strong> Çerezleri devre dışı bırakmanız durumunda platformun bazı özellikleri
                    düzgün çalışmayabilir. Özellikle zorunlu çerezlerin devre dışı bırakılması oturum açmanızı
                    engelleyecektir.
                </div>

                <h2>6. Çerez Saklama Süreleri</h2>
                <p>Kullandığımız çerezler saklama sürelerine göre ikiye ayrılır:</p>
                <ul>
                    <li>
                        <strong>Oturum Çerezleri (Session Cookies):</strong>
                        Tarayıcınızı kapattığınızda otomatik olarak silinir
                    </li>
                    <li>
                        <strong>Kalıcı Çerezler (Persistent Cookies):</strong>
                        Belirli bir süre boyunca veya manuel olarak silene kadar cihazınızda kalır
                    </li>
                </ul>

                <h2>7. Güncellemeler</h2>
                <p>
                    Bu Çerez Politikası, hizmetlerimizde veya yasal düzenlemelerde değişiklik olması
                    durumunda güncellenebilir. Önemli değişiklikler için size bildirim gönderilecektir.
                    Düzenli olarak bu sayfayı kontrol etmenizi öneririz.
                </p>

                <h2>8. İletişim</h2>
                <p>
                    Çerez politikamız hakkında sorularınız için bizimle iletişime geçebilirsiniz:
                </p>
                <ul>
                    <li><strong>E-posta:</strong> gizlilik@diyetlenio.com</li>
                    <li><strong>Telefon:</strong> 0850 123 4567</li>
                    <li><strong>Adres:</strong> İstanbul, Türkiye</li>
                </ul>

                <h2>9. KVKK Uyarısı</h2>
                <p>
                    Çerezler aracılığıyla toplanan veriler, 6698 sayılı Kişisel Verilerin Korunması Kanunu
                    kapsamında işlenmektedir. Detaylı bilgi için
                    <a href="/kvkk">KVKK Aydınlatma Metni</a>'mizi inceleyebilirsiniz.
                </p>

                <div class="highlight-box">
                    <p class="mb-2"><strong>Kişisel Verilerinizin Güvenliği</strong></p>
                    <p class="mb-0">
                        Çerezler aracılığıyla toplanan tüm veriler güvenli sunucularda saklanır ve
                        yetkisiz erişime karşı korunur. Hassas bilgileriniz (şifreler, ödeme bilgileri)
                        asla çerezlerde saklanmaz.
                    </p>
                </div>

                <h2>10. Onay ve Kabul</h2>
                <p>
                    Platformumuzu kullanmaya devam ederek bu Çerez Politikası'nı kabul etmiş sayılırsınız.
                    İlk ziyaretinizde çerez onay banner'ı üzerinden tercihlerinizi belirtebilirsiniz.
                </p>
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
