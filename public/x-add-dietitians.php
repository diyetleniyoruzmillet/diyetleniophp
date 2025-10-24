<?php
/**
 * Simple Web-based Dietitian Setup
 * Access: https://www.diyetlenio.com/x-add-dietitians.php
 */

// Simple password protection
$access_code = $_GET['code'] ?? '';
if ($access_code !== 'setup2025') {
    die('Access code required. URL format: ?code=setup2025');
}

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {

    $dietitians = [
        ['Dr. Ayşe Yılmaz', 'ayse.yilmaz@diyetlenio.com', '0532 111 11 11', 'Diyetisyen, Beslenme Uzmanı', 'Spor Beslenmesi', 8, 500, 4.8],
        ['Mehmet Demir', 'mehmet.demir@diyetlenio.com', '0533 222 22 22', 'Uzman Diyetisyen', 'Klinik Beslenme', 12, 600, 4.9],
        ['Zeynep Kaya', 'zeynep.kaya@diyetlenio.com', '0534 333 33 33', 'Diyetisyen', 'Çocuk Beslenmesi', 6, 450, 4.7],
        ['Ahmet Öztürk', 'ahmet.ozturk@diyetlenio.com', '0535 444 44 44', 'Klinik Diyetisyen', 'Obezite ve Kilo Yönetimi', 9, 550, 4.8],
        ['Elif Şahin', 'elif.sahin@diyetlenio.com', '0536 555 55 55', 'Beslenme Uzmanı', 'Vejetaryen ve Vegan Beslenme', 5, 400, 4.6],
        ['Can Yıldırım', 'can.yildirim@diyetlenio.com', '0537 666 66 66', 'Uzman Diyetisyen', 'Fonksiyonel Beslenme', 7, 500, 4.7]
    ];

    $hashedPassword = password_hash('Demo123!', PASSWORD_BCRYPT);

    foreach ($dietitians as $d) {
        try {
            // Email kontrolü
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$d[1]]);
            if ($stmt->fetch()) {
                $results[] = ['status' => 'skipped', 'name' => $d[0]];
                continue;
            }

            // User ekle
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, user_type, is_active, created_at) VALUES (?, ?, ?, ?, 'dietitian', 1, NOW())");
            $stmt->execute([$d[0], $d[1], $hashedPassword, $d[2]]);
            $userId = $conn->lastInsertId();

            // Profile ekle
            $stmt = $conn->prepare("INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, experience_years, consultation_fee, rating_avg, rating_count, total_clients, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 50, 100, 1, NOW())");
            $stmt->execute([$userId, $d[3], $d[4], "Uzman {$d[4]} diyetisyeni. {$d[5]} yıllık deneyim.", $d[5], $d[6], $d[7]]);

            $results[] = ['status' => 'added', 'name' => $d[0], 'id' => $userId];
        } catch (Exception $e) {
            $results[] = ['status' => 'error', 'name' => $d[0], 'error' => $e->getMessage()];
        }
    }
}

// Mevcut diyetisyenler
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'dietitian'");
$count = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Dietitians</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .btn { background: #4CAF50; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #45a049; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .skip { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Demo Diyetisyen Kurulumu</h1>

        <div class="info">
            <strong>Mevcut Durum:</strong> <?= $count ?> diyetisyen kayıtlı
        </div>

        <?php if (!empty($results)): ?>
            <h3>Sonuçlar:</h3>
            <?php foreach ($results as $r): ?>
                <div class="result <?= $r['status'] === 'added' ? 'success' : ($r['status'] === 'skipped' ? 'skip' : 'error') ?>">
                    <?php if ($r['status'] === 'added'): ?>
                        ✅ <strong><?= $r['name'] ?></strong> eklendi (ID: <?= $r['id'] ?>)
                    <?php elseif ($r['status'] === 'skipped'): ?>
                        ⏭️ <strong><?= $r['name'] ?></strong> zaten var
                    <?php else: ?>
                        ❌ <strong><?= $r['name'] ?></strong> - Hata: <?= $r['error'] ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="info">
                <strong>Giriş Bilgileri:</strong><br>
                Email: ayse.yilmaz@diyetlenio.com<br>
                Şifre: Demo123!<br><br>
                <a href="/dietitians.php" target="_blank">🔗 Diyetisyen Listesini Gör</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <p><strong>Eklenecekler:</strong></p>
                <ul>
                    <li>Dr. Ayşe Yılmaz - Spor Beslenmesi</li>
                    <li>Mehmet Demir - Klinik Beslenme</li>
                    <li>Zeynep Kaya - Çocuk Beslenmesi</li>
                    <li>Ahmet Öztürk - Obezite</li>
                    <li>Elif Şahin - Vegan Beslenme</li>
                    <li>Can Yıldırım - Fonksiyonel Beslenme</li>
                </ul>
                <button type="submit" name="add" class="btn">✨ 6 Diyetisyen Ekle</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
