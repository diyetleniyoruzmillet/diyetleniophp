<?php
/**
 * Diyetlenio - Gizlilik Politikası
 */

require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Gizlilik Politikası';
include __DIR__ . '/../includes/partials/header.php';
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
    

    <div class="container">
        <div class="content">
            <h1>Gizlilik Politikası</h1>
            <p><strong>Son Güncelleme: 21 Ekim 2024</strong></p>

            <p>Diyetlenio olarak, kullanıcılarımızın gizliliğine önem veriyoruz. Bu gizlilik politikası, kişisel verilerinizin nasıl toplandığını, kullanıldığını ve korunduğunu açıklamaktadır.</p>

            <h2>1. Toplanan Bilgiler</h2>
            <p>Hizmetlerimizi kullanırken aşağıdaki bilgileri toplayabiliriz:</p>
            <ul>
                <li><strong>Kişisel Bilgiler:</strong> Ad, soyad, e-posta adresi, telefon numarası</li>
                <li><strong>Sağlık Bilgileri:</strong> Kilo, boy, yaş, sağlık durumu, diyet hedefleri</li>
                <li><strong>Ödeme Bilgileri:</strong> Kredi kartı bilgileri (güvenli ödeme sistemleri aracılığıyla)</li>
                <li><strong>Kullanım Bilgileri:</strong> IP adresi, tarayıcı türü, cihaz bilgileri</li>
            </ul>

            <h2>2. Bilgilerin Kullanımı</h2>
            <p>Topladığımız bilgiler şu amaçlarla kullanılır:</p>
            <ul>
                <li>Hizmetlerimizi sunmak ve geliştirmek</li>
                <li>Diyetisyen-danışan eşleşmesi yapmak</li>
                <li>Randevu ve ödeme işlemlerini gerçekleştirmek</li>
                <li>Müşteri desteği sağlamak</li>
                <li>Hizmet kalitesini iyileştirmek</li>
                <li>Yasal yükümlülükleri yerine getirmek</li>
            </ul>

            <h2>3. Bilgi Paylaşımı</h2>
            <p>Kişisel bilgileriniz aşağıdaki durumlar dışında üçüncü taraflarla paylaşılmaz:</p>
            <ul>
                <li>Diyetisyenler ile danışanlık ilişkisi kapsamında</li>
                <li>Ödeme işlemleri için finansal kuruluşlar ile</li>
                <li>Yasal zorunluluk durumunda yetkili makamlar ile</li>
                <li>Açık rızanızın bulunduğu durumlarda</li>
            </ul>

            <h2>4. Veri Güvenliği</h2>
            <p>Verilerinizin güvenliğini sağlamak için:</p>
            <ul>
                <li>SSL şifreleme teknolojisi kullanılır</li>
                <li>Güvenli sunucularda saklanır</li>
                <li>Düzenli güvenlik denetimleri yapılır</li>
                <li>Yetkisiz erişime karşı koruma sağlanır</li>
            </ul>

            <h2>5. Çerezler (Cookies)</h2>
            <p>Sitemiz, kullanıcı deneyimini iyileştirmek için çerezler kullanır. Tarayıcı ayarlarınızdan çerezleri yönetebilirsiniz.</p>

            <h2>6. Kullanıcı Hakları</h2>
            <p>KVKK kapsamında şu haklara sahipsiniz:</p>
            <ul>
                <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
                <li>İşlenmişse bilgi talep etme</li>
                <li>İşlenme amacını ve amaca uygun kullanılıp kullanılmadığını öğrenme</li>
                <li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme</li>
                <li>Eksik veya yanlış işlenmişse düzeltilmesini isteme</li>
                <li>Silinmesini veya yok edilmesini isteme</li>
            </ul>

            <h2>7. Değişiklikler</h2>
            <p>Bu gizlilik politikası zaman zaman güncellenebilir. Önemli değişiklikler e-posta yoluyla bildirilecektir.</p>

            <h2>8. İletişim</h2>
            <p>Gizlilik politikası hakkında sorularınız için bizimle iletişime geçebilirsiniz:</p>
            <ul>
                <li><strong>E-posta:</strong> privacy@diyetlenio.com</li>
                <li><strong>Telefon:</strong> +90 (212) 123 45 67</li>
                <li><strong>Adres:</strong> Maslak Mahallesi, Büyükdere Cad. No:123, Sarıyer, İstanbul</li>
            </ul>
        </div>
    </div>

<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
