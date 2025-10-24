#!/usr/bin/env php
<?php
/**
 * Railway Database - Demo Diyetisyen Ekleme
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Railway Database - Demo Diyetisyen Ekleme ===\n\n";

try {
    $dsn = "mysql:host=nozomi.proxy.rlwy.net;port=12434;dbname=railway;charset=utf8mb4";
    $username = "root";
    $password = "HrpWATAjzmJhHeUuItKmmwvtVXGZf";

    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Railway database'e bağlanıldı!\n\n";

    $dietitians = [
        [
            'full_name' => 'Dr. Ayşe Yılmaz',
            'email' => 'ayse.yilmaz@diyetlenio.com',
            'phone' => '0532 111 11 11',
            'title' => 'Diyetisyen, Beslenme Uzmanı',
            'specialization' => 'Spor Beslenmesi',
            'about_me' => 'Spor beslenmesi alanında 8 yıllık deneyime sahip uzman diyetisyen.',
            'education' => 'Hacettepe Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Spor Beslenmesi Sertifikası',
            'experience_years' => 8,
            'consultation_fee' => 500,
            'rating_avg' => 4.8,
            'rating_count' => 45,
            'total_clients' => 120
        ],
        [
            'full_name' => 'Mehmet Demir',
            'email' => 'mehmet.demir@diyetlenio.com',
            'phone' => '0533 222 22 22',
            'title' => 'Uzman Diyetisyen',
            'specialization' => 'Klinik Beslenme',
            'about_me' => 'Klinik beslenme ve metabolik hastalıklar konusunda uzman.',
            'education' => 'Ankara Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Klinik Beslenme Uzmanlığı',
            'experience_years' => 12,
            'consultation_fee' => 600,
            'rating_avg' => 4.9,
            'rating_count' => 78,
            'total_clients' => 200
        ],
        [
            'full_name' => 'Zeynep Kaya',
            'email' => 'zeynep.kaya@diyetlenio.com',
            'phone' => '0534 333 33 33',
            'title' => 'Diyetisyen',
            'specialization' => 'Çocuk Beslenmesi',
            'about_me' => 'Bebek ve çocuk beslenmesi konusunda uzman.',
            'education' => 'Ege Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Çocuk Beslenmesi Sertifikası',
            'experience_years' => 6,
            'consultation_fee' => 450,
            'rating_avg' => 4.7,
            'rating_count' => 52,
            'total_clients' => 90
        ],
        [
            'full_name' => 'Ahmet Öztürk',
            'email' => 'ahmet.ozturk@diyetlenio.com',
            'phone' => '0535 444 44 44',
            'title' => 'Klinik Diyetisyen',
            'specialization' => 'Obezite ve Kilo Yönetimi',
            'about_me' => 'Obezite tedavisi ve sağlıklı kilo kaybı konusunda uzman.',
            'education' => 'İstanbul Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Obezite Tedavisi Sertifikası',
            'experience_years' => 9,
            'consultation_fee' => 550,
            'rating_avg' => 4.8,
            'rating_count' => 65,
            'total_clients' => 150
        ],
        [
            'full_name' => 'Elif Şahin',
            'email' => 'elif.sahin@diyetlenio.com',
            'phone' => '0536 555 55 55',
            'title' => 'Beslenme Uzmanı',
            'specialization' => 'Vejetaryen ve Vegan Beslenme',
            'about_me' => 'Bitkisel beslenme konusunda uzman.',
            'education' => 'Gazi Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Vegan Beslenme Sertifikası',
            'experience_years' => 5,
            'consultation_fee' => 400,
            'rating_avg' => 4.6,
            'rating_count' => 38,
            'total_clients' => 75
        ],
        [
            'full_name' => 'Can Yıldırım',
            'email' => 'can.yildirim@diyetlenio.com',
            'phone' => '0537 666 66 66',
            'title' => 'Uzman Diyetisyen',
            'specialization' => 'Fonksiyonel Beslenme',
            'about_me' => 'Fonksiyonel beslenme ve bağırsak sağlığı konusunda uzman.',
            'education' => 'Başkent Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Fonksiyonel Beslenme Sertifikası',
            'experience_years' => 7,
            'consultation_fee' => 500,
            'rating_avg' => 4.7,
            'rating_count' => 42,
            'total_clients' => 95
        ]
    ];

    $addedCount = 0;
    $skippedCount = 0;
    $hashedPassword = password_hash('Demo123!', PASSWORD_BCRYPT);

    foreach ($dietitians as $d) {
        // Email kontrolü
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$d['email']]);
        if ($stmt->fetch()) {
            echo "⏭️  {$d['full_name']} zaten var, atlanıyor...\n";
            $skippedCount++;
            continue;
        }

        // User ekle
        $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
            VALUES (?, ?, ?, ?, 'dietitian', 1, NOW())
        ");
        $stmt->execute([$d['full_name'], $d['email'], $hashedPassword, $d['phone']]);
        $userId = $conn->lastInsertId();

        // Profile ekle
        $stmt = $conn->prepare("
            INSERT INTO dietitian_profiles (
                user_id, title, specialization, about_me, education,
                certifications, experience_years, consultation_fee,
                rating_avg, rating_count, total_clients, is_approved, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $userId,
            $d['title'],
            $d['specialization'],
            $d['about_me'],
            $d['education'],
            $d['certifications'],
            $d['experience_years'],
            $d['consultation_fee'],
            $d['rating_avg'],
            $d['rating_count'],
            $d['total_clients']
        ]);

        echo "✅ {$d['full_name']} eklendi (ID: {$userId})\n";
        $addedCount++;
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "ÖZET:\n";
    echo "  ✅ Eklenen: {$addedCount}\n";
    echo "  ⏭️  Atlanan: {$skippedCount}\n";
    echo "\n🎉 İşlem tamamlandı!\n\n";

    if ($addedCount > 0) {
        echo "Demo Giriş Bilgileri:\n";
        echo "  Email: ayse.yilmaz@diyetlenio.com\n";
        echo "  Şifre: Demo123!\n";
        echo "  (Tüm demo diyetisyenler için aynı şifre)\n\n";

        echo "Artık diyetisyenleri görebilirsiniz:\n";
        echo "  https://www.diyetlenio.com/dietitians.php\n";
    }

} catch (PDOException $e) {
    echo "❌ Veritabanı Hatası: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
