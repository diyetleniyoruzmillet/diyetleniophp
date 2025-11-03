<?php
/**
 * Diyetlenio - İletişim Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting kontrolü (3 mesaj / 10 dakika)
    $rateLimiter = new RateLimiter($db);
    if ($rateLimiter->tooManyAttempts('contact_form', null, 3, 10)) {
        $remainingSeconds = $rateLimiter->availableIn('contact_form', null, 10);
        $remainingMinutes = ceil($remainingSeconds / 60);
        $errors[] = "Çok fazla mesaj gönderdiniz. Lütfen {$remainingMinutes} dakika sonra tekrar deneyin.";
    }
    // CSRF kontrolü
    elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    }
    else {
        $name = sanitizeString($_POST['name'] ?? '', 100);
        $email = sanitizeString($_POST['email'] ?? '', 100);
        $phone = sanitizeString($_POST['phone'] ?? '', 20);
        $subject = sanitizeString($_POST['subject'] ?? '', 200);
        $message = sanitizeString($_POST['message'] ?? '', 2000);

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

                // Rate limit'e kaydet (başarılı gönderim)
                $rateLimiter->hit(hash('sha256', 'contact_form|ip_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')), 10);

                $success = true;

                // Admin'e bildirim gönder (hata olsa bile devam et)
                try {
                    Mail::sendContactNotification([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'subject' => $subject,
                        'message' => $message
                    ]);
                } catch (Exception $mailError) {
                    // Mail gönderim hatası - logla ama kullanıcıya gösterme
                    error_log('Contact mail error: ' . $mailError->getMessage());
                }

            } catch (Exception $e) {
                error_log('Contact form error: ' . $e->getMessage());
                $errors[] = 'Mesaj gönderilirken bir hata oluştu. Lütfen tekrar deneyin.';
            }
        }
    }
}

$pageTitle = 'İletişim';
$metaDescription = 'Diyetlenio ile iletişime geçin. Size nasıl yardımcı olabiliriz?';
include __DIR__ . '/../includes/partials/header.php';
?>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            --primary-color: #0ea5e9;
            --secondary-color: #06b6d4;
            --dark-text: #1e293b;
            --medium-text: #475569;
            --light-text: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --error-color: #ef4444;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-light);
            color: var(--dark-text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: var(--shadow-sm);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            box-shadow: var(--shadow-md);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: var(--medium-text) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--primary-gradient);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 80%;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero {
            background: var(--primary-gradient);
            color: white;
            padding: 120px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            font-weight: 400;
            max-width: 600px;
            margin: 0 auto;
        }

        .hero-decoration {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            filter: blur(60px);
        }

        .hero-decoration:nth-child(1) {
            top: -200px;
            left: -200px;
        }

        .hero-decoration:nth-child(2) {
            bottom: -200px;
            right: -200px;
        }

        /* Section Styles */
        .contact-section {
            padding: 80px 0;
            position: relative;
        }

        /* Contact Info Cards */
        .contact-info-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            height: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .contact-info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .contact-info-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .contact-info-card:hover::before {
            transform: scaleX(1);
        }

        .contact-info-item {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .contact-info-item:last-child {
            border-bottom: none;
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
            transition: all 0.3s ease;
        }

        .contact-info-item:hover .contact-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .contact-info-content h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }

        .contact-info-content p {
            color: var(--medium-text);
            margin: 0.25rem 0;
            font-size: 0.95rem;
        }

        .contact-info-content a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-info-content a:hover {
            color: var(--secondary-color);
        }

        /* Contact Form Card */
        .contact-form-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-md);
            animation: fadeInRight 0.8s ease-out;
        }

        .contact-form-card h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }

        .contact-form-card .subtitle {
            color: var(--light-text);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        /* Floating Label Form */
        .form-floating-custom {
            position: relative;
            margin-bottom: 1.75rem;
        }

        .form-floating-custom label {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: var(--light-text);
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            background: white;
            padding: 0 0.5rem;
        }

        .form-floating-custom input,
        .form-floating-custom textarea,
        .form-floating-custom select {
            width: 100%;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 1rem;
            font-size: 1rem;
            color: var(--dark-text);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }

        .form-floating-custom input:focus,
        .form-floating-custom textarea:focus,
        .form-floating-custom select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        .form-floating-custom input:focus + label,
        .form-floating-custom input:not(:placeholder-shown) + label,
        .form-floating-custom textarea:focus + label,
        .form-floating-custom textarea:not(:placeholder-shown) + label,
        .form-floating-custom select:focus + label,
        .form-floating-custom select:not([value=""]) + label {
            top: -0.625rem;
            left: 0.75rem;
            font-size: 0.75rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-floating-custom input.is-valid,
        .form-floating-custom textarea.is-valid,
        .form-floating-custom select.is-valid {
            border-color: var(--success-color);
        }

        .form-floating-custom input.is-invalid,
        .form-floating-custom textarea.is-invalid,
        .form-floating-custom select.is-invalid {
            border-color: var(--error-color);
        }

        .form-floating-custom textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-floating-custom .form-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            pointer-events: none;
        }

        /* Submit Button */
        .btn-submit {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: var(--radius-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(14, 165, 233, 0.4);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(14, 165, 233, 0.6);
        }

        .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Alert Messages */
        .alert-modern {
            border-radius: var(--radius-md);
            border: none;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.4s ease-out;
            box-shadow: var(--shadow-sm);
        }

        .alert-modern i {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .alert-modern .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        /* Map Section */
        .map-section {
            margin-top: 2rem;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            height: 400px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .map-placeholder {
            text-align: center;
            padding: 3rem;
        }

        .map-placeholder i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .map-placeholder h3 {
            color: var(--dark-text);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .map-placeholder p {
            color: var(--medium-text);
        }

        /* FAQ Section */
        .faq-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 3rem;
            margin-top: 4rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 1;
        }

        .faq-section h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 2rem;
            text-align: center;
        }

        .faq-item {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .faq-question:hover {
            color: var(--primary-color);
        }

        .faq-question i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .faq-question h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .faq-answer {
            color: var(--medium-text);
            margin: 1rem 0 0 2.5rem;
            line-height: 1.7;
        }

        /* Social Media Section */
        .social-section {
            text-align: center;
            padding: 3rem 0;
            position: relative;
            z-index: 10;
        }

        .social-section h3 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 1.5rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .social-link {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }

        .social-link:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: var(--shadow-md);
        }

        .social-link.facebook {
            background: linear-gradient(135deg, #1877f2 0%, #0d65d9 100%);
        }

        .social-link.twitter {
            background: linear-gradient(135deg, #1da1f2 0%, #0c85d0 100%);
        }

        .social-link.instagram {
            background: linear-gradient(135deg, #e4405f 0%, #c13584 100%);
        }

        .social-link.linkedin {
            background: linear-gradient(135deg, #0077b5 0%, #005582 100%);
        }

        .social-link.youtube {
            background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
        }

        /* Trust Badges */
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            margin: 3rem 0;
        }

        .trust-badge {
            text-align: center;
        }

        .trust-badge i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }

        .trust-badge h5 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0.25rem;
        }

        .trust-badge p {
            font-size: 0.875rem;
            color: var(--light-text);
            margin: 0;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .contact-form-card,
            .contact-info-card {
                padding: 2rem;
            }

            .faq-section {
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .contact-section {
                padding: 40px 0;
            }

            .contact-form-card h2,
            .faq-section h2 {
                font-size: 1.5rem;
            }

            .trust-badges {
                gap: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-decoration"></div>
        <div class="hero-decoration"></div>
        <div class="container">
            <div class="hero-content">
                <h1>İletişime Geçin</h1>
                <p>Size nasıl yardımcı olabiliriz? Sorularınız için buradayız</p>
            </div>
        </div>
    </section>

    <!-- Trust Badges -->
    <div class="container">
        <div class="trust-badges">
            <div class="trust-badge">
                <i class="fas fa-shield-alt"></i>
                <h5>Güvenli İletişim</h5>
                <p>Bilgileriniz korunur</p>
            </div>
            <div class="trust-badge">
                <i class="fas fa-clock"></i>
                <h5>Hızlı Yanıt</h5>
                <p>24 saat içinde dönüş</p>
            </div>
            <div class="trust-badge">
                <i class="fas fa-headset"></i>
                <h5>7/24 Destek</h5>
                <p>Her zaman yanınızda</p>
            </div>
            <div class="trust-badge">
                <i class="fas fa-user-check"></i>
                <h5>Uzman Ekip</h5>
                <p>Deneyimli kadro</p>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="contact-info-card">
                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Telefon</h4>
                                <p><a href="tel:+902121234567">+90 (212) 123 45 67</a></p>
                                <p class="text-muted small">Pazartesi-Cuma 09:00-18:00</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-info-content">
                                <h4>Email</h4>
                                <p><a href="mailto:info@diyetlenio.com">info@diyetlenio.com</a></p>
                                <p><a href="mailto:destek@diyetlenio.com">destek@diyetlenio.com</a></p>
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

                    <!-- Social Media -->
                    <div class="social-section">
                        <h3>Bizi Takip Edin</h3>
                        <div class="social-links">
                            <a href="#" class="social-link facebook" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link twitter" aria-label="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link instagram" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link linkedin" aria-label="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-link youtube" aria-label="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="contact-form-card">
                        <h2>Bize Mesaj Gönderin</h2>
                        <p class="subtitle">Formunu doldurun, en kısa sürede size geri dönelim</p>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-modern alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <?php foreach ($errors as $error): ?>
                                        <div><?= clean($error) ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-modern alert-dismissible fade show">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Başarılı!</strong> Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="contactForm">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating-custom">
                                            <input
                                                type="text"
                                                name="name"
                                                id="name"
                                                placeholder=" "
                                                value="<?= clean($_POST['name'] ?? '') ?>"
                                                required
                                            >
                                            <label for="name">Ad Soyad *</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating-custom">
                                            <input
                                                type="email"
                                                name="email"
                                                id="email"
                                                placeholder=" "
                                                value="<?= clean($_POST['email'] ?? '') ?>"
                                                required
                                            >
                                            <label for="email">Email Adresi *</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating-custom">
                                            <input
                                                type="tel"
                                                name="phone"
                                                id="phone"
                                                placeholder=" "
                                                value="<?= clean($_POST['phone'] ?? '') ?>"
                                            >
                                            <label for="phone">Telefon Numarası (Opsiyonel)</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-floating-custom">
                                            <select name="subject" id="subject" required>
                                                <option value="">Seçiniz</option>
                                                <option value="Genel Bilgi">Genel Bilgi</option>
                                                <option value="Teknik Destek">Teknik Destek</option>
                                                <option value="Diyetisyen Başvurusu">Diyetisyen Başvurusu</option>
                                                <option value="Ödeme Sorunu">Ödeme Sorunu</option>
                                                <option value="Şikayet">Şikayet</option>
                                                <option value="Öneri">Öneri</option>
                                                <option value="Diğer">Diğer</option>
                                            </select>
                                            <label for="subject">Konu *</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-floating-custom">
                                    <textarea
                                        name="message"
                                        id="message"
                                        placeholder=" "
                                        required
                                    ><?= clean($_POST['message'] ?? '') ?></textarea>
                                    <label for="message">Mesajınız *</label>
                                </div>

                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Mesajı Gönder</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Map Section -->
                    <div class="map-section">
                        <div class="map-placeholder">
                            <i class="fas fa-map-marked-alt"></i>
                            <h3>Harita Entegrasyonu</h3>
                            <p>Google Maps veya başka bir harita servisi buraya eklenebilir</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2>Sık Sorulan Sorular</h2>

                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <h4>Mesajıma ne kadar sürede dönüş yapılır?</h4>
                    </div>
                    <p class="faq-answer">
                        Genellikle 24 saat içinde tüm mesajlara dönüş yapıyoruz. Acil durumlar için telefon numaramızı kullanabilirsiniz.
                    </p>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <h4>Diyetisyen olarak nasıl başvuru yapabilirim?</h4>
                    </div>
                    <p class="faq-answer">
                        Diyetisyen başvurusu için konu kısmından "Diyetisyen Başvurusu" seçerek formunu doldurabilirsiniz. Ekibimiz başvurunuzu değerlendirip size geri dönecektir.
                    </p>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <h4>Teknik bir sorun yaşıyorum, ne yapmalıyım?</h4>
                    </div>
                    <p class="faq-answer">
                        Teknik sorunlar için "Teknik Destek" konusunu seçerek detaylı bilgi verebilirsiniz. Ekibimiz en kısa sürede sorununuzu çözecektir.
                    </p>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <h4>Ödeme ile ilgili sorun yaşıyorum</h4>
                    </div>
                    <p class="faq-answer">
                        Ödeme sorunları için "Ödeme Sorunu" konusunu seçerek, sipariş numaranız ve sorun detaylarını bizimle paylaşabilirsiniz.
                    </p>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <h4>Gizliliğim korunuyor mu?</h4>
                    </div>
                    <p class="faq-answer">
                        Evet, tüm bilgileriniz güvenli bir şekilde saklanır ve üçüncü şahıslarla paylaşılmaz. Gizlilik politikamızı inceleyebilirsiniz.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Form validation feedback
        const form = document.getElementById('contactForm');
        if (form) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '') {
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    } else {
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    }
                });

                // Email validation
                if (input.type === 'email') {
                    input.addEventListener('blur', function() {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (emailRegex.test(this.value)) {
                            this.classList.add('is-valid');
                            this.classList.remove('is-invalid');
                        } else if (this.value !== '') {
                            this.classList.add('is-invalid');
                            this.classList.remove('is-valid');
                        }
                    });
                }
            });

            // Form submission animation
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('.btn-submit');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Gönderiliyor...</span>';
                submitBtn.disabled = true;
            });
        }

        // Smooth scroll animation for elements
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all contact cards and FAQ items
        document.querySelectorAll('.contact-info-card, .contact-form-card, .faq-section').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-modern');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
