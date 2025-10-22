<?php
/**
 * Diyetlenio - İletişim Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Validasyon
        if (empty($name)) {
            $errors[] = 'İsim gereklidir.';
        }

        if (empty($email)) {
            $errors[] = 'Email adresi gereklidir.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir email adresi girin.';
        }

        // Telefon opsiyonel ama girilmişse formatı kontrol et
        if (!empty($phone) && !preg_match('/^[0-9\s\+\-\(\)]+$/', $phone)) {
            $errors[] = 'Geçerli bir telefon numarası girin.';
        }

        if (empty($subject)) {
            $errors[] = 'Konu gereklidir.';
        }

        if (empty($message)) {
            $errors[] = 'Mesaj gereklidir.';
        }

        // Mesaj kaydetme
        if (empty($errors)) {
            try {
                $conn = $db->getConnection();
                $stmt = $conn->prepare("
                    INSERT INTO contact_messages (name, email, phone, subject, message, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $email, $phone, $subject, $message]);

                $success = true;

                // Admin'e bildirim gönder
                Mail::sendContactNotification([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $message
                ]);

            } catch (Exception $e) {
                error_log('Contact form error: ' . $e->getMessage());
                $errors[] = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
            }
        }
    }
}

$pageTitle = 'İletişim';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0ea5e9 !important;
        }

        .hero {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .section {
            padding: 80px 0;
        }

        .contact-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .contact-info-item {
            display: flex;
            align-items: start;
            margin-bottom: 30px;
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .contact-info-content {
            margin-left: 20px;
        }

        .contact-info-content h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .contact-info-content p {
            color: #718096;
            margin: 0;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(14, 165, 233, 0.5);
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 25px;
        }

        .footer {
            background: #1e293b;
            color: white;
            padding: 40px 0;
            text-align: center;
        }

        .footer a {
            color: #0ea5e9;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
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
                        <a class="nav-link" href="/dietitians.php">Diyetisyenler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/recipes.php">Tarifler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/contact.php">İletişim</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="/login.php">Giriş Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>İletişim</h1>
            <p>Size nasıl yardımcı olabiliriz? Bizimle iletişime geçin</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="contact-card">
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Telefon</h4>
                                <p>+90 (212) 123 45 67</p>
                                <p class="text-muted small">Pazartesi-Cuma 09:00-18:00</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Email</h4>
                                <p>info@diyetlenio.com</p>
                                <p>destek@diyetlenio.com</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Adres</h4>
                                <p>Maslak Mahallesi<br>Büyükdere Cad. No:123<br>Sarıyer, İstanbul</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Çalışma Saatleri</h4>
                                <p>Pazartesi - Cuma: 09:00 - 18:00</p>
                                <p>Cumartesi: 10:00 - 16:00</p>
                                <p>Pazar: Kapalı</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="contact-card">
                        <h2 class="mb-4">Bize Mesaj Gönderin</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php foreach ($errors as $error): ?>
                                    <div><?= clean($error) ?></div>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-user me-2"></i>Ad Soyad
                                        </label>
                                        <input
                                            type="text"
                                            name="name"
                                            class="form-control"
                                            id="name"
                                            value="<?= clean($_POST['name'] ?? '') ?>"
                                            required
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-2"></i>Email Adresi
                                        </label>
                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control"
                                            id="email"
                                            value="<?= clean($_POST['email'] ?? '') ?>"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Telefon Numarası
                                        <span class="text-muted small">(Opsiyonel)</span>
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        class="form-control"
                                        id="phone"
                                        value="<?= clean($_POST['phone'] ?? '') ?>"
                                        placeholder="+90 (5XX) XXX XX XX"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Konu
                                    </label>
                                    <select name="subject" class="form-select" id="subject" required>
                                        <option value="">Konu seçin</option>
                                        <option value="Genel Bilgi">Genel Bilgi</option>
                                        <option value="Teknik Destek">Teknik Destek</option>
                                        <option value="Diyetisyen Başvurusu">Diyetisyen Başvurusu</option>
                                        <option value="Ödeme Sorunu">Ödeme Sorunu</option>
                                        <option value="Şikayet">Şikayet</option>
                                        <option value="Öneri">Öneri</option>
                                        <option value="Diğer">Diğer</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="message" class="form-label">
                                        <i class="fas fa-comment me-2"></i>Mesajınız
                                    </label>
                                    <textarea
                                        name="message"
                                        class="form-control"
                                        id="message"
                                        required
                                    ><?= clean($_POST['message'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-paper-plane me-2"></i>Gönder
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Diyetlenio. Tüm hakları saklıdır.</p>
            <div class="mt-3">
                <a href="/about.php" class="me-3">Hakkımızda</a>
                <a href="/privacy-policy.php" class="me-3">Gizlilik Politikası</a>
                <a href="/terms.php">Kullanım Şartları</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
