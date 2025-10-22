<?php
/**
 * Demo Data Setup - Web Interface
 * WARNING: Delete this file after use!
 */

// Security token
$token = $_GET['token'] ?? '';
$expectedToken = md5('setup-demo-2025-' . date('Y-m-d'));

if ($token !== $expectedToken) {
    http_response_code(403);
    die('Invalid security token. Use: ?token=' . $expectedToken . '<br>Token: ' . $expectedToken);
}

require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin only
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    die('Access denied. Admin only.');
}

$conn = $db->getConnection();
$results = [];
$setupRun = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    $setupRun = true;
    $output = '';

    try {
        // Demo diyetisyenler
        $demoDietitians = [
            ['full_name' => 'Ayşe Yılmaz', 'email' => 'ayse.yilmaz@diyetlenio.com', 'phone' => '05321234567', 'title' => 'Dyt. Ayşe Yılmaz', 'specialization' => 'Spor Beslenmesi', 'about_me' => 'Spor beslenmesi alanında 8 yıllık deneyime sahibim. Özellikle sporcuların performans artırıcı beslenme programları konusunda uzmanım.', 'experience_years' => 8, 'education' => 'Hacettepe Üniversitesi Beslenme ve Diyetetik', 'certifications' => 'ISSN Spor Beslenmesi Sertifikası', 'consultation_fee' => 500.00, 'online_consultation_fee' => 350.00, 'rating_avg' => 4.8, 'total_clients' => 127],
            ['full_name' => 'Mehmet Demir', 'email' => 'mehmet.demir@diyetlenio.com', 'phone' => '05339876543', 'title' => 'Dyt. Mehmet Demir', 'specialization' => 'Klinik Beslenme', 'about_me' => 'Diyabet, metabolik sendrom ve kardiyovasküler hastalıklar için özel diyet programları hazırlıyorum.', 'experience_years' => 12, 'education' => 'Ankara Üniversitesi Beslenme ve Diyetetik', 'certifications' => 'Diyabet Eğiticisi Sertifikası', 'consultation_fee' => 600.00, 'online_consultation_fee' => 400.00, 'rating_avg' => 4.9, 'total_clients' => 213],
            ['full_name' => 'Zeynep Kaya', 'email' => 'zeynep.kaya@diyetlenio.com', 'phone' => '05357654321', 'title' => 'Dyt. Zeynep Kaya', 'specialization' => 'Çocuk Beslenmesi', 'about_me' => 'Bebek, çocuk ve ergen beslenmesi konusunda uzmanım. Ailelerle birlikte çalışarak çocuklarınızın sağlıklı beslenme alışkanlıkları kazanmasına yardımcı oluyorum.', 'experience_years' => 6, 'education' => 'Başkent Üniversitesi Beslenme ve Diyetetik', 'certifications' => 'Çocuk Beslenmesi Uzmanı', 'consultation_fee' => 450.00, 'online_consultation_fee' => 300.00, 'rating_avg' => 4.7, 'total_clients' => 89],
            ['full_name' => 'Ahmet Öztürk', 'email' => 'ahmet.ozturk@diyetlenio.com', 'phone' => '05364567890', 'title' => 'Dyt. Ahmet Öztürk', 'specialization' => 'Obezite ve Kilo Yönetimi', 'about_me' => 'Obezite tedavisi ve sağlıklı kilo yönetimi alanında uzmanlaşmış bir diyetisyenim.', 'experience_years' => 9, 'education' => 'Gazi Üniversitesi Beslenme ve Diyetetik', 'certifications' => 'Bariatrik Cerrahi Beslenme Uzmanı', 'consultation_fee' => 550.00, 'online_consultation_fee' => 375.00, 'rating_avg' => 4.8, 'total_clients' => 156],
            ['full_name' => 'Elif Şahin', 'email' => 'elif.sahin@diyetlenio.com', 'phone' => '05372345678', 'title' => 'Dyt. Elif Şahin', 'specialization' => 'Vejetaryen ve Vegan Beslenme', 'about_me' => 'Bitkisel beslenme, vejetaryen ve vegan diyetler konusunda uzmanım.', 'experience_years' => 5, 'education' => 'İstanbul Üniversitesi Beslenme ve Diyetetik', 'certifications' => 'Vejetaryen Beslenme Uzmanı', 'consultation_fee' => 400.00, 'online_consultation_fee' => 275.00, 'rating_avg' => 4.6, 'total_clients' => 73],
            ['full_name' => 'Can Yıldırım', 'email' => 'can.yildirim@diyetlenio.com', 'phone' => '05383456789', 'title' => 'Dyt. Can Yıldırım', 'specialization' => 'Fonksiyonel Beslenme', 'about_me' => 'Fonksiyonel tıp yaklaşımı ile beslenme programları hazırlıyorum.', 'experience_years' => 7, 'education' => 'Ege Üniversitesi Beslenme ve Diyetetik', 'certifications' => 'Fonksiyonel Beslenme Uzmanı', 'consultation_fee' => 650.00, 'online_consultation_fee' => 450.00, 'rating_avg' => 4.9, 'total_clients' => 94]
        ];

        $created = 0;
        foreach ($demoDietitians as $d) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$d['email']]);
            if ($stmt->fetch()) continue;

            $passwordHash = password_hash('Demo123!', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified, created_at, updated_at) VALUES (?, ?, ?, ?, 'dietitian', 1, 1, NOW(), NOW())");
            $stmt->execute([$d['email'], $passwordHash, $d['full_name'], $d['phone']]);
            $userId = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO dietitian_profiles (user_id, title, specialization, about_me, experience_years, education, certifications, consultation_fee, online_consultation_fee, rating_avg, total_clients, is_verified, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$userId, $d['title'], $d['specialization'], $d['about_me'], $d['experience_years'], $d['education'], $d['certifications'], $d['consultation_fee'], $d['online_consultation_fee'], $d['rating_avg'], $d['total_clients']]);
            $created++;
        }
        $output .= "✅ {$created} diyetisyen eklendi\n\n";

        // Get admin user for articles/recipes
        $stmt = $conn->query("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
        $adminUser = $stmt->fetch();
        $authorId = $adminUser['id'] ?? 1;

        // Demo Articles
        $demoArticles = [
            ['title' => 'Sağlıklı Beslenmenin 10 Altın Kuralı', 'slug' => 'saglikli-beslenmenin-10-altin-kurali', 'excerpt' => 'Sağlıklı bir yaşam için beslenme alışkanlıklarınızı düzenleyin.', 'content' => "Sağlıklı beslenme, yaşam kalitenizi artırmanın en önemli yollarından biridir.\n\n## 1. Dengeli Beslenin\nHer öğünde protein, karbonhidrat ve sağlıklı yağları dengeli şekilde tüketin.", 'category' => 'beslenme', 'tags' => 'sağlıklı beslenme, diyet', 'views' => 250],
            ['title' => 'Kilo Vermek İçin 5 Etkili Strateji', 'slug' => 'kilo-vermek-icin-5-etkili-strateji', 'excerpt' => 'Sağlıklı ve kalıcı kilo kaybı için bilimsel stratejiler.', 'content' => "Kilo vermek bir maraton, sprint değildir.\n\n## 1. Kalori Açığı Oluşturun\nHarcadığınızdan daha az kalori alın.", 'category' => 'kilo-yonetimi', 'tags' => 'kilo verme, zayıflama', 'views' => 350],
            ['title' => 'Spor Öncesi ve Sonrası Beslenme', 'slug' => 'spor-oncesi-ve-sonrasi-beslenme', 'excerpt' => 'Egzersiz performansınızı maksimize edin.', 'content' => "Spor yaparken doğru beslenme performansınızı etkiler.\n\n## Spor Öncesi\n2-3 saat önce kompleks karbonhidrat.", 'category' => 'spor-beslenmesi', 'tags' => 'spor, egzersiz', 'views' => 180],
            ['title' => 'Çocuk Beslenmesinde Dikkat Edilenler', 'slug' => 'cocuk-beslenmesinde-dikkat-edilenler', 'excerpt' => 'Çocuklarınızın sağlıklı büyümesi için öneriler.', 'content' => "Çocukluk döneminde doğru beslenme çok önemlidir.\n\n## Kahvaltı Şart\nGünün en önemli öğünü.", 'category' => 'cocuk-beslenmesi', 'tags' => 'çocuk, büyüme', 'views' => 220],
            ['title' => 'Vejetaryen ve Vegan Beslenme', 'slug' => 'vejetaryen-ve-vegan-beslenme', 'excerpt' => 'Bitkisel beslenmeye geçişte önemli noktalar.', 'content' => "Bitkisel beslenme doğru planlandığında sağlıklıdır.\n\n## Protein Kaynakları\nBaklagiller, tofu, kinoa.", 'category' => 'vejetaryen-vegan', 'tags' => 'vejetaryen, vegan', 'views' => 190]
        ];

        $articlesCreated = 0;
        foreach ($demoArticles as $a) {
            $stmt = $conn->prepare("SELECT id FROM articles WHERE slug = ?");
            $stmt->execute([$a['slug']]);
            if ($stmt->fetch()) continue;

            $stmt = $conn->prepare("INSERT INTO articles (author_id, title, slug, excerpt, content, category, tags, status, views, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'published', ?, NOW(), NOW())");
            $stmt->execute([$authorId, $a['title'], $a['slug'], $a['excerpt'], $a['content'], $a['category'], $a['tags'], $a['views']]);
            $articlesCreated++;
        }
        $output .= "✅ {$articlesCreated} makale eklendi\n\n";

        // Demo Recipes
        $demoRecipes = [
            ['title' => 'Protein Pankek', 'slug' => 'protein-pankek', 'description' => 'Yüksek proteinli kahvaltı.', 'ingredients' => "2 yumurta\n1 scoop protein\n1 muz", 'instructions' => "1. Karıştır\n2. Pişir", 'prep_time' => 5, 'cook_time' => 10, 'servings' => 2, 'calories' => 320, 'protein' => 28, 'carbs' => 35, 'fat' => 8, 'category' => 'Kahvaltı'],
            ['title' => 'Kinoa Salatası', 'slug' => 'kinoa-salatasi', 'description' => 'Protein zengini salata.', 'ingredients' => "1 su bardağı kinoa\n1 salatalık\n2 domates", 'instructions' => "1. Kinoayı haşla\n2. Sebzeleri doğra\n3. Karıştır", 'prep_time' => 15, 'cook_time' => 15, 'servings' => 4, 'calories' => 280, 'protein' => 10, 'carbs' => 38, 'fat' => 12, 'category' => 'Salata'],
            ['title' => 'Fırında Somon', 'slug' => 'firinda-somon', 'description' => 'Omega-3 zengini balık.', 'ingredients' => "4 dilim somon\nZeytinyağı\nLimon", 'instructions' => "1. Marine et\n2. 180°C fırında 20dk", 'prep_time' => 10, 'cook_time' => 20, 'servings' => 4, 'calories' => 320, 'protein' => 35, 'carbs' => 2, 'fat' => 18, 'category' => 'Ana Yemek'],
            ['title' => 'Chia Puding', 'slug' => 'chia-puding', 'description' => 'Sağlıklı tatlı.', 'ingredients' => "3 yemek kaşığı chia\n1 bardak süt\nBal", 'instructions' => "1. Karıştır\n2. Buzdolabında beklet", 'prep_time' => 5, 'cook_time' => 0, 'servings' => 2, 'calories' => 180, 'protein' => 5, 'carbs' => 22, 'fat' => 8, 'category' => 'Tatlı'],
            ['title' => 'Mercimek Çorbası', 'slug' => 'mercimek-corbasi', 'description' => 'Klasik Türk çorbası.', 'ingredients' => "1 bardak kırmızı mercimek\n1 soğan\n1 havuç", 'instructions' => "1. Soğanı kavur\n2. Mercimek ekle\n3. Pişir", 'prep_time' => 10, 'cook_time' => 30, 'servings' => 6, 'calories' => 160, 'protein' => 8, 'carbs' => 28, 'fat' => 3, 'category' => 'Çorba'],
            ['title' => 'Izgara Tavuk', 'slug' => 'izgara-tavuk', 'description' => 'Yüksek protein, düşük yağ.', 'ingredients' => "4 parça tavuk göğsü\nBaharatlar", 'instructions' => "1. Marine et\n2. Izgarada pişir", 'prep_time' => 40, 'cook_time' => 15, 'servings' => 4, 'calories' => 250, 'protein' => 42, 'carbs' => 1, 'fat' => 8, 'category' => 'Ana Yemek']
        ];

        $recipesCreated = 0;
        foreach ($demoRecipes as $r) {
            $stmt = $conn->prepare("SELECT id FROM recipes WHERE slug = ?");
            $stmt->execute([$r['slug']]);
            if ($stmt->fetch()) continue;

            $stmt = $conn->prepare("INSERT INTO recipes (author_id, title, slug, description, ingredients, instructions, prep_time, cook_time, servings, calories, protein, carbs, fat, category, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$authorId, $r['title'], $r['slug'], $r['description'], $r['ingredients'], $r['instructions'], $r['prep_time'], $r['cook_time'], $r['servings'], $r['calories'], $r['protein'], $r['carbs'], $r['fat'], $r['category']]);
            $recipesCreated++;
        }
        $output .= "✅ {$recipesCreated} tarif eklendi\n\n";

        $output .= "🎉 Toplam: {$created} diyetisyen, {$articlesCreated} makale, {$recipesCreated} tarif!";

        $results['success'] = true;
        $results['output'] = $output;
    } catch (Exception $e) {
        $results['success'] = false;
        $results['error'] = $e->getMessage();
    }
}

