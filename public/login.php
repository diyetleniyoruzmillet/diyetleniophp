<?php
/**
 * Diyetlenio - Login Sayfası
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
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validasyon
        if (empty($email)) {
            $errors[] = 'Email adresi gereklidir.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir email adresi girin.';
        }

        if (empty($password)) {
            $errors[] = 'Şifre gereklidir.';
        }

        // Giriş denemesi
        if (empty($errors)) {
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

                    setFlash('success', 'Hoş geldiniz, ' . $auth->user()->getFullName() . '!');
                    redirect($redirectUrl);
                } else {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -300px;
            right: -200px;
            animation: float 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            bottom: -200px;
            left: -100px;
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, 30px) rotate(180deg); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1000px;
            animation: fadeInUp 0.6s ease;
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .brand-section {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -100px;
            right: -100px;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .brand-icon {
            font-size: 5rem;
            margin-bottom: 30px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .brand-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .feature-item i {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin-right: 15px;
        }

        .form-section {
            padding: 60px 50px;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            text-align: center;
        }

        .form-subtitle {
            color: #718096;
            text-align: center;
            margin-bottom: 40px;
        }

        .form-floating {
            margin-bottom: 25px;
        }

        .form-floating input {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.2rem 1rem;
            font-size: 1rem;
            transition: all 0.3s;
            height: auto;
        }

        .form-floating input:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.1);
        }

        .form-floating label {
            color: #718096;
            padding: 1.2rem 1rem;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 5px 10px;
            z-index: 10;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #11998e;
        }

        .form-check {
            margin-bottom: 25px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #11998e;
            border-color: #11998e;
        }

        .form-check-label {
            margin-left: 8px;
            color: #2d3748;
            cursor: pointer;
        }

        .btn-login {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(17, 153, 142, 0.5);
            color: white;
        }

        .forgot-link {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-link a {
            color: #11998e;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .forgot-link a:hover {
            color: #0f8478;
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 15px;
            color: #718096;
            position: relative;
            z-index: 1;
        }

        .register-section {
            text-align: center;
        }

        .register-title {
            color: #718096;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .btn-register {
            border: 2px solid #e2e8f0;
            background: white;
            color: #2d3748;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .btn-register:hover {
            border-color: #11998e;
            color: #11998e;
            background: rgba(17, 153, 142, 0.05);
            transform: translateY(-2px);
        }

        .back-home {
            text-align: center;
            margin-top: 30px;
        }

        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .back-home a:hover {
            opacity: 0.8;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .brand-section {
                padding: 40px 30px;
            }
            .form-section {
                padding: 40px 30px;
            }
            .brand-title {
                font-size: 2rem;
            }
            .form-title {
                font-size: 1.5rem;
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
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

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
