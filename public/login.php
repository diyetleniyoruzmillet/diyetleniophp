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
$bodyClass = 'login-page bg-hero-green';
$showNavbar = false;
?>
<?php include __DIR__ . '/../includes/partials/header.php'; ?>
    
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
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
