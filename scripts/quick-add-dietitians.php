#!/usr/bin/env php
<?php
/**
 * Quick Add Demo Dietitians
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Quick Demo Diyetisyen Ekleme ===\n\n";

// Load .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ .env file not found\n");
}

$env = parse_ini_file($envFile);

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

    echo "✅ Database connected\n\n";

    $dietitians = [
        ['Dr. Ayşe Yılmaz', 'ayse.yilmaz@diyetlenio.com', '0532 111 11 11', 'Diyetisyen, Beslenme Uzmanı', 'Spor Beslenmesi', 8, 500, 4.8],
        ['Mehmet Demir', 'mehmet.demir@diyetlenio.com', '0533 222 22 22', 'Uzman Diyetisyen', 'Klinik Beslenme', 12, 600, 4.9],
        ['Zeynep Kaya', 'zeynep.kaya@diyetlenio.com', '0534 333 33 33', 'Diyetisyen', 'Çocuk Beslenmesi', 6, 450, 4.7],
        ['Ahmet Öztürk', 'ahmet.ozturk@diyetlenio.com', '0535 444 44 44', 'Klinik Diyetisyen', 'Obezite ve Kilo Yönetimi', 9, 550, 4.8],
        ['Elif Şahin', 'elif.sahin@diyetlenio.com', '0536 555 55 55', 'Beslenme Uzmanı', 'Vejetaryen ve Vegan Beslenme', 5, 400, 4.6],
        ['Can Yıldırım', 'can.yildirim@diyetlenio.com', '0537 666 66 66', 'Uzman Diyetisyen', 'Fonksiyonel Beslenme', 7, 500, 4.7]
    ];

    $addedCount = 0;
    $hashedPassword = password_hash('Demo123!', PASSWORD_BCRYPT);

    foreach ($dietitians as $d) {
        // Email kontrolü
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$d[1]]);
        if ($stmt->fetch()) {
            echo "⏭️  {$d[0]} zaten var\n";
            continue;
        }

        // User ekle
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at) VALUES (?, ?, ?, ?, 'dietitian', 1, NOW())");
        $stmt->execute([$d[0], $d[1], $hashedPassword, $d[2]]);
        $userId = $conn->lastInsertId();

        // Profile ekle
        $stmt = $conn->prepare("INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 50, 100, 1, NOW())");
        $stmt->execute([
            $userId,
            $d[3],
            $d[4],
            "Uzman {$d[4]} diyetisyeni. {$d[5]} yıllık deneyim.",
            $d[5],
            $d[6],
            $d[7]
        ]);

        echo "✅ {$d[0]} eklendi (ID: {$userId})\n";
        $addedCount++;
    }

    echo "\n🎉 Toplam {$addedCount} diyetisyen eklendi!\n";
    echo "\nGiriş Bilgileri:\n";
    echo "Email: ayse.yilmaz@diyetlenio.com\n";
    echo "Şifre: Demo123!\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
