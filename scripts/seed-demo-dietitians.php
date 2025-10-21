<?php
/**
 * Demo Diyetisyen Seeder
 * 12 demo diyetisyen profili oluşturur (fotoğraflı)
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// Demo diyetisyen bilgileri
$demoDietitians = [
    [
        'full_name' => 'Dr. Ayşe Yılmaz',
        'email' => 'ayse.yilmaz@demo.com',
        'phone' => '05321234567',
        'title' => 'Diyetisyen ve Beslenme Uzmanı',
        'specialization' => 'Spor Beslenmesi, Kilo Yönetimi',
        'experience_years' => 12,
        'about_me' => 'Spor beslenmesi ve kilo yönetimi alanında 12 yıllık deneyime sahip uzman diyetisyen. Özellikle sporcular ve aktif yaşam tarzı olan bireyler için özelleştirilmiş beslenme programları hazırlamaktayım.',
        'education' => 'Hacettepe Üniversitesi Beslenme ve Diyetetik Bölümü',
        'consultation_fee' => 500,
        'profile_photo' => 'demo-dietitian-1.jpg',
        'rating_avg' => 4.9,
        'total_clients' => 156,
        'is_on_call' => 1
    ],
    [
        'full_name' => 'Uzm. Dyt. Mehmet Demir',
        'email' => 'mehmet.demir@demo.com',
        'phone' => '05331234568',
        'title' => 'Klinik Diyetisyen',
        'specialization' => 'Diyabet, Metabolik Hastalıklar',
        'experience_years' => 8,
        'about_me' => 'Diyabet ve metabolik hastalıklar konusunda uzman klinik diyetisyen. Hastalarıma bireysel diyet programları ile sağlıklı yaşam desteği sağlıyorum.',
        'education' => 'Gazi Üniversitesi Beslenme ve Diyetetik',
        'consultation_fee' => 450,
        'profile_photo' => 'demo-dietitian-2.jpg',
        'rating_avg' => 4.8,
        'total_clients' => 134,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dyt. Zeynep Kaya',
        'email' => 'zeynep.kaya@demo.com',
        'phone' => '05341234569',
        'title' => 'Pediatrik Diyetisyen',
        'specialization' => 'Çocuk Beslenmesi, Gelişim',
        'experience_years' => 6,
        'about_me' => 'Çocuk beslenmesi ve gelişimi konusunda uzmanlaşmış diyetisyen. Bebek ve çocuklarınız için sağlıklı büyüme destekli beslenme programları hazırlıyorum.',
        'education' => 'İstanbul Üniversitesi Beslenme ve Diyetetik',
        'consultation_fee' => 400,
        'profile_photo' => 'demo-dietitian-3.jpg',
        'rating_avg' => 4.9,
        'total_clients' => 98,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Prof. Dr. Ali Yıldız',
        'email' => 'ali.yildiz@demo.com',
        'phone' => '05351234570',
        'title' => 'Profesör Diyetisyen',
        'specialization' => 'Onkolojik Beslenme, Yaşlı Beslenmesi',
        'experience_years' => 20,
        'about_me' => 'Onkoloji hastaları ve yaşlı beslenmesi konusunda 20 yıllık akademik ve klinik deneyim. Kanser tedavisi sürecinde doğru beslenme desteği sağlıyorum.',
        'education' => 'Ankara Üniversitesi Tıp Fakültesi',
        'consultation_fee' => 750,
        'profile_photo' => 'demo-dietitian-4.jpg',
        'rating_avg' => 5.0,
        'total_clients' => 245,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dyt. Elif Şahin',
        'email' => 'elif.sahin@demo.com',
        'phone' => '05361234571',
        'title' => 'Vejetaryen/Vegan Beslenme Uzmanı',
        'specialization' => 'Bitkisel Beslenme, Makrobiyotik',
        'experience_years' => 5,
        'about_me' => 'Vejetaryen ve vegan beslenme konusunda uzman diyetisyen. Bitkisel beslenme ile dengeli ve sağlıklı yaşam için özel programlar hazırlıyorum.',
        'education' => 'Ege Üniversitesi Beslenme ve Diyetetik',
        'consultation_fee' => 380,
        'profile_photo' => 'demo-dietitian-5.jpg',
        'rating_avg' => 4.7,
        'total_clients' => 87,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Uzm. Dyt. Can Arslan',
        'email' => 'can.arslan@demo.com',
        'phone' => '05371234572',
        'title' => 'Fitness ve Vücut Geliştirme Diyetisyeni',
        'specialization' => 'Kas Gelişimi, Yağ Yakımı',
        'experience_years' => 10,
        'about_me' => 'Fitness sporcuları ve vücut geliştirme ile ilgilenenler için özelleştirilmiş beslenme programları. Kas artışı ve yağ yakımı hedeflerinize ulaşmanızda yanınızdayım.',
        'education' => 'Marmara Üniversitesi Spor Bilimleri',
        'consultation_fee' => 550,
        'profile_photo' => 'demo-dietitian-6.jpg',
        'rating_avg' => 4.8,
        'total_clients' => 178,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dyt. Selin Özdemir',
        'email' => 'selin.ozdemir@demo.com',
        'phone' => '05381234573',
        'title' => 'Hamilelik ve Emzirme Diyetisyeni',
        'specialization' => 'Gebelik, Laktasyon Dönemi',
        'experience_years' => 7,
        'about_me' => 'Hamilelik ve emzirme döneminde annelere özel beslenme danışmanlığı. Hem anne hem de bebek sağlığı için dengeli programlar hazırlıyorum.',
        'education' => 'Başkent Üniversitesi Beslenme ve Diyetetik',
        'consultation_fee' => 420,
        'profile_photo' => 'demo-dietitian-7.jpg',
        'rating_avg' => 4.9,
        'total_clients' => 112,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dr. Emre Yılmaz',
        'email' => 'emre.yilmaz@demo.com',
        'phone' => '05391234574',
        'title' => 'Kardiyolojik Beslenme Uzmanı',
        'specialization' => 'Kalp Sağlığı, Kolesterol Yönetimi',
        'experience_years' => 15,
        'about_me' => 'Kalp sağlığı ve kolesterol yönetimi konusunda uzman diyetisyen. Kardiyovasküler hastalıkların önlenmesi ve tedavisinde beslenme desteği sağlıyorum.',
        'education' => 'Hacettepe Üniversitesi Tıp Fakültesi',
        'consultation_fee' => 650,
        'profile_photo' => 'demo-dietitian-8.jpg',
        'rating_avg' => 4.9,
        'total_clients' => 203,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dyt. Deniz Kara',
        'email' => 'deniz.kara@demo.com',
        'phone' => '05401234575',
        'title' => 'Alerji ve İntolerans Uzmanı',
        'specialization' => 'Gıda Alerjileri, Çölyak, Laktoz İntoleransı',
        'experience_years' => 9,
        'about_me' => 'Gıda alerjileri ve intoleranslar konusunda uzman diyetisyen. Çölyak, laktoz intoleransı gibi durumlarda güvenli beslenme programları hazırlıyorum.',
        'education' => 'Dokuz Eylül Üniversitesi Beslenme',
        'consultation_fee' => 480,
        'profile_photo' => 'demo-dietitian-9.jpg',
        'rating_avg' => 4.8,
        'total_clients' => 145,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Uzm. Dyt. Merve Aydın',
        'email' => 'merve.aydin@demo.com',
        'phone' => '05411234576',
        'title' => 'Psikolojik Beslenme Danışmanı',
        'specialization' => 'Duygusal Yeme, Binge Eating',
        'experience_years' => 6,
        'about_me' => 'Duygusal yeme ve binge eating bozukluğu konusunda uzman diyetisyen. Beslenme alışkanlıklarınızı psikolojik destekle değiştirmenize yardımcı oluyorum.',
        'education' => 'İstanbul Üniversitesi Psikoloji & Beslenme',
        'consultation_fee' => 520,
        'profile_photo' => 'demo-dietitian-10.jpg',
        'rating_avg' => 4.9,
        'total_clients' => 128,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dyt. Burak Çelik',
        'email' => 'burak.celik@demo.com',
        'phone' => '05421234577',
        'title' => 'Detoks ve Bağırsak Sağlığı Uzmanı',
        'specialization' => 'Detoks Programları, Probiyotik Beslenme',
        'experience_years' => 4,
        'about_me' => 'Detoks programları ve bağırsak sağlığı konusunda uzman diyetisyen. Vücudunuzu arındırmak ve bağırsak floranızı iyileştirmek için özel programlar.',
        'education' => 'Akdeniz Üniversitesi Beslenme ve Diyetetik',
        'consultation_fee' => 440,
        'profile_photo' => 'demo-dietitian-11.jpg',
        'rating_avg' => 4.7,
        'total_clients' => 92,
        'is_on_call' => 0
    ],
    [
        'full_name' => 'Dr. Dyt. Işıl Tan',
        'email' => 'isil.tan@demo.com',
        'phone' => '05431234578',
        'title' => 'Fonksiyonel Tıp Diyetisyeni',
        'specialization' => 'Hormon Dengesi, Anti-aging',
        'experience_years' => 11,
        'about_me' => 'Fonksiyonel tıp yaklaşımıyla hormon dengesi ve anti-aging beslenme programları. Kök neden analizi ile sağlıklı yaşlanma desteği sağlıyorum.',
        'education' => 'Yeditepe Üniversitesi Tıp Fakültesi',
        'consultation_fee' => 700,
        'profile_photo' => 'demo-dietitian-12.jpg',
        'rating_avg' => 5.0,
        'total_clients' => 167,
        'is_on_call' => 0
    ]
];

// IBAN placeholder
$defaultIban = 'TR330006100519786457841326';

echo "🚀 Demo diyetisyen seed işlemi başlıyor...\n\n";

try {
    $conn->beginTransaction();

    $created = 0;
    $skipped = 0;

    foreach ($demoDietitians as $index => $dietitian) {
        // Email kontrolü
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$dietitian['email']]);
        if ($stmt->fetch()) {
            echo "⚠️  {$dietitian['full_name']} zaten mevcut, atlanıyor...\n";
            $skipped++;
            continue;
        }

        // Kullanıcı oluştur
        $password = password_hash('demo123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, full_name, phone, user_type, is_active, email_verified, created_at)
            VALUES (?, ?, ?, ?, 'dietitian', 1, 1, NOW())
        ");
        $stmt->execute([
            $dietitian['email'],
            $password,
            $dietitian['full_name'],
            $dietitian['phone']
        ]);

        $userId = $conn->lastInsertId();

        // Profil fotoğrafı placeholder (UI Avatars API)
        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($dietitian['full_name']) . '&size=400&background=random';

        // Diyetisyen profili oluştur
        $stmt = $conn->prepare("
            INSERT INTO dietitian_profiles (
                user_id, title, specialization, experience_years, about_me,
                education, consultation_fee, is_approved, rating_avg, total_clients,
                is_on_call, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $dietitian['title'],
            $dietitian['specialization'],
            $dietitian['experience_years'],
            $dietitian['about_me'],
            $dietitian['education'],
            $dietitian['consultation_fee'],
            $dietitian['rating_avg'],
            $dietitian['total_clients'],
            $dietitian['is_on_call']
        ]);

        echo "✅ {$dietitian['full_name']} oluşturuldu (ID: $userId)\n";
        $created++;
    }

    $conn->commit();

    echo "\n";
    echo "═══════════════════════════════════════\n";
    echo "🎉 Seed işlemi tamamlandı!\n";
    echo "═══════════════════════════════════════\n";
    echo "✅ Oluşturulan: $created\n";
    echo "⚠️  Atlanan: $skipped\n";
    echo "📊 Toplam: " . count($demoDietitians) . "\n";
    echo "\n";
    echo "📝 Demo Hesap Bilgileri:\n";
    echo "   Email: ayse.yilmaz@demo.com (ve diğerleri)\n";
    echo "   Şifre: demo123\n";
    echo "\n";
    echo "🔥 Acil nöbetçi: Dr. Ayşe Yılmaz (is_on_call=1)\n";
    echo "\n";

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "❌ HATA: " . $e->getMessage() . "\n";
    exit(1);
}
