<?php
/**
 * Web-based Demo Dietitians Setup
 */

// Security token
$requiredToken = md5('add-demo-dietitians-2025-' . date('Y-m-d'));
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $requiredToken) {
    die("🔒 Access Denied. Invalid token.<br><br>Today's token: <strong>{$requiredToken}</strong>");
}

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();
$added = [];
$skipped = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dietitians'])) {
    // Demo diyetisyenler
    $dietitians = [
        [
            'full_name' => 'Dr. Ayşe Yılmaz',
            'email' => 'ayse.yilmaz@diyetlenio.com',
            'password' => 'Demo123!',
            'phone' => '0532 111 11 11',
            'title' => 'Diyetisyen, Beslenme Uzmanı',
            'specialization' => 'Spor Beslenmesi',
            'about_me' => 'Spor beslenmesi alanında 8 yıllık deneyime sahip, çok sayıda profesyonel sporcu ile çalışmış uzman diyetisyenim.',
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
            'about_me' => 'Klinik beslenme ve metabolik hastalıklar konusunda uzmanım. Diyabet, kolesterol, tiroid hastalıkları ile ilgili profesyonel destek sağlıyorum.',
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
            'about_me' => 'Bebek ve çocuk beslenmesi konusunda uzmanlaşmış diyetisyenim. Çocuğunuzun sağlıklı büyümesi için yanınızdayım.',
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
            'about_me' => 'Obezite tedavisi ve sağlıklı kilo kaybı konusunda uzmanım. Sürdürülebilir kilo kaybı programları ile hedeflerinize ulaşmanıza yardımcı oluyorum.',
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
            'about_me' => 'Bitkisel beslenme konusunda uzmanım. Vejetaryen ve vegan yaşam tarzı için dengeli beslenme programları hazırlıyorum.',
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
            'about_me' => 'Fonksiyonel beslenme ve bağırsak sağlığı konusunda uzmanım. Kişiselleştirilmiş beslenme programları sunuyorum.',
            'education' => 'Başkent Üniversitesi Beslenme ve Diyetetik',
            'certifications' => 'Fonksiyonel Beslenme Sertifikası, Probiyotik Tedavi Eğitimi',
            'experience_years' => 7,
            'consultation_fee' => 500,
            'rating_avg' => 4.7,
            'rating_count' => 42,
            'total_clients' => 95
        ]
    ];

    foreach ($dietitians as $dietitian) {
        try {
            // Email kontrolü
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$dietitian['email']]);
            if ($stmt->fetch()) {
                $skipped[] = $dietitian['full_name'];
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

            $added[] = $dietitian['full_name'];
        } catch (Exception $e) {
            error_log('Error adding dietitian: ' . $e->getMessage());
        }
    }

    $success = true;
}

// Mevcut diyetisyenleri kontrol et
$stmt = $conn->query("
    SELECT u.full_name, u.email, dp.specialization, u.is_active, dp.is_approved
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.user_type = 'dietitian'
    ORDER BY u.created_at DESC
");
$existingDietitians = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Diyetisyen Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 30px;
        }
        .btn-add {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            font-weight: bold;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            box-shadow: 0 10px 30px rgba(67, 233, 123, 0.4);
        }
        .success-box {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1000px;">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">
                    <i class="fas fa-user-md me-3"></i>Demo Diyetisyen Ekleme
                </h2>
            </div>
            <div class="card-body p-4">

                <?php if ($success): ?>
                    <div class="success-box">
                        <h3><i class="fas fa-check-circle me-2"></i>Başarılı!</h3>
                        <?php if (count($added) > 0): ?>
                            <p><strong>Eklenen Diyetisyenler (<?= count($added) ?>):</strong></p>
                            <ul>
                                <?php foreach ($added as $name): ?>
                                    <li><?= $name ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (count($skipped) > 0): ?>
                            <p><strong>Zaten Mevcut (<?= count($skipped) ?>):</strong></p>
                            <ul>
                                <?php foreach ($skipped as $name): ?>
                                    <li><?= $name ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Eklenecek Diyetisyenler</h5>
                    <ul class="mb-0">
                        <li>Dr. Ayşe Yılmaz - Spor Beslenmesi</li>
                        <li>Mehmet Demir - Klinik Beslenme</li>
                        <li>Zeynep Kaya - Çocuk Beslenmesi</li>
                        <li>Ahmet Öztürk - Obezite ve Kilo Yönetimi</li>
                        <li>Elif Şahin - Vejetaryen ve Vegan Beslenme</li>
                        <li>Can Yıldırım - Fonksiyonel Beslenme</li>
                    </ul>
                </div>

                <h5 class="mt-4">Mevcut Diyetisyenler (<?= count($existingDietitians) ?>)</h5>
                <?php if (count($existingDietitians) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ad Soyad</th>
                                    <th>Email</th>
                                    <th>Uzmanlık</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existingDietitians as $d): ?>
                                <tr>
                                    <td><?= clean($d['full_name']) ?></td>
                                    <td><?= clean($d['email']) ?></td>
                                    <td><?= clean($d['specialization']) ?></td>
                                    <td>
                                        <?php if ($d['is_active'] && $d['is_approved']): ?>
                                            <span class="badge bg-success">Aktif & Onaylı</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Henüz diyetisyen bulunmuyor.</p>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <form method="POST">
                        <button type="submit" name="add_dietitians" class="btn btn-add">
                            <i class="fas fa-plus-circle me-2"></i>
                            6 Demo Diyetisyen Ekle
                        </button>
                    </form>
                </div>

                <div class="alert alert-success mt-4">
                    <h5><i class="fas fa-key me-2"></i>Giriş Bilgileri</h5>
                    <p class="mb-0">
                        <strong>Tüm demo diyetisyenler için:</strong><br>
                        Şifre: <code>Demo123!</code><br>
                        Email: <code>[isim.soyisim]@diyetlenio.com</code>
                    </p>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <a href="/dietitians.php" class="btn btn-primary me-2">
                        <i class="fas fa-users me-2"></i>Diyetisyen Listesi
                    </a>
                    <a href="/admin/dietitians.php" class="btn btn-secondary">
                        <i class="fas fa-cog me-2"></i>Admin Paneli
                    </a>
                </div>

            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-white">
                <i class="fas fa-lock me-1"></i>
                Bu dosyayı kullandıktan sonra silin:
                <code style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 5px;">
                    rm public/add-demo-dietitians.php
                </code>
            </small>
        </div>
    </div>
</body>
</html>
