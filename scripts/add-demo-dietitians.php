#!/usr/bin/env php
<?php
/**
 * Demo Diyetisyen Ekleme Scripti
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $conn = $db->getConnection();

    echo "=== Demo Diyetisyen Ekleme Scripti ===\n\n";

    // Demo diyetisyenler
    $dietitians = [
        [
            'full_name' => 'Dr. Ayşe Yılmaz',
            'email' => 'ayse.yilmaz@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0532 111 11 11',
            'title' => 'Diyetisyen, Beslenme Uzmanı',
            'specialization' => 'Spor Beslenmesi',
            'about_me' => 'Spor beslenmesi alanında 8 yıllık deneyime sahip, çok sayıda profesyonel sporcu ile çalışmış uzman diyetisyenim. Kişiye özel beslenme programları ile spor performansınızı artırmanıza yardımcı oluyorum.',
            'education' => 'Hacettepe Üniversitesi Beslenme ve Diyetetik Bölümü',
            'certifications' => 'Spor Beslenmesi Sertifikası, İleri Diyetetik Eğitimi',
            'experience_years' => 8,
            'consultation_fee' => 500,
            'rating_avg' => 4.8,
            'rating_count' => 45,
            'total_clients' => 120
        ],
        [
            'full_name' => 'Mehmet Demir',
            'email' => 'mehmet.demir@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0533 222 22 22',
            'title' => 'Uzman Diyetisyen',
            'specialization' => 'Klinik Beslenme',
            'about_me' => 'Klinik beslenme ve metabolik hastalıklar konusunda uzmanım. Diyabet, kolesterol, tiroid hastalıkları gibi kronik rahatsızlıkların beslenmesi ile ilgili profesyonel destek sağlıyorum.',
            'education' => 'Ankara Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Klinik Beslenme Uzmanlığı, Diyabet Eğitimi Sertifikası',
            'experience_years' => 12,
            'consultation_fee' => 600,
            'rating_avg' => 4.9,
            'rating_count' => 78,
            'total_clients' => 200
        ],
        [
            'full_name' => 'Zeynep Kaya',
            'email' => 'zeynep.kaya@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0534 333 33 33',
            'title' => 'Diyetisyen',
            'specialization' => 'Çocuk Beslenmesi',
            'about_me' => 'Bebek ve çocuk beslenmesi konusunda uzmanlaşmış diyetisyenim. Aileler için pratik ve sağlıklı beslenme çözümleri üretiyorum. Çocuğunuzun sağlıklı büyümesi için yanınızdayım.',
            'education' => 'Ege Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Çocuk Beslenmesi Sertifikası, Ek Gıdaya Geçiş Eğitimi',
            'experience_years' => 6,
            'consultation_fee' => 450,
            'rating_avg' => 4.7,
            'rating_count' => 52,
            'total_clients' => 90
        ],
        [
            'full_name' => 'Ahmet Öztürk',
            'email' => 'ahmet.ozturk@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0535 444 44 44',
            'title' => 'Klinik Diyetisyen',
            'specialization' => 'Obezite ve Kilo Yönetimi',
            'about_me' => 'Obezite tedavisi ve sağlıklı kilo kaybı konusunda uzmanım. Yoyo etkisi olmadan, sürdürülebilir kilo kaybı programları ile hedeflerinize ulaşmanıza yardımcı oluyorum.',
            'education' => 'İstanbul Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Obezite Tedavisi Sertifikası, Davranışsal Beslenme Terapisi',
            'experience_years' => 9,
            'consultation_fee' => 550,
            'rating_avg' => 4.8,
            'rating_count' => 65,
            'total_clients' => 150
        ],
        [
            'full_name' => 'Elif Şahin',
            'email' => 'elif.sahin@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0536 555 55 55',
            'title' => 'Beslenme Uzmanı',
            'specialization' => 'Vejetaryen ve Vegan Beslenme',
            'about_me' => 'Bitkisel beslenme konusunda uzmanım. Vejetaryen ve vegan yaşam tarzını benimseyen bireyler için dengeli ve sağlıklı beslenme programları hazırlıyorum.',
            'education' => 'Gazi Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Vegan Beslenme Sertifikası, Bitkisel Protein Kaynakları Eğitimi',
            'experience_years' => 5,
            'consultation_fee' => 400,
            'rating_avg' => 4.6,
            'rating_count' => 38,
            'total_clients' => 75
        ],
        [
            'full_name' => 'Can Yıldırım',
            'email' => 'can.yildirim@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0537 666 66 66',
            'title' => 'Uzman Diyetisyen',
            'specialization' => 'Fonksiyonel Beslenme',
            'about_me' => 'Fonksiyonel beslenme ve bağırsak sağlığı konusunda uzmanım. Vücudunuzun optimal performans göstermesi için kişiselleştirilmiş beslenme programları sunuyorum.',
            'education' => 'Başkent Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Fonksiyonel Beslenme Sertifikası, Probiyotik Tedavi Eğitimi',
            'experience_years' => 7,
            'consultation_fee' => 500,
            'rating_avg' => 4.7,
            'rating_count' => 42,
            'total_clients' => 95
        ]
    ];

    $addedCount = 0;
    $skippedCount = 0;

    foreach ($dietitians as $dietitian) {
        // Email kontrolü
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$dietitian['email']]);
        if ($stmt->fetch()) {
            echo "⏭️  {$dietitian['full_name']} zaten var, atlanıyor...\n";
            $skippedCount++;
            continue;
        }

        // Kullanıcı oluştur
        $hashedPassword = password_hash($dietitian['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at)
            VALUES (?, ?, ?, ?, 'dietitian', 1, NOW())
        ");
        $stmt->execute([
            $dietitian['full_name'],
            $dietitian['email'],
            $hashedPassword,
            $dietitian['phone']
        ]);
        $userId = $conn->lastInsertId();

        // Diyetisyen profili oluştur
        $stmt = $conn->prepare("
            INSERT INTO dietitian_profiles (
                user_id, title, specialization, about_me, education,
                certifications, experience_years, consultation_fee,
                rating_avg, rating_count, total_clients, is_approved, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $userId,
            $dietitian['title'],
            $dietitian['specialization'],
            $dietitian['about_me'],
            $dietitian['education'],
            $dietitian['certifications'],
            $dietitian['experience_years'],
            $dietitian['consultation_fee'],
            $dietitian['rating_avg'],
            $dietitian['rating_count'],
            $dietitian['total_clients']
        ]);

        echo "✅ {$dietitian['full_name']} eklendi (ID: {$userId})\n";
        $addedCount++;
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Özet:\n";
    echo "  ✅ Eklenen: {$addedCount}\n";
    echo "  ⏭️  Atlanan: {$skippedCount}\n";
    echo "\n🎉 İşlem tamamlandı!\n\n";

    echo "Demo Giriş Bilgileri:\n";
    echo "Email: ayse.yilmaz@diyetlenio.com\n";
    echo "Şifre: Demo123!\n";
    echo "(Tüm demo diyetisyenler için aynı şifre)\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
