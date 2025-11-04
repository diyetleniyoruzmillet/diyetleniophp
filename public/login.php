<?php
/**
 * Diyetlenio - Login Sayfası
 * Standalone login page - no footer/navbar
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Zaten giriş yapmışsa yönlendir
if ($auth->check()) {
    $userType = $auth->user()->getUserType();
    redirect(match($userType) {
        'admin' => '/admin/dashboard.php',
        'dietitian' => '/dietitian/dashboard.php',
        'client' => '/client/dashboard.php',
        default => '/'
    });
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting kontrolü (5 deneme / 15 dakika) - with error handling
    $rateLimitExceeded = false;
    try {
        $rateLimiter = new RateLimiter($db);
        if ($rateLimiter->tooManyAttempts('login', null, 5, 15)) {
            $remainingSeconds = $rateLimiter->availableIn('login', null, 15);
            $remainingMinutes = ceil($remainingSeconds / 60);
            $errors[] = "Çok fazla giriş denemesi yaptınız. Lütfen {$remainingMinutes} dakika sonra tekrar deneyin.";
            $rateLimitExceeded = true;
        }
    } catch (Exception $e) {
        // Rate limiter hatası - ignore and continue
        error_log('Rate limiter error: ' . $e->getMessage());
    }

    // CSRF kontrolü
    if ($rateLimitExceeded) {
        // Already set error above
    }
    elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    }
    else {
        // Validator ile validasyon
        $validator = new Validator($_POST);
        $validator
            ->required(['email', 'password'])
            ->email('email')
            ->min('password', 1);

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        // Giriş denemesi
        if (empty($errors)) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $remember = isset($_POST['remember']);
            try {
                if ($auth->attempt($email, $password, $remember)) {
                    $userType = $auth->user()->getUserType();

                    // Kullanıcı tipine göre yönlendirme
                    $redirectUrl = match($userType) {
                        'admin' => '/admin/dashboard.php',
                        'dietitian' => '/dietitian/dashboard.php',
                        'client' => '/client/dashboard.php',
                        default => '/'
                    };

                    // Diyetisyen onay kontrolü
                    if ($userType === 'dietitian') {
                        try {
                            $conn = $db->getConnection();
                            $stmt = $conn->prepare("SELECT is_approved FROM dietitian_profiles WHERE user_id = ? LIMIT 1");
                            $stmt->execute([$auth->user()->getId()]);
                            $row = $stmt->fetch();
                            if (!$row || (int)$row['is_approved'] !== 1) {
                                $redirectUrl = '/dietitian/pending-approval.php';
                            }
                        } catch (Exception $e) {
                            // Hata halinde güvenli varsayılan: dashboard
                            error_log('Dietitian approval check error: ' . $e->getMessage());
                        }
                    }

                    setFlash('success', 'Hoş geldiniz, ' . $auth->user()->getFullName() . '!');
                    redirect($redirectUrl);
                } else {
                    // Başarısız giriş - rate limit'e kaydet
                    try {
                        if (isset($rateLimiter)) {
                            $rateLimiter->hit(hash('sha256', 'login|ip_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')), 15);
                        }
                    } catch (Exception $e) {
                        error_log('Rate limiter hit error: ' . $e->getMessage());
                    }
                    $errors[] = 'Email veya şifre hatalı.';
                }
            } catch (Exception $e) {
                $errors[] = 'Giriş sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Giriş Yap';
$bodyClass = 'login-page';
$showNavbar = false;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Diyetlenio</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 1100px;
            animation: fadeInUp 0.6s ease-out;
        }

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

        .login-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .brand-section {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            backdrop-filter: blur(10px);
        }

        .brand-icon i {
            font-size: 40px;
        }

        .brand-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .brand-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            margin-bottom: 12px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .feature-item i {
            font-size: 24px;
            width: 30px;
        }

        .form-section {
            padding: 60px 50px;
        }

        .form-title {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating input {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            height: 60px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-floating input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .form-floating label {
            padding: 18px 16px;
            color: #6b7280;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 8px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #10b981;
        }

        .form-check {
            margin-bottom: 24px;
        }

        .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .forgot-link {
            text-align: center;
            margin-bottom: 24px;
        }

        .forgot-link a {
            color: #10b981;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-link a:hover {
            color: #059669;
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 30px 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            position: relative;
            background: white;
            padding: 0 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .register-section {
            text-align: center;
        }

        .register-title {
            color: #6b7280;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .btn-register {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-register:hover {
            border-color: #10b981;
            color: #10b981;
            background: #f0fdf4;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }

        .back-home a:hover {
            opacity: 1;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px;
            margin-bottom: 24px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
        }

        @media (max-width: 991px) {
            .form-section {
                padding: 40px 30px;
            }

            .brand-section {
                padding: 40px 30px;
            }

            .form-title {
                font-size: 28px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .form-section {
                padding: 30px 20px;
            }

            .form-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Brand Section -->
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="brand-section h-100">
                        <div class="text-center" style="position: relative; z-index: 1;">
                            <div class="brand-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h1 class="brand-title">Diyetlenio</h1>
                            <p class="brand-subtitle">Sağlıklı yaşam için profesyonel destek</p>

                            <div class="mt-5">
                                <div class="feature-item">
                                    <i class="fas fa-video"></i>
                                    <span>Online Video Görüşme</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Kolay Randevu Sistemi</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Kişisel İlerleme Takibi</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-utensils"></i>
                                    <span>Özel Diyet Programları</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="col-lg-7">
                    <div class="form-section">
                        <h2 class="form-title">Hoş Geldiniz</h2>
                        <p class="form-subtitle">Hesabınıza giriş yapın</p>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php foreach ($errors as $error): ?>
                                    <div><?= clean($error) ?></div>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($msg = getFlash('success')): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($msg = getFlash('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= clean($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/login.php">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                            <div class="form-floating">
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    id="email"
                                    placeholder="Email Adresi"
                                    value="<?= clean($_POST['email'] ?? '') ?>"
                                    required
                                    autofocus
                                >
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email Adresi
                                </label>
                            </div>

                            <div class="form-floating position-relative">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    id="password"
                                    placeholder="Şifre"
                                    required
                                >
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Şifre
                                </label>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>

                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="form-check-input"
                                    id="remember"
                                >
                                <label class="form-check-label" for="remember">
                                    Beni Hatırla
                                </label>
                            </div>

                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </button>

                            <div class="forgot-link">
                                <a href="/forgot-password.php">
                                    <i class="fas fa-key me-1"></i>Şifremi Unuttum
                                </a>
                            </div>

                            <div class="divider">
                                <span>veya</span>
                            </div>

                            <div class="register-section">
                                <p class="register-title">Hesabınız yok mu? Hemen kayıt olun!</p>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <a href="/register-client.php" class="btn btn-register w-100">
                                            <i class="fas fa-user me-2"></i>Danışan
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="/register-dietitian.php" class="btn btn-register w-100">
                                            <i class="fas fa-user-md me-2"></i>Diyetisyen
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="back-home">
            <a href="/">
                <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
