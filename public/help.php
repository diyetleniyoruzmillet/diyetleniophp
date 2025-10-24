<?php
/**
 * Diyetlenio - Yardım Merkezi
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Yardım Merkezi';
$metaDescription = 'Diyetlenio kullanımı hakkında yardım ve destek. Sıkça sorulan sorular, rehberler ve daha fazlası.';
include __DIR__ . '/../includes/partials/header.php';
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
            margin: 0 auto 40px;
        }

        .search-box {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-box input {
            padding: 18px 60px 18px 25px;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            outline: none;
        }

        .search-box button {
            position: absolute;
            right: 8px;
            top: 8px;
            padding: 10px 25px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 600;
        }

        .categories-section {
            padding: 60px 0;
        }

        .category-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }

        .category-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .category-card p {
            color: #718096;
            margin-bottom: 0;
        }

        .contact-section {
            padding: 80px 0;
            background: white;
        }

        .contact-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .contact-section .subtitle {
            text-align: center;
            color: #718096;
            font-size: 1.2rem;
            margin-bottom: 50px;
        }

        .contact-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }

        .contact-card:hover {
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-card i {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .contact-card h4 {
            font-weight: 700;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .contact-card p {
            color: #718096;
            margin-bottom: 15px;
        }

        .contact-card a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
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
        }
    </style>
</head>
<body>
    <!-- Navbar (kaldırıldı, partial kullanılıyor) -->
    
    

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Nasıl Yardımcı Olabiliriz?</h1>
            <p>Size yardımcı olmak için buradayız. Aradığınızı bulmak için aşağıdaki kategorilere göz atın.</p>
            <div class="search-box">
                <input type="text" placeholder="Bir şey arayın..." id="helpSearch">
                <button type="button"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="categories-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #0ea5e920 0%, #06b6d420 100%); color: #0ea5e9;">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3>Başlangıç</h3>
                        <p>Platform kullanımına başlamak için gerekli bilgiler</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#appointments'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #10b98120 0%, #05966920 100%); color: #10b981;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>Randevular</h3>
                        <p>Randevu alma, iptal etme ve yönetme</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#payments'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #f9731620 0%, #ea580c20 100%); color: #f97316;">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3>Ödemeler</h3>
                        <p>Ödeme yöntemleri ve fatura işlemleri</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#account'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #ec489920 0%, #d9465120 100%); color: #ec4899;">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3>Hesap</h3>
                        <p>Profil ayarları ve güvenlik</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#dietitians'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #6366f120 0%, #818cf820 100%); color: #6366f1;">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h3>Diyetisyenler İçin</h3>
                        <p>Diyetisyen hesabı ve işlemler</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#video'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #3b82f620 0%, #2563eb20 100%); color: #3b82f6;">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3>Video Görüşme</h3>
                        <p>Online seans ve teknik destek</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#diet'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #10b98120 0%, #05966920 100%); color: #059669;">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>Diyet Planları</h3>
                        <p>Programlarınıza erişim ve takip</p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="category-card" onclick="window.location.href='/faq#privacy'">
                        <div class="category-icon" style="background: linear-gradient(135deg, #ef444420 0%, #dc262620 100%); color: #ef4444;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Gizlilik & Güvenlik</h3>
                        <p>Verilerinizin korunması</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section class="contact-section">
        <div class="container">
            <h2>Hala Yardıma İhtiyacınız Var mı?</h2>
            <p class="subtitle">Ekibimiz size yardımcı olmak için burada</p>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="contact-card">
                        <i class="fas fa-envelope"></i>
                        <h4>E-posta Desteği</h4>
                        <p>7/24 size yardımcı olmaya hazırız</p>
                        <a href="mailto:destek@diyetlenio.com">destek@diyetlenio.com</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="contact-card">
                        <i class="fas fa-phone"></i>
                        <h4>Telefon Desteği</h4>
                        <p>Hafta içi 09:00 - 18:00</p>
                        <a href="tel:08501234567">0850 123 4567</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="contact-card">
                        <i class="fas fa-comments"></i>
                        <h4>Canlı Destek</h4>
                        <p>Anında yardım alın</p>
                        <a href="/contact">Sohbet Başlat</a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted">
                    <i class="fas fa-clock me-2"></i>
                    Ortalama yanıt süresi: 2 saat
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
    <script>
        // Simple search functionality
        document.getElementById('helpSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.toLowerCase();
                if (query) {
                    window.location.href = '/faq?search=' + encodeURIComponent(query);
                }
            }
        });
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