// Check current state
$dietitiansCount = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'dietitian'")->fetchColumn();
$articlesCount = $conn->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$recipesCount = $conn->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Data Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 40px 0; }
        .setup-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        .stat-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .output-box { background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 14px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <div class="setup-card">
            <h1 class="mb-4">
                <i class="fas fa-database text-primary"></i>
                Demo Data Setup
            </h1>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>UYARI:</strong> Bu dosyayı kullandıktan sonra mutlaka silin!
                <code>rm public/admin/setup-demo.php</code>
            </div>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-info-circle text-info"></i>
                Mevcut Durum
            </h3>

            <div class="row">
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <i class="fas fa-user-md fa-2x text-primary mb-2"></i>
                        <h4><?= $dietitiansCount ?></h4>
                        <small>Diyetisyen</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <i class="fas fa-newspaper fa-2x text-success mb-2"></i>
                        <h4><?= $articlesCount ?></h4>
                        <small>Makale</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box text-center">
                        <i class="fas fa-utensils fa-2x text-warning mb-2"></i>
                        <h4><?= $recipesCount ?></h4>
                        <small>Tarif</small>
                    </div>
                </div>
            </div>

            <?php if ($setupRun && isset($results['output'])): ?>
                <hr>
                <h3 class="mt-4 mb-3">
                    <i class="fas fa-terminal text-success"></i>
                    Kurulum Çıktısı
                </h3>
                <div class="output-box"><?= htmlspecialchars($results['output']) ?></div>
            <?php elseif ($setupRun && isset($results['error'])): ?>
                <hr>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Hata:</strong> <?= htmlspecialchars($results['error']) ?>
                </div>
            <?php endif; ?>

            <hr>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-play-circle text-success"></i>
                Demo Verileri Ekle
            </h3>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Eklenecekler:</strong>
                <ul class="mb-0 mt-2">
                    <li>6 Demo Diyetisyen (farklı uzmanlık alanları)</li>
                    <li>5 Demo Makale (blog yazıları)</li>
                    <li>6 Demo Tarif (sağlıklı yemek tarifleri)</li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="run_setup" class="btn btn-success btn-lg">
                    <i class="fas fa-rocket"></i>
                    Demo Verileri Ekle
                </button>
            </form>

            <hr>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-key text-warning"></i>
                Demo Giriş Bilgileri
            </h3>

            <div class="table-responsive">
                <table class="table table-sm">
                    <tr>
                        <th>Diyetisyen:</th>
                        <td>
                            <code>ayse.yilmaz@diyetlenio.com</code> / <code>Demo123!</code>
                            <span class="badge bg-primary">Spor Beslenmesi</span>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <code>mehmet.demir@diyetlenio.com</code> / <code>Demo123!</code>
                            <span class="badge bg-info">Klinik Beslenme</span>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <code>zeynep.kaya@diyetlenio.com</code> / <code>Demo123!</code>
                            <span class="badge bg-success">Çocuk Beslenmesi</span>
                        </td>
                    </tr>
                </table>
            </div>

            <hr>

            <h3 class="mt-4 mb-3">
                <i class="fas fa-link text-info"></i>
                Test Linkleri
            </h3>

            <div class="list-group">
                <a href="https://www.diyetlenio.com/dietitians.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-user-md"></i> Diyetisyenler Sayfası
                </a>
                <a href="https://www.diyetlenio.com/blog.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-newspaper"></i> Blog Sayfası
                </a>
                <a href="https://www.diyetlenio.com/recipes.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-utensils"></i> Tarifler Sayfası
                </a>
                <a href="https://www.diyetlenio.com/admin/dashboard.php" class="list-group-item list-group-item-action" target="_blank">
                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </a>
            </div>

            <div class="mt-4 text-center text-muted small">
                <i class="fas fa-shield-alt"></i>
                Security Token: <?= $expectedToken ?>
            </div>
        </div>
    </div>
</body>
</html>
