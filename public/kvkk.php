<?php
/**
 * Diyetlenio - KVKK Aydınlatma Metni
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'KVKK Aydınlatma Metni';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="Diyetlenio KVKK Aydınlatma Metni. Kişisel verilerinizin nasıl işlendiği ve korunduğu hakkında bilgi.">
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
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
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

        .highlight-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
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
                        <a class="nav-link active" href="/kvkk">KVKK</a>
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
            <h1>KVKK Aydınlatma Metni</h1>
            <p>6698 Sayılı Kişisel Verilerin Korunması Kanunu Kapsamında Bilgilendirme</p>
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

                <h2>1. Veri Sorumlusu</h2>
                <p>
                    6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") uyarınca, kişisel verileriniz;
                    veri sorumlusu olarak <strong>Diyetlenio</strong> tarafından aşağıda açıklanan kapsamda
                    işlenebilecektir.
                </p>

                <div class="highlight-box">
                    <strong>İletişim Bilgilerimiz:</strong><br>
                    E-posta: kvkk@diyetlenio.com<br>
                    Adres: İstanbul, Türkiye<br>
                    Telefon: 0850 123 4567
                </div>

                <h2>2. Kişisel Verilerin İşlenme Amaçları</h2>
                <p>Toplanan kişisel verileriniz aşağıdaki amaçlarla işlenmektedir:</p>
                <ul>
                    <li>Platformun sunduğu hizmetlerin sağlanması ve yönetilmesi</li>
                    <li>Kullanıcı hesabınızın oluşturulması ve yönetimi</li>
                    <li>Diyetisyen-danışan eşleştirme hizmetinin sunulması</li>
                    <li>Online randevu ve video görüşme hizmetlerinin sağlanması</li>
                    <li>Ödeme işlemlerinin gerçekleştirilmesi</li>
                    <li>Müşteri destek hizmetlerinin sunulması</li>
                    <li>Yasal yükümlülüklerin yerine getirilmesi</li>
                    <li>Platform güvenliğinin sağlanması</li>
                    <li>İstatistiksel analiz ve raporlama</li>
                    <li>Kullanıcı deneyiminin iyileştirilmesi</li>
                </ul>

                <h2>3. İşlenen Kişisel Veriler</h2>

                <h3>3.1. Kimlik Bilgileri</h3>
                <ul>
                    <li>Ad, soyad</li>
                    <li>T.C. Kimlik numarası (diyetisyenler için)</li>
                    <li>Doğum tarihi</li>
                </ul>

                <h3>3.2. İletişim Bilgileri</h3>
                <ul>
                    <li>E-posta adresi</li>
                    <li>Telefon numarası</li>
                    <li>Adres bilgisi</li>
                </ul>

                <h3>3.3. Sağlık Verileri (Özel Nitelikli Kişisel Veri)</h3>
                <p><strong>Dikkat:</strong> Sağlık verileriniz KVKK Madde 6 kapsamında özel nitelikli kişisel veridir ve açık rızanız ile işlenmektedir.</p>
                <ul>
                    <li>Kilo, boy, vücut kitle indeksi</li>
                    <li>Sağlık durumu ve hastalık geçmişi</li>
                    <li>Beslenme alışkanlıkları</li>
                    <li>Alerjiler ve intoleranslar</li>
                    <li>Kullanılan ilaçlar</li>
                    <li>Diyet planları ve takip verileri</li>
                </ul>

                <h3>3.4. Finansal Bilgiler</h3>
                <ul>
                    <li>Ödeme bilgileri (şifreli olarak saklanır)</li>
                    <li>Fatura bilgileri</li>
                    <li>IBAN numarası (diyetisyenler için)</li>
                </ul>

                <h3>3.5. İşlem Güvenliği Bilgileri</h3>
                <ul>
                    <li>IP adresi</li>
                    <li>Çerez kayıtları</li>
                    <li>Cihaz bilgileri</li>
                    <li>Log kayıtları</li>
                </ul>

                <h2>4. Kişisel Verilerin Toplanma Yöntemi</h2>
                <p>Kişisel verileriniz aşağıdaki yöntemlerle toplanmaktadır:</p>
                <ul>
                    <li>Web sitesi kayıt formları</li>
                    <li>Mobil uygulama</li>
                    <li>E-posta ve telefon iletişimleri</li>
                    <li>Çerezler ve benzer teknolojiler</li>
                    <li>Video görüşme platformu (kayıt yapılmaz)</li>
                    <li>Ödeme sistemleri</li>
                </ul>

                <h2>5. Kişisel Verilerin Aktarılması</h2>
                <p>Kişisel verileriniz aşağıdaki durumlarda üçüncü kişilere aktarılabilir:</p>
                <ul>
                    <li><strong>Diyetisyenlere:</strong> Hizmet alabilmeniz için eşleştiğiniz diyetisyenle gerekli bilgileriniz paylaşılır</li>
                    <li><strong>Ödeme Kuruluşları:</strong> Ödeme işlemlerinin gerçekleştirilmesi için</li>
                    <li><strong>Teknoloji Hizmet Sağlayıcıları:</strong> Altyapı ve hosting hizmetleri için</li>
                    <li><strong>Yasal Yükümlülükler:</strong> Mahkeme kararı, kanuni düzenleme gereği</li>
                </ul>

                <div class="highlight-box">
                    <strong>Önemli:</strong> Sağlık verileriniz sadece tedavi ve takip amacıyla, açık rızanız
                    doğrultusunda eşleştiğiniz diyetisyenle paylaşılır. Başka hiçbir amaçla üçüncü kişilere
                    aktarılmaz.
                </div>

                <h2>6. Kişisel Verilerin Saklanma Süresi</h2>
                <p>
                    Kişisel verileriniz, işleme amacının gerektirdiği süre boyunca ve yasal saklama
                    yükümlülükleri çerçevesinde saklanır:
                </p>
                <ul>
                    <li>Hesap verileri: Hesap aktif olduğu sürece + 5 yıl</li>
                    <li>Sağlık verileri: KVKK ve ilgili mevzuat gereği 15 yıl</li>
                    <li>Finansal kayıtlar: Vergi mevzuatı gereği 10 yıl</li>
                    <li>Log kayıtları: 2 yıl</li>
                </ul>

                <h2>7. KVKK Kapsamındaki Haklarınız</h2>
                <p>KVKK'nın 11. maddesi uyarınca aşağıdaki haklara sahipsiniz:</p>
                <ol>
                    <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
                    <li>İşlenmişse buna ilişkin bilgi talep etme</li>
                    <li>İşlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme</li>
                    <li>Yurt içinde veya yurt dışında aktarıldığı 3. kişileri bilme</li>
                    <li>Eksik veya yanlış işlenmiş olması halinde düzeltilmesini isteme</li>
                    <li>KVKK'nın 7. maddesinde öngörülen şartlar çerçevesinde silinmesini veya yok edilmesini isteme</li>
                    <li>Düzeltme, silme ve yok edilme işlemlerinin kişisel verilerin aktarıldığı 3. kişilere bildirilmesini isteme</li>
                    <li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme</li>
                    <li>KVKK'ya aykırı olarak işlenmesi sebebiyle zarara uğramanız halinde zararın giderilmesini talep etme</li>
                </ol>

                <h2>8. Haklarınızı Kullanma</h2>
                <p>Yukarıda belirtilen haklarınızı kullanmak için:</p>
                <ul>
                    <li>E-posta: kvkk@diyetlenio.com</li>
                    <li>Başvuru Formu: Web sitemizdeki KVKK başvuru formunu doldurabilirsiniz</li>
                    <li>Kimlik tespitinin yapılabilmesi için başvurunuzda kimlik fotokopinizi eklemelisiniz</li>
                </ul>

                <p>
                    Başvurularınız en geç 30 gün içinde ücretsiz olarak sonuçlandırılacaktır.
                    İşlemin ayrıca bir maliyeti gerektirmesi durumunda Kişisel Verileri Koruma Kurulu
                    tarafından belirlenen tarifedeki ücret alınabilir.
                </p>

                <h2>9. Güvenlik Önlemleri</h2>
                <p>Kişisel verilerinizin güvenliği için aldığımız önlemler:</p>
                <ul>
                    <li>256-bit SSL şifreleme</li>
                    <li>ISO 27001 sertifikalı veri merkezleri</li>
                    <li>Düzenli güvenlik taramaları</li>
                    <li>Erişim kontrolü ve yetkilendirme</li>
                    <li>Düzenli yedekleme</li>
                    <li>Personel gizlilik sözleşmeleri</li>
                </ul>

                <h2>10. Çerezler</h2>
                <p>
                    Platformumuzda kullanıcı deneyimini iyileştirmek için çerezler kullanılmaktadır.
                    Detaylı bilgi için <a href="/cookies">Çerez Politikası</a> sayfamızı inceleyebilirsiniz.
                </p>

                <h2>11. Değişiklikler</h2>
                <p>
                    Bu aydınlatma metni yasal düzenlemeler ve hizmetlerimizdeki değişiklikler doğrultusunda
                    güncellenebilir. Önemli değişiklikler için e-posta ile bilgilendirileceksiniz.
                </p>

                <h2>12. İletişim</h2>
                <p>
                    KVKK ile ilgili sorularınız için:<br>
                    <strong>E-posta:</strong> kvkk@diyetlenio.com<br>
                    <strong>Telefon:</strong> 0850 123 4567
                </p>

                <div class="highlight-box">
                    <p class="mb-0">
                        <strong>Kişisel Verileri Koruma Kurumu:</strong><br>
                        Şikayetlerinizi <a href="https://www.kvkk.gov.tr" target="_blank">www.kvkk.gov.tr</a>
                        adresinden Kuruma iletebilirsiniz.
                    </p>
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
