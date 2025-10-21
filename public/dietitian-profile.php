<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("
    SELECT u.*, d.*
    FROM users u
    INNER JOIN dietitians d ON u.id = d.user_id
    WHERE u.id = ? AND u.user_type = 'dietitian' AND u.status = 'active'
");
$stmt->execute([$id]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    header('Location: /');
    exit;
}

// Reviews
$reviewStmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE dietitian_id = ?");
$reviewStmt->execute([$id]);
$rating = $reviewStmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($dietitian['first_name'] . ' ' . $dietitian['last_name']) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 1rem 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: #0ea5e9 !important; }
        .profile-header { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); padding: 60px 0; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; border: 5px solid white; background: white; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #0ea5e9; margin: 0 auto 20px; }
        .profile-name { font-size: 2rem; font-weight: 800; color: white; margin-bottom: 10px; }
        .profile-specialty { color: rgba(255,255,255,0.9); font-size: 1.1rem; margin-bottom: 20px; }
        .rating { color: #fbbf24; font-size: 1.2rem; }
        .info-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .info-card h3 { font-size: 1.5rem; font-weight: 600; margin-bottom: 20px; }
        .footer { background: #1e293b; color: white; padding: 40px 0; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-heartbeat me-2"></i>Diyetlenio</a>
            <div class="ms-auto">
                <a href="/" class="btn btn-outline-primary me-2">Ana Sayfa</a>
                <?php if ($auth->check()): ?>
                    <a href="/client/dashboard.php" class="btn btn-primary">Panel</a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-primary">Giriş Yap</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header class="profile-header text-center">
        <div class="container">
            <div class="profile-photo">
                <?php if ($dietitian['profile_photo']): ?>
                    <img src="<?= upload($dietitian['profile_photo']) ?>" alt="<?= clean($dietitian['first_name']) ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <h1 class="profile-name">Dyt. <?= clean($dietitian['first_name'] . ' ' . $dietitian['last_name']) ?></h1>
            <p class="profile-specialty"><?= clean($dietitian['specialization'] ?? 'Diyetisyen') ?></p>
            <div class="rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star<?= $i <= round($rating['avg_rating']) ? '' : '-o' ?>"></i>
                <?php endfor; ?>
                <span class="text-white ms-2"><?= number_format($rating['avg_rating'], 1) ?> (<?= $rating['total'] ?> değerlendirme)</span>
            </div>
            <div class="mt-4">
                <?php if ($auth->check()): ?>
                    <a href="/client/appointments.php?dietitian_id=<?= $id ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-calendar-plus me-2"></i>Randevu Al
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calendar-plus me-2"></i>Randevu İçin Giriş Yapın
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="info-card">
                    <h3>Hakkında</h3>
                    <p><?= nl2br(clean($dietitian['bio'] ?? 'Hakkında bilgisi eklenmemiş.')) ?></p>
                </div>

                <div class="info-card">
                    <h3>Deneyim</h3>
                    <p><?= clean($dietitian['experience_years'] ?? 0) ?> yıllık deneyim</p>
                    <p><?= nl2br(clean($dietitian['experience'] ?? 'Deneyim bilgisi eklenmemiş.')) ?></p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info-card">
                    <h3>Bilgiler</h3>
                    <p><strong>Eğitim:</strong><br><?= clean($dietitian['education'] ?? 'Belirtilmemiş') ?></p>
                    <p><strong>Uzmanlık:</strong><br><?= clean($dietitian['specialization'] ?? 'Genel') ?></p>
                    <p><strong>Randevu Ücreti:</strong><br><strong class="text-primary"><?= number_format($dietitian['hourly_rate'] ?? 0, 2) ?> ₺</strong></p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container"><p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p></div>
    </footer>
</body>
</html>
