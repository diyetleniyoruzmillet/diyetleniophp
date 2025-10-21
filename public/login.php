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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .logo-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px 0 0 15px;
            padding: 40px;
        }
        @media (max-width: 768px) {
            .logo-section {
                border-radius: 15px 15px 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card login-card border-0">
                    <div class="row g-0">
                        <!-- Logo ve Bilgi Bölümü -->
                        <div class="col-md-5 d-flex align-items-center">
                            <div class="logo-section w-100 text-center">
                                <i class="fas fa-heartbeat fa-4x mb-3"></i>
                                <h2 class="mb-3">Diyetlenio</h2>
                                <p class="mb-4">Sağlıklı yaşam için profesyonel destek</p>
                                <div class="mb-3">
                                    <i class="fas fa-check-circle me-2"></i> Online Görüşme
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-check-circle me-2"></i> Randevu Sistemi
                                </div>
                                <div>
                                    <i class="fas fa-check-circle me-2"></i> Kişisel Takip
                                </div>
                            </div>
                        </div>

                        <!-- Form Bölümü -->
                        <div class="col-md-7">
                            <div class="card-body p-5">
                                <h3 class="mb-4 text-center">Giriş Yap</h3>

                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= clean($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($msg = getFlash('success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <?= clean($msg) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($msg = getFlash('error')): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <?= clean($msg) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="/login.php">
                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-envelope text-muted me-2"></i>Email Adresi
                                        </label>
                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control form-control-lg"
                                            placeholder="ornek@email.com"
                                            value="<?= clean($_POST['email'] ?? '') ?>"
                                            required
                                            autofocus
                                        >
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-lock text-muted me-2"></i>Şifre
                                        </label>
                                        <input
                                            type="password"
                                            name="password"
                                            class="form-control form-control-lg"
                                            placeholder="••••••••"
                                            required
                                        >
                                    </div>

                                    <div class="mb-3 form-check">
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

                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                        </button>
                                    </div>

                                    <div class="text-center mb-3">
                                        <a href="/forgot-password.php" class="text-decoration-none">
                                            Şifremi Unuttum
                                        </a>
                                    </div>

                                    <hr>

                                    <div class="text-center">
                                        <p class="text-muted mb-3">Hesabınız yok mu?</p>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <a href="/register-client.php" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-user me-2"></i>Danışan Kayıt
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="/register-dietitian.php" class="btn btn-outline-success w-100">
                                                    <i class="fas fa-user-md me-2"></i>Diyetisyen Kayıt
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="/" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
