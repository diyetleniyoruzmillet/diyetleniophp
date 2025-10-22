<?php
/**
 * Diyetlenio - Geri Bildirim
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Geri Bildirim';

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirect('/feedback');
    }

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $type = sanitize($_POST['type'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if ($name && $email && $type && $message) {
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO feedback (name, email, type, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $email, $type, $message]);

            setFlash('success', 'Geri bildiriminiz başarıyla gönderildi. Teşekkür ederiz!');
            redirect('/feedback');
        } catch (Exception $e) {
            error_log('Feedback error: ' . $e->getMessage());
            setFlash('error', 'Geri bildiriminiz gönderilirken bir hata oluştu. Lütfen tekrar deneyin.');
        }
    } else {
        setFlash('error', 'Lütfen tüm alanları doldurun.');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="Diyetlenio hakkındaki görüş ve önerilerinizi bizimle paylaşın. Sizin için daha iyi bir deneyim oluşturmak istiyoruz.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero {
            padding: 100px 0 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.3rem;
            color: #718096;
            max-width: 700px;
            margin: 0 auto;
        }

        .feedback-section {
            padding: 60px 0 100px;
        }

        .feedback-form {
            background: white;
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .form-control, .form-select {
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border: none;
            color: white;
            padding: 16px 50px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.4);
            color: white;
        }

        .feature-boxes {
            margin-top: 60px;
        }

        .feature-box {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
        }

        .feature-box i {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-box h4 {
            font-weight: 700;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .feature-box p {
            color: #718096;
            margin: 0;
        }

        footer {
            background: #1a202c;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            .hero p {
                font-size: 1.1rem;
            }
            .feedback-form {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat me-2"></i>Diyetlenio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact">İletişim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/feedback">Geri Bildirim</a>
                    </li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/<?= $auth->user()->getUserType() ?>/dashboard.php">
                                Panel
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Fikirleriniz Bizim İçin Değerli</h1>
            <p>Görüş, öneri ve şikayetlerinizi bizimle paylaşın. Sizin için daha iyi bir deneyim oluşturalım.</p>
        </div>
    </section>

    <!-- Feedback Form -->
    <section class="feedback-section">
        <div class="container">
            <?php if (hasFlash()): ?>
                <?php if ($msg = getFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= clean($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($msg = getFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= clean($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="feedback-form">
                <form method="POST" action="/feedback">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <div class="mb-4">
                        <label for="name" class="form-label">Adınız Soyadınız *</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               placeholder="Ad Soyad" value="<?= $auth->check() ? clean($auth->user()->getFullName()) : '' ?>">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label">E-posta Adresiniz *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               placeholder="ornek@email.com" value="<?= $auth->check() ? clean($auth->user()->getEmail()) : '' ?>">
                    </div>

                    <div class="mb-4">
                        <label for="type" class="form-label">Geri Bildirim Türü *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Seçiniz</option>
                            <option value="suggestion">Öneri</option>
                            <option value="complaint">Şikayet</option>
                            <option value="bug">Hata Bildirimi</option>
                            <option value="compliment">Teşekkür</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label">Mesajınız *</label>
                        <textarea class="form-control" id="message" name="message" required
                                  placeholder="Lütfen detaylı açıklama yapın..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-paper-plane me-2"></i>Gönder
                    </button>
                </form>
            </div>

            <!-- Feature Boxes -->
            <div class="feature-boxes">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="feature-box">
                            <i class="fas fa-reply"></i>
                            <h4>Hızlı Yanıt</h4>
                            <p>Geri bildirimleriniz 24 saat içinde değerlendirilir</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-box">
                            <i class="fas fa-user-shield"></i>
                            <h4>Gizlilik</h4>
                            <p>Bilgileriniz gizli tutulur ve korunur</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-box">
                            <i class="fas fa-chart-line"></i>
                            <h4>Sürekli İyileştirme</h4>
                            <p>Geri bildirimlerinizle platformu geliştiriyoruz</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-2"><strong><i class="fas fa-heartbeat me-2"></i>Diyetlenio</strong></p>
            <p class="text-white-50 mb-0">&copy; <?= date('Y') ?> Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
