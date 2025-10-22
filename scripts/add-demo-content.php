#!/usr/bin/env php
<?php
/**
 * Demo İçerik Ekle (Makaleler ve Tarifler)
 * Run: php scripts/add-demo-content.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "📝 Demo İçerik Ekleniyor...\n";
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

    // Get admin user ID
    $stmt = $conn->query("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
    $adminUser = $stmt->fetch();

    if (!$adminUser) {
        die("❌ Admin kullanıcısı bulunamadı\n");
    }

    $authorId = $adminUser['id'];

    // ===================================
    // DEMO MAKALELER (ARTICLES)
    // ===================================

    echo "📰 Demo Makaleler Ekleniyor...\n\n";

    $demoArticles = [
        [
            'title' => 'Sağlıklı Beslenmenin 10 Altın Kuralı',
            'slug' => 'saglikli-beslenmenin-10-altin-kurali',
            'excerpt' => 'Sağlıklı bir yaşam için beslenme alışkanlıklarınızı düzenleyin. İşte sağlıklı beslenmenin temel kuralları...',
            'content' => "Sağlıklı beslenme, yaşam kalitenizi artırmanın en önemli yollarından biridir.\n\n## 1. Dengeli Beslenin\nHer öğünde protein, karbonhidrat ve sağlıklı yağları dengeli şekilde tüketin.\n\n## 2. Bol Su İçin\nGünde en az 2-2.5 litre su içmeye özen gösterin.\n\n## 3. Sebze ve Meyve Tüketin\nGünlük 5 porsiyon sebze ve meyve hedefleyin.\n\n## 4. İşlenmiş Gıdalardan Kaçının\nDoğal ve taze gıdaları tercih edin.\n\n## 5. Düzenli Öğün Saatleri\nGünde 3 ana 2-3 ara öğün tüketin.",
            'category' => 'beslenme',
            'tags' => 'sağlıklı beslenme, diyet, yaşam tarzı',
            'status' => 'published',
            'views' => rand(150, 500)
        ],
        [
            'title' => 'Kilo Vermek İçin 5 Etkili Strateji',
            'slug' => 'kilo-vermek-icin-5-etkili-strateji',
            'excerpt' => 'Sağlıklı ve kalıcı kilo kaybı için bilimsel olarak kanıtlanmış 5 strateji.',
            'content' => "Kilo vermek bir maraton, sprint değildir. İşte size yardımcı olacak stratejiler:\n\n## 1. Kalori Açığı Oluşturun\nHarcadığınızdan daha az kalori alın, ancak aşırıya kaçmayın.\n\n## 2. Protein Ağırlıklı Beslenin\nTokluk hissi verir ve kas kaybını önler.\n\n## 3. Düzenli Egzersiz Yapın\nHaftada en az 150 dakika orta tempo egzersiz.\n\n## 4. Uykuya Önem Verin\nYetersiz uyku metabolizmayı yavaşlatır.\n\n## 5. Stres Yönetimi\nStres hormonu kortizol kilo almayı tetikleyebilir.",
            'category' => 'kilo-yonetimi',
            'tags' => 'kilo verme, zayıflama, diyet',
            'status' => 'published',
            'views' => rand(200, 600)
        ],
        [
            'title' => 'Spor Öncesi ve Sonrası Beslenme Rehberi',
            'slug' => 'spor-oncesi-ve-sonrasi-beslenme-rehberi',
            'excerpt' => 'Egzersiz performansınızı maksimize etmek için doğru beslenme stratejileri.',
            'content' => "Spor yaparken doğru beslenme performansınızı doğrudan etkiler.\n\n## Spor Öncesi Beslenme\n- 2-3 saat önce: Kompleks karbonhidrat + protein\n- 30-60 dk önce: Hafif atıştırmalık (muz, yulaf)\n\n## Spor Sonrası Beslenme\n- İlk 30 dakika: Hızlı emilen protein (whey)\n- İlk 2 saat: Tam öğün (tavuk/balık + pilav/patates + salata)\n\n## Hidrasyon\nEgzersiz öncesi, sırası ve sonrasında bol su için.",
            'category' => 'spor-beslenmesi',
            'tags' => 'spor, egzersiz, performans',
            'status' => 'published',
            'views' => rand(100, 400)
        ],
        [
            'title' => 'Çocuk Beslenmesinde Dikkat Edilmesi Gerekenler',
            'slug' => 'cocuk-beslenmesinde-dikkat-edilmesi-gerekenler',
            'excerpt' => 'Çocuklarınızın sağlıklı büyümesi için beslenme önerileri.',
            'content' => "Çocukluk döneminde doğru beslenme, sağlıklı gelişimin temelidir.\n\n## Kahvaltı Şart\nGünün en önemli öğünüdür. Atlamamalı.\n\n## Renkli Tabak\nFarklı renklerde sebze ve meyveler sunun.\n\n## Şekerli İçeceklerden Uzak Durun\nKola, meyve suyu yerine su ve süt tercih edin.\n\n## Örnek Olun\nÇocuklar yetişkinleri taklit eder. Siz de sağlıklı beslenin.\n\n## Zorlamayın\nAç kalmayacaklarından emin olun ama yemeye zorlamayın.",
            'category' => 'cocuk-beslenmesi',
            'tags' => 'çocuk, bebek, büyüme',
            'status' => 'published',
            'views' => rand(120, 450)
        ],
        [
            'title' => 'Vejetaryen ve Vegan Beslenme: Bilmeniz Gerekenler',
            'slug' => 'vejetaryen-ve-vegan-beslenme-bilmeniz-gerekenler',
            'excerpt' => 'Bitkisel beslenmeye geçişte dikkat edilmesi gereken önemli noktalar.',
            'content' => "Bitkisel beslenme doğru planlandığında çok sağlıklıdır.\n\n## Protein Kaynakları\n- Baklagiller (mercimek, nohut, fasulye)\n- Tofu ve tempeh\n- Kuruyemişler ve tohumlar\n- Kinoa\n\n## B12 Vitamini\nMutlaka takviye alın, bitkisel kaynaklarda yok.\n\n## Demir\nC vitamini ile birlikte tüketin (emilimi artırır).\n\n## Omega-3\nKeten tohumu, chia, ceviz tüketin.\n\n## Dengeli Beslenin\nSadece ekmek-makarna yemeyin, çeşitlendirin.",
            'category' => 'vejetaryen-vegan',
            'tags' => 'vejetaryen, vegan, bitkisel',
            'status' => 'published',
            'views' => rand(90, 350)
        ]
    ];

    $articlesCreated = 0;

    foreach ($demoArticles as $article) {
        // Check if exists
        $stmt = $conn->prepare("SELECT id FROM articles WHERE slug = ?");
        $stmt->execute([$article['slug']]);

        if ($stmt->fetch()) {
            echo "   ⊘ '{$article['title']}' zaten var\n";
            continue;
        }

        $stmt = $conn->prepare("
            INSERT INTO articles (
                author_id, title, slug, excerpt, content, category, tags,
                status, views, created_at, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $authorId,
            $article['title'],
            $article['slug'],
            $article['excerpt'],
            $article['content'],
            $article['category'],
            $article['tags'],
            $article['status'],
            $article['views']
        ]);

        echo "   ✅ '{$article['title']}' eklendi\n";
        $articlesCreated++;
    }

    echo "\n📊 Makaleler: {$articlesCreated} eklendi\n\n";

    // ===================================
    // DEMO TARİFLER (RECIPES)
    // ===================================

    echo "🍳 Demo Tarifler Ekleniyor...\n\n";

    $demoRecipes = [
        [
            'title' => 'Protein Pankek',
            'slug' => 'protein-pankek',
            'description' => 'Sabah kahvaltınız için yüksek proteinli, sağlıklı pankek tarifi.',
            'ingredients' => "2 yumurta\n1 scoop protein tozu (vanilya)\n1 muz\n1/2 çay kaşığı kabartma tozu\n1 çay kaşığı tarçın",
            'instructions' => "1. Tüm malzemeleri blenderda karıştırın\n2. Yapışmaz tavada pişirin\n3. Her iki tarafı da altın sarısı olana kadar çevirin\n4. Üzerine meyve ve bal ekleyerek servis yapın",
            'prep_time' => 5,
            'cook_time' => 10,
            'servings' => 2,
            'calories' => 320,
            'protein' => 28,
            'carbs' => 35,
            'fat' => 8,
            'category' => 'Kahvaltı',
            'is_published' => 1
        ],
        [
            'title' => 'Kinoa Salatası',
            'slug' => 'kinoa-salatasi',
            'description' => 'Protein açısından zengin, doyurucu ve lezzetli kinoa salatası.',
            'ingredients' => "1 su bardağı kinoa\n1 salatalık\n2 domates\n1 avokado\n1/2 demet maydanoz\nZeytinyağı ve limon",
            'instructions' => "1. Kinoayı haşlayın ve soğumaya bırakın\n2. Sebzeleri küp şeklinde doğrayın\n3. Tüm malzemeleri karıştırın\n4. Zeytinyağı ve limon ile tatlandırın",
            'prep_time' => 15,
            'cook_time' => 15,
            'servings' => 4,
            'calories' => 280,
            'protein' => 10,
            'carbs' => 38,
            'fat' => 12,
            'category' => 'Salata',
            'is_published' => 1
        ],
        [
            'title' => 'Fırında Somon',
            'slug' => 'firinda-somon',
            'description' => 'Omega-3 açısından zengin, kolay ve lezzetli somon tarifi.',
            'ingredients' => "4 dilim somon\n2 diş sarımsak\nZeytinyağı\nLimon\nTaze biberiye\nTuz, karabiber",
            'instructions' => "1. Somonu fırın tepsisine yerleştirin\n2. Zeytinyağı, sarımsak ve baharatlarla marine edin\n3. 180 derece fırında 20 dakika pişirin\n4. Limon ile servis yapın",
            'prep_time' => 10,
            'cook_time' => 20,
            'servings' => 4,
            'calories' => 320,
            'protein' => 35,
            'carbs' => 2,
            'fat' => 18,
            'category' => 'Ana Yemek',
            'is_published' => 1
        ],
        [
            'title' => 'Chia Puding',
            'slug' => 'chia-puding',
            'description' => 'Sağlıklı atıştırmalık veya kahvaltı için chia tohumlu puding.',
            'ingredients' => "3 yemek kaşığı chia tohumu\n1 su bardağı badem sütü\n1 yemek kaşığı bal\n1/2 çay kaşığı vanilya\nTaze meyveler (üzeri için)",
            'instructions' => "1. Chia, süt, bal ve vanilyayı karıştırın\n2. Buzdolabında 4 saat ya da bir gece bekletin\n3. Üzerine taze meyve ekleyerek servis yapın",
            'prep_time' => 5,
            'cook_time' => 0,
            'servings' => 2,
            'calories' => 180,
            'protein' => 5,
            'carbs' => 22,
            'fat' => 8,
            'category' => 'Tatlı',
            'is_published' => 1
        ],
        [
            'title' => 'Mercimek Çorbası',
            'slug' => 'mercimek-corbasi',
            'description' => 'Klasik Türk mercimek çorbası, proteinli ve doyurucu.',
            'ingredients' => "1 su bardağı kırmızı mercimek\n1 soğan\n1 havuç\n1 patates\n1 yemek kaşığı salça\nZeytinyağı, tuz, kimyon",
            'instructions' => "1. Soğanı zeytinyağında kavurun\n2. Salça, havuç ve patatesi ekleyin\n3. Mercimeği ve suyu ilave edin\n4. Yumuşayana kadar pişirin ve blenderdan geçirin\n5. Baharatlarla tatlandırıp servis yapın",
            'prep_time' => 10,
            'cook_time' => 30,
            'servings' => 6,
            'calories' => 160,
            'protein' => 8,
            'carbs' => 28,
            'fat' => 3,
            'category' => 'Çorba',
            'is_published' => 1
        ],
        [
            'title' => 'Izgara Tavuk Göğsü',
            'slug' => 'izgara-tavuk-gogsu',
            'description' => 'Yüksek protein, düşük yağ, sporcular için ideal bir ana yemek.',
            'ingredients' => "4 parça tavuk göğsü\n2 yemek kaşığı zeytinyağı\n1 çay kaşığı kekik\nSarımsak tozu\nTuz, karabiber",
            'instructions' => "1. Tavukları ince dövün\n2. Zeytinyağı ve baharatlarla marine edin\n3. 30 dakika bekletin\n4. Izgarada veya tavada her iki tarafını pişirin\n5. Salata ve pilav ile servis yapın",
            'prep_time' => 40,
            'cook_time' => 15,
            'servings' => 4,
            'calories' => 250,
            'protein' => 42,
            'carbs' => 1,
            'fat' => 8,
            'category' => 'Ana Yemek',
            'is_published' => 1
        ]
    ];

    $recipesCreated = 0;

    foreach ($demoRecipes as $recipe) {
        // Check if exists
        $stmt = $conn->prepare("SELECT id FROM recipes WHERE slug = ?");
        $stmt->execute([$recipe['slug']]);

        if ($stmt->fetch()) {
            echo "   ⊘ '{$recipe['title']}' zaten var\n";
            continue;
        }

        $stmt = $conn->prepare("
            INSERT INTO recipes (
                author_id, title, slug, description, ingredients, instructions,
                prep_time, cook_time, servings, calories, protein, carbs, fat,
                category, is_published, created_at, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $authorId,
            $recipe['title'],
            $recipe['slug'],
            $recipe['description'],
            $recipe['ingredients'],
            $recipe['instructions'],
            $recipe['prep_time'],
            $recipe['cook_time'],
            $recipe['servings'],
            $recipe['calories'],
            $recipe['protein'],
            $recipe['carbs'],
            $recipe['fat'],
            $recipe['category'],
            $recipe['is_published']
        ]);

        echo "   ✅ '{$recipe['title']}' eklendi\n";
        $recipesCreated++;
    }

    echo "\n📊 Tarifler: {$recipesCreated} eklendi\n\n";

    echo "═══════════════════════════════════════\n";
    echo "🎉 TAMAMLANDI!\n";
    echo "═══════════════════════════════════════\n\n";

    echo "📊 Toplam:\n";
    echo "   📰 Makaleler: {$articlesCreated}\n";
    echo "   🍳 Tarifler: {$recipesCreated}\n\n";

    echo "🌐 Test için:\n";
    echo "   - Makaleler: http://localhost:8080/blog.php\n";
    echo "   - Tarifler: http://localhost:8080/recipes.php\n";
    echo "   - Admin Panel: http://localhost:8080/admin/articles.php\n\n";

} catch (Exception $e) {
    echo "\n❌ Hata: " . $e->getMessage() . "\n";
    echo "\nDetay:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
