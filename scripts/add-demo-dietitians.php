#!/usr/bin/env php
<?php
/**
 * Demo Diyetisyenler Ekle
 * Run: php scripts/add-demo-dietitians.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🏥 Demo Diyetisyenler Ekleniyor...\n";
echo "═══════════════════════════════════════\n\n";

// Load .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ .env dosyası bulunamadı\n");
}

$env = parse_ini_file($envFile);

// Direct database connection
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $env['DB_HOST'] ?? 'localhost',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? 'diyetlenio_db',
        $env['DB_CHARSET'] ?? 'utf8mb4'
    );

    $conn = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Veritabanı bağlantısı başarılı\n\n";

    // Demo diyetisyenler
    $demoDietitians = [
        [
            'full_name' => 'Ayşe Yılmaz',
            'email' => 'ayse.yilmaz@diyetlenio.com',
            'phone' => '05321234567',
            'title' => 'Dyt. Ayşe Yılmaz',
            'specialization' => 'Spor Beslenmesi',
            'about_me' => 'Spor beslenmesi alanında 8 yıllık deneyime sahibim. Özellikle sporcuların performans artırıcı beslenme programları konusunda uzmanım. Kilo kaybı ve kas kazanımı için kişiye özel programlar hazırlıyorum.',
            'experience_years' => 8,
            'education' => 'Hacettepe Üniversitesi Beslenme ve Diyetetik, Spor Beslenmesi Yüksek Lisans',
            'certifications' => 'ISSN Spor Beslenmesi Sertifikası, Klinik Beslenme Destek Uzmanı',
            'consultation_fee' => 500.00,
            'online_consultation_fee' => 350.00,
            'rating_avg' => 4.8,
            'total_clients' => 127
        ],
        [
            'full_name' => 'Mehmet Demir',
            'email' => 'mehmet.demir@diyetlenio.com',
            'phone' => '05339876543',
            'title' => 'Dyt. Mehmet Demir',
            'specialization' => 'Klinik Beslenme',
            'about_me' => 'Diyabet, metabolik sendrom ve kardiyovasküler hastalıklar için özel diyet programları hazırlıyorum. 10 yılı aşkın klinik deneyimim ile hastalarıma sağlıklı yaşam yolunda rehberlik ediyorum.',
            'experience_years' => 12,
            'education' => 'Ankara Üniversitesi Beslenme ve Diyetetik, Klinik Beslenme Doktora',
            'certifications' => 'Diyabet Eğiticisi Sertifikası, Klinik Beslenme Uzmanı',
            'consultation_fee' => 600.00,
            'online_consultation_fee' => 400.00,
            'rating_avg' => 4.9,
            'total_clients' => 213
        ],
        [
            'full_name' => 'Zeynep Kaya',
            'email' => 'zeynep.kaya@diyetlenio.com',
            'phone' => '05357654321',
            'title' => 'Dyt. Zeynep Kaya',
            'specialization' => 'Çocuk Beslenmesi',
            'about_me' => 'Bebek, çocuk ve ergen beslenmesi konusunda uzmanım. Ailelerle birlikte çalışarak çocuklarınızın sağlıklı beslenme alışkanlıkları kazanmasına yardımcı oluyorum. Seçici yeme, obezite ve büyüme-gelişme sorunlarında deneyimliyim.',
            'experience_years' => 6,
            'education' => 'Başkent Üniversitesi Beslenme ve Diyetetik, Çocuk Beslenmesi Sertifika Programı',
            'certifications' => 'Çocuk Beslenmesi Uzmanı, Beslenme Koçu',
            'consultation_fee' => 450.00,
            'online_consultation_fee' => 300.00,
            'rating_avg' => 4.7,
            'total_clients' => 89
        ],
        [
            'full_name' => 'Ahmet Öztürk',
            'email' => 'ahmet.ozturk@diyetlenio.com',
            'phone' => '05364567890',
            'title' => 'Dyt. Ahmet Öztürk',
            'specialization' => 'Obezite ve Kilo Yönetimi',
            'about_me' => 'Obezite tedavisi ve sağlıklı kilo yönetimi alanında uzmanlaşmış bir diyetisyenim. Davranış değişikliği ve sürdürülebilir yaşam tarzı değişiklikleri ile kalıcı sonuçlar elde etmenize yardımcı oluyorum.',
            'experience_years' => 9,
            'education' => 'Gazi Üniversitesi Beslenme ve Diyetetik, Obezite Tedavisi Sertifika',
            'certifications' => 'Bariatrik Cerrahi Beslenme Uzmanı, Motivasyonel Görüşme Sertifikası',
            'consultation_fee' => 550.00,
            'online_consultation_fee' => 375.00,
            'rating_avg' => 4.8,
            'total_clients' => 156
        ],
        [
            'full_name' => 'Elif Şahin',
            'email' => 'elif.sahin@diyetlenio.com',
            'phone' => '05372345678',
            'title' => 'Dyt. Elif Şahin',
            'specialization' => 'Vejetaryen ve Vegan Beslenme',
            'about_me' => 'Bitkisel beslenme, vejetaryen ve vegan diyetler konusunda uzmanım. Dengeli ve sağlıklı bitkisel beslenme ile yaşam kalitenizi artırmanıza yardımcı oluyorum. Protein, vitamin ve mineral dengesine özel önem veriyorum.',
            'experience_years' => 5,
            'education' => 'İstanbul Üniversitesi Beslenme ve Diyetetik, Bitkisel Beslenme Sertifikası',
            'certifications' => 'Vejetaryen Beslenme Uzmanı, Beslenme Koçluğu Sertifikası',
            'consultation_fee' => 400.00,
            'online_consultation_fee' => 275.00,
            'rating_avg' => 4.6,
            'total_clients' => 73
        ],
        [
            'full_name' => 'Can Yıldırım',
            'email' => 'can.yildirim@diyetlenio.com',
            'phone' => '05383456789',
            'title' => 'Dyt. Can Yıldırım',
            'specialization' => 'Fonksiyonel Beslenme',
            'about_me' => 'Fonksiyonel tıp yaklaşımı ile beslenme programları hazırlıyorum. Bağırsak sağlığı, hormon dengesi, otoimmün hastalıklar ve kronik inflamasyon konularında danışmanlık veriyorum.',
            'experience_years' => 7,
            'education' => 'Ege Üniversitesi Beslenme ve Diyetetik, Fonksiyonel Tıp Sertifikası',
            'certifications' => 'Fonksiyonel Beslenme Uzmanı, Bağırsak Sağlığı Sertifikası',
            'consultation_fee' => 650.00,
            'online_consultation_fee' => 450.00,
            'rating_avg' => 4.9,
            'total_clients' => 94
        ]
    ];

    $created = 0;
    $skipped = 0;

    foreach ($demoDietitians as $dietitian) {
        echo "👤 {$dietitian['full_name']} ekleniyor...\n";

        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$dietitian['email']]);

        if ($stmt->fetch()) {
            echo "   ⊘ Zaten var, atlanıyor\n\n";
            $skipped++;
            continue;
        }

        // Create password hash (Demo123!)
        $passwordHash = password_hash('Demo123!', PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'dietitian', 1, 1, NOW(), NOW())
        ");

        $stmt->execute([
            $dietitian['email'],
            $passwordHash,
            $dietitian['full_name'],
            $dietitian['phone']
        ]);

        $userId = $conn->lastInsertId();

        // Insert dietitian profile
        $stmt = $conn->prepare("
            INSERT INTO dietitian_profiles (
                user_id, title, specialization, about_me, experience_years,
                education, certifications, consultation_fee, online_consultation_fee,
                rating_avg, total_clients, is_verified, created_at, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");

        $stmt->execute([
            $userId,
            $dietitian['title'],
            $dietitian['specialization'],
            $dietitian['about_me'],
            $dietitian['experience_years'],
            $dietitian['education'],
            $dietitian['certifications'],
            $dietitian['consultation_fee'],
            $dietitian['online_consultation_fee'],
            $dietitian['rating_avg'],
            $dietitian['total_clients']
        ]);

        echo "   ✅ Başarıyla eklendi (ID: {$userId})\n";
        echo "   📧 Email: {$dietitian['email']}\n";
        echo "   🔒 Şifre: Demo123!\n";
        echo "   💼 Uzmanlık: {$dietitian['specialization']}\n\n";

        $created++;
    }

    echo "═══════════════════════════════════════\n";
    echo "🎉 TAMAMLANDI!\n";
    echo "═══════════════════════════════════════\n\n";
    echo "📊 Sonuç:\n";
    echo "   ✅ Eklenen: {$created}\n";
    echo "   ⊘ Atlanan: {$skipped}\n";
    echo "   📝 Toplam: " . ($created + $skipped) . "\n\n";

    if ($created > 0) {
        echo "🔑 Demo Diyetisyen Giriş Bilgileri:\n";
        echo "   Şifre (hepsi için): Demo123!\n\n";

        echo "📧 Email Adresleri:\n";
        foreach ($demoDietitians as $dietitian) {
            echo "   - {$dietitian['email']} ({$dietitian['specialization']})\n";
        }
        echo "\n";

        echo "🌐 Test için:\n";
        echo "   1. http://localhost:8080/login.php\n";
        echo "   2. Yukarıdaki email'lerden birini kullan\n";
        echo "   3. Şifre: Demo123!\n\n";
    }

} catch (Exception $e) {
    echo "\n❌ Hata: " . $e->getMessage() . "\n";
    echo "\nDetay:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
