<?php
/**
 * Diyetlenio - Kullanım Şartları
 */

require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Kullanım Şartları';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #0ea5e9 !important; }
        .content { background: white; border-radius: 20px; padding: 50px; margin: 50px 0; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { color: #2d3748; font-weight: 700; margin-bottom: 30px; }
        h2 { color: #2d3748; font-weight: 600; margin-top: 40px; margin-bottom: 20px; font-size: 1.5rem; }
        p { color: #718096; line-height: 1.8; margin-bottom: 15px; }
        ul { color: #718096; line-height: 1.8; }
        .footer { background: #1e293b; color: white; padding: 40px 0; text-align: center; }
        .footer a { color: #0ea5e9; text-decoration: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <div class="ms-auto">
                <a href="/" class="btn btn-outline-primary me-2">Ana Sayfa</a>
                <a href="/login.php" class="btn btn-primary">Giriş Yap</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content">
            <h1>Kullanım Şartları</h1>
            <p><strong>Son Güncelleme: 21 Ekim 2024</strong></p>

            <p>Diyetlenio platformunu kullanarak aşağıdaki şartları kabul etmiş olursunuz. Lütfen dikkatlice okuyun.</p>

            <h2>1. Hizmet Tanımı</h2>
            <p>Diyetlenio, lisanslı diyetisyenler ile danışanları buluşturan online bir platformdur. Platform aracılığıyla:</p>
            <ul>
                <li>Diyetisyen arama ve randevu alma</li>
                <li>Online video görüşme</li>
                <li>Diyet programı takibi</li>
                <li>Mesajlaşma ve dosya paylaşımı</li>
            </ul>
            <p>hizmetleri sunulmaktadır.</p>

            <h2>2. Kullanıcı Yükümlülükleri</h2>
            <p>Platform kullanıcıları olarak:</p>
            <ul>
                <li>Doğru ve güncel bilgi vermekle yükümlüsünüz</li>
                <li>Hesap güvenliğinden sorumlusunuz</li>
                <li>Yasalara ve ahlak kurallarına uygun davranmalısınız</li>
                <li>Platformu kötüye kullanmamalısınız</li>
                <li>Diyetisyenlerle saygılı iletişim kurmalısınız</li>
            </ul>

            <h2>3. Diyetisyen Yükümlülükleri</h2>
            <p>Platformdaki diyetisyenler:</p>
            <ul>
                <li>Geçerli diyetisyenlik diplomasına sahip olmalıdır</li>
                <li>Profesyonel etik kurallara uymalıdır</li>
                <li>Danışanlara kaliteli hizmet sunmalıdır</li>
                <li>Kişisel sağlık bilgilerini gizli tutmalıdır</li>
                <li>Randevularına zamanında katılmalıdır</li>
            </ul>

            <h2>4. Ödeme ve İade Politikası</h2>
            <p><strong>Ödeme:</strong></p>
            <ul>
                <li>Randevu öncesi ödeme yapılır</li>
                <li>Kredi kartı ve banka kartı kabul edilir</li>
                <li>Fiyatlar diyetisyen tarafından belirlenir</li>
            </ul>
            <p><strong>İade:</strong></p>
            <ul>
                <li>Randevudan 24 saat öncesine kadar ücretsiz iptal</li>
                <li>24 saatten az iptal durumunda %50 kesinti</li>
                <li>Randevu saatinden sonra iptal kabul edilmez</li>
                <li>Diyetisyen iptali durumunda tam iade</li>
            </ul>

            <h2>5. Sorumluluk Sınırlamaları</h2>
            <p>Diyetlenio platformu:</p>
            <ul>
                <li>Diyetisyen-danışan ilişkisinde aracıdır</li>
                <li>Verilen diyetisyenlik hizmetinden sorumlu değildir</li>
                <li>Sağlık sonuçlarını garanti etmez</li>
                <li>Teknik aksaklıklardan kaynaklanan kayıplardan sorumlu değildir</li>
            </ul>

            <h2>6. Fikri Mülkiyet Hakları</h2>
            <p>Platform içeriği (logo, tasarım, yazılım) Diyetlenio'ya aittir. İzinsiz kullanımı yasaktır.</p>

            <h2>7. Gizlilik</h2>
            <p>Kişisel verileriniz <a href="/privacy-policy.php">Gizlilik Politikası</a>'na uygun işlenir.</p>

            <h2>8. Hesap Askıya Alma ve Kapatma</h2>
            <p>Diyetlenio, şu durumlarda hesabı askıya alabilir veya kapatabilir:</p>
            <ul>
                <li>Kullanım şartlarının ihlali</li>
                <li>Yasadışı faaliyetler</li>
                <li>Diğer kullanıcılara zarar verme</li>
                <li>Sahte bilgi kullanımı</li>
            </ul>

            <h2>9. Değişiklikler</h2>
            <p>Diyetlenio, kullanım şartlarını dilediği zaman değiştirme hakkını saklı tutar. Değişiklikler yayınlandığında yürürlüğe girer.</p>

            <h2>10. Uyuşmazlık Çözümü</h2>
            <p>Bu sözleşmeden kaynaklanan uyuşmazlıklar İstanbul Mahkemeleri ve İcra Daireleri yetkisindedir.</p>

            <h2>11. İletişim</h2>
            <p>Sorularınız için:</p>
            <ul>
                <li><strong>E-posta:</strong> legal@diyetlenio.com</li>
                <li><strong>Telefon:</strong> +90 (212) 123 45 67</li>
                <li><strong>Adres:</strong> Maslak Mahallesi, Büyükdere Cad. No:123, Sarıyer, İstanbul</li>
            </ul>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
            <div class="mt-3">
                <a href="/about.php" class="me-3">Hakkımızda</a>
                <a href="/contact.php" class="me-3">İletişim</a>
                <a href="/privacy-policy.php">Gizlilik Politikası</a>
            </div>
        </div>
    </footer>
</body>
</html>
