<?php
/**
 * Diyetlenio - Danışan Kayıt Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Zaten giriş yapmışsa yönlendir
if ($auth->check()) {
    redirect('/');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting kontrolü (3 kayıt denemesi / 10 dakika)
    $rateLimiter = new RateLimiter($db);
    if ($rateLimiter->tooManyAttempts('register_client', null, 3, 10)) {
        $remainingSeconds = $rateLimiter->availableIn('register_client', null, 10);
        $remainingMinutes = ceil($remainingSeconds / 60);
        $errors[] = "Çok fazla kayıt denemesi yaptınız. Lütfen {$remainingMinutes} dakika sonra tekrar deneyin.";
    }
    // CSRF kontrolü
    elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    }
    else {
        // Validator ile validasyon
        $validator = new Validator($_POST);

        $validator
            ->required(['full_name', 'email', 'phone', 'password', 'password_confirm'])
            ->min('full_name', 3)
            ->max('full_name', 100)
            ->email('email')
            ->unique('email', 'users', 'email')
            ->phone('phone')
            ->min('password', 8)
            ->match('password_confirm', 'password');

        // Şifre güçlülük kontrolü
        $validator->custom('password', function($value) {
            return preg_match('/[A-Z]/', $value) &&
                   preg_match('/[a-z]/', $value) &&
                   preg_match('/[0-9]/', $value);
        }, 'Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.');

        // Şartlar kabul edildi mi?
        if (!isset($_POST['terms'])) {
            $validator->errors()['terms'][] = 'Kullanım şartlarını kabul etmelisiniz.';
        }

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        // Kayıt işlemi
        if (empty($errors)) {
            try {
                // Rate limit'e kaydet (başarılı deneme öncesi)
                $rateLimiter->hit(hash('sha256', 'register_client|ip_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')), 10);

                // Validated data al
                $data = $validator->validated();

                $conn = $db->getConnection();
                $conn->beginTransaction();

                // Email doğrulama token'ı oluştur
                $verificationToken = bin2hex(random_bytes(32));
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                // Kullanıcıyı kaydet
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        email, password, full_name, phone, user_type,
                        email_verification_token, is_active, created_at
                    ) VALUES (?, ?, ?, ?, 'client', ?, 1, NOW())
                ");

                $stmt->execute([
                    $data['email'],
                    $hashedPassword,
                    $data['full_name'],
                    $data['phone'],
                    $verificationToken
                ]);

                $conn->commit();
                $success = true;

            } catch (Exception $e) {
                if (isset($conn) && $conn->inTransaction()) {
                    $conn->rollBack();
                }
                $errors[] = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Danışan Kayıt';
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
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 50%, #4facfe 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Orbs */
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            filter: blur(80px);
            top: -300px;
            right: -200px;
            animation: float 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79, 172, 254, 0.2) 0%, transparent 70%);
            filter: blur(80px);
            bottom: -200px;
            left: -100px;
            animation: float 15s ease-in-out infinite reverse;
        }

        .register-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 600px;
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

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(86, 171, 47, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1rem;
        }

        .form-floating {
            margin-bottom: 20px;
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
            border-color: #56ab2f;
            box-shadow: 0 0 0 4px rgba(86, 171, 47, 0.15);
            transform: translateY(-2px);
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
            color: #56ab2f;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
            margin-top: 8px;
        }

        .strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
            font-weight: 500;
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
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-color: #56ab2f;
        }

        .form-check-label {
            margin-left: 8px;
            color: #2d3748;
            cursor: pointer;
        }

        .form-check-label a {
            color: #56ab2f;
            text-decoration: none;
            font-weight: 600;
        }

        .form-check-label a:hover {
            text-decoration: underline;
        }

        .btn-register {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border: none;
            color: white;
            padding: 16px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 20px rgba(86, 171, 47, 0.4);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 30px rgba(86, 171, 47, 0.6);
            color: white;
        }

        .btn-register:active {
            transform: translateY(-1px) scale(1);
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

        .login-section {
            text-align: center;
        }

        .btn-link {
            border: 2px solid #e2e8f0;
            background: white;
            color: #2d3748;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-link:hover {
            border-color: #0ea5e9;
            color: #0ea5e9;
            background: rgba(102, 126, 234, 0.05);
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

        .success-animation {
            text-align: center;
            animation: bounceIn 0.6s ease;
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .register-card {
                padding: 30px 25px;
            }
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <?php if ($success): ?>
                <div class="success-animation">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="page-title mb-3">Kayıt Başarılı!</h2>
                    <p class="text-muted mb-4">
                        Hesabınız oluşturuldu. Email adresinize gönderilen doğrulama linkine tıklayarak
                        hesabınızı aktif edebilirsiniz.
                    </p>
                    <a href="/login.php" class="btn btn-register">
                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                    </a>
                </div>
            <?php else: ?>
                <div class="brand-header">
                    <div class="brand-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1 class="page-title">Danışan Kayıt</h1>
                    <p class="page-subtitle">Sağlıklı yaşam yolculuğunuza bugün başlayın</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Lütfen hataları düzeltin:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= clean($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/register-client.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="form-floating">
                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            id="fullName"
                            placeholder="Ad Soyad"
                            value="<?= clean($_POST['full_name'] ?? '') ?>"
                            required
                            autofocus
                        >
                        <label for="fullName">
                            <i class="fas fa-user me-2"></i>Ad Soyad
                        </label>
                    </div>

                    <div class="form-floating">
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            id="email"
                            placeholder="Email Adresi"
                            value="<?= clean($_POST['email'] ?? '') ?>"
                            required
                        >
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email Adresi
                        </label>
                    </div>

                    <div class="form-floating">
                        <input
                            type="tel"
                            name="phone"
                            class="form-control"
                            id="phone"
                            placeholder="Telefon"
                            value="<?= clean($_POST['phone'] ?? '') ?>"
                            required
                        >
                        <label for="phone">
                            <i class="fas fa-phone me-2"></i>Telefon (05551234567)
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
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                    <div class="password-strength bg-secondary" id="passwordStrength"></div>
                    <small class="text-muted strength-text" id="strengthText">
                        En az 8 karakter, büyük harf, küçük harf ve rakam
                    </small>

                    <div class="form-floating position-relative mt-3">
                        <input
                            type="password"
                            name="password_confirm"
                            class="form-control"
                            id="passwordConfirm"
                            placeholder="Şifre Tekrar"
                            required
                        >
                        <label for="passwordConfirm">
                            <i class="fas fa-lock me-2"></i>Şifre Tekrar
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword('passwordConfirm', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>

                    <div class="form-check mt-3">
                        <input
                            type="checkbox"
                            name="terms"
                            class="form-check-input"
                            id="terms"
                            required
                        >
                        <label class="form-check-label" for="terms">
                            <a href="/page/kullanim-sartlari" target="_blank">Kullanım Şartları</a> ve
                            <a href="/page/gizlilik-politikasi" target="_blank">Gizlilik Politikası</a>'nı
                            kabul ediyorum
                        </label>
                    </div>

                    <button type="submit" class="btn btn-register mt-4">
                        <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                    </button>

                    <div class="divider">
                        <span>veya</span>
                    </div>

                    <div class="login-section">
                        <p class="text-muted mb-3">Zaten hesabınız var mı?</p>
                        <a href="/login.php" class="btn-link me-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                        </a>
                        <a href="/register-dietitian.php" class="btn-link">
                            <i class="fas fa-user-md me-2"></i>Diyetisyen Kayıt
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="back-home">
            <a href="/">
                <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Şifre güvenlik göstergesi
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');

        passwordInput?.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            const feedback = [];

            if (password.length >= 8) {
                strength++;
            } else {
                feedback.push('8 karakter');
            }

            if (/[a-z]/.test(password)) {
                strength++;
            } else {
                feedback.push('küçük harf');
            }

            if (/[A-Z]/.test(password)) {
                strength++;
            } else {
                feedback.push('büyük harf');
            }

            if (/[0-9]/.test(password)) {
                strength++;
            } else {
                feedback.push('rakam');
            }

            if (/[^a-zA-Z0-9]/.test(password)) {
                strength++;
            }

            const colors = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];
            const labels = ['Çok Zayıf', 'Zayıf', 'Orta', 'İyi', 'Güçlü'];
            const labelColors = ['text-danger', 'text-danger', 'text-warning', 'text-info', 'text-success'];

            strengthBar.className = 'password-strength ' + (colors[strength - 1] || 'bg-secondary');
            strengthBar.style.width = widths[strength - 1] || '0%';

            if (password.length > 0) {
                if (feedback.length > 0) {
                    strengthText.textContent = 'Eksik: ' + feedback.join(', ');
                    strengthText.className = 'strength-text text-muted';
                } else {
                    strengthText.textContent = labels[strength - 1] || 'Şifre Girin';
                    strengthText.className = 'strength-text ' + (labelColors[strength - 1] || 'text-muted');
                }
            } else {
                strengthText.textContent = 'En az 8 karakter, büyük harf, küçük harf ve rakam';
                strengthText.className = 'strength-text text-muted';
            }
        });
    </script>
</body>
</html>
