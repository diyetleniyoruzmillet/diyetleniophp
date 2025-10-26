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
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    }
    else {
        // Rate limiting kontrolü (3 kayıt denemesi / 10 dakika) - with error handling
        try {
            $rateLimiter = new RateLimiter($db);
            if ($rateLimiter->tooManyAttempts('register_client', null, 3, 10)) {
                $remainingSeconds = $rateLimiter->availableIn('register_client', null, 10);
                $remainingMinutes = ceil($remainingSeconds / 60);
                $errors[] = "Çok fazla kayıt denemesi yaptınız. Lütfen {$remainingMinutes} dakika sonra tekrar deneyin.";
            }
        } catch (Exception $e) {
            error_log('Rate limiter error in register-client: ' . $e->getMessage());
            // Continue without rate limiting
        }

        if (empty($errors)) {
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
                    // Rate limit'e kaydet (başarılı deneme öncesi) - with error handling
                    try {
                        if (isset($rateLimiter)) {
                            $rateLimiter->hit(hash('sha256', 'register_client|ip_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')), 10);
                        }
                    } catch (Exception $e) {
                        error_log('Rate limiter hit error: ' . $e->getMessage());
                    }

                    // Validated data al
                    $data = $validator->validated();

                    $conn = $db->getConnection();
                    $conn->beginTransaction();

                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

                    // Kullanıcıyı kaydet (doğrulama gerektirmeden aktif)
                    $stmt = $conn->prepare("
                        INSERT INTO users (
                            email, password, full_name, phone, user_type,
                            is_active, created_at
                        ) VALUES (?, ?, ?, ?, 'client', 1, NOW())
                    ");

                    $stmt->execute([
                        $data['email'],
                        $hashedPassword,
                        $data['full_name'],
                        $data['phone']
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
}

$pageTitle = 'Danışan Kayıt';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            padding: 50px 0;
            position: relative;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating Background Animation */
        .floating-circles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 15s infinite ease-in-out;
        }
        .floating-circle:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            top: 20%;
            animation-delay: 0s;
        }
        .floating-circle:nth-child(2) {
            width: 60px;
            height: 60px;
            right: 15%;
            top: 60%;
            animation-delay: 2s;
        }
        .floating-circle:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 70%;
            top: 30%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        .register-container {
            position: relative;
            z-index: 1;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 50px;
            animation: fadeInUp 0.6s ease-out;
        }

        @media (max-width: 768px) {
            body {
                padding: 30px 0;
            }
            .register-card {
                padding: 30px 25px;
            }
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

        .brand-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 25px rgba(86, 171, 47, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 8px 25px rgba(86, 171, 47, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 12px 35px rgba(86, 171, 47, 0.6); }
        }

        .brand-icon i {
            font-size: 36px;
            color: white;
        }

        .register-card h3 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .register-card .subtitle {
            color: #718096;
            margin-bottom: 30px;
        }

        /* Floating Label Inputs */
        .form-floating-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .form-floating-custom input {
            width: 100%;
            padding: 18px 15px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }

        .form-floating-custom input:focus {
            outline: none;
            border-color: #56ab2f;
            box-shadow: 0 0 0 4px rgba(86, 171, 47, 0.15);
            transform: translateY(-2px);
        }

        .form-floating-custom label {
            position: absolute;
            left: 15px;
            top: 16px;
            color: #a0aec0;
            font-size: 16px;
            transition: all 0.3s;
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }

        .form-floating-custom input:focus + label,
        .form-floating-custom input:not(:placeholder-shown) + label {
            top: -8px;
            font-size: 12px;
            color: #56ab2f;
            font-weight: 600;
        }

        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.3s;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #56ab2f;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 10px;
            display: none;
        }

        .password-strength.active {
            display: block;
        }

        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-text {
            font-size: 12px;
            font-weight: 600;
        }

        .strength-feedback {
            font-size: 11px;
            color: #718096;
            margin-top: 3px;
        }

        /* Buttons */
        .btn-gradient {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border: none;
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 20px rgba(86, 171, 47, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-gradient:hover::before {
            left: 100%;
        }

        .btn-gradient:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 30px rgba(86, 171, 47, 0.6);
            color: white;
        }

        .btn-gradient:active {
            transform: translateY(-1px) scale(1);
        }

        .btn-outline-custom {
            border: 2px solid #56ab2f;
            color: #56ab2f;
            background: white;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-outline-custom:hover {
            background: #56ab2f;
            color: white;
        }

        /* Custom Checkbox */
        .custom-checkbox {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
        }

        .custom-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .custom-checkbox label {
            font-size: 14px;
            color: #4a5568;
            cursor: pointer;
            margin: 0;
        }

        .custom-checkbox a {
            color: #56ab2f;
            text-decoration: none;
            font-weight: 600;
        }

        .custom-checkbox a:hover {
            text-decoration: underline;
        }

        /* Alert Messages */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        .alert-danger {
            background: #fff5f5;
            color: #c53030;
            border-left: 4px solid #f56565;
        }

        /* Success State */
        .success-animation {
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: bounceIn 0.6s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .success-icon i {
            font-size: 50px;
            color: white;
        }

        .success-animation h4 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 15px;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .back-link:hover {
            color: white;
            transform: translateX(-5px);
        }

        .back-link i {
            margin-right: 8px;
        }

        /* Required Star */
        .required {
            color: #f56565;
            margin-left: 3px;
        }
    </style>
</head>
<body>
    <!-- Floating Background Circles -->
    <div class="floating-circles">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <div class="container register-container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="register-card">
                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="success-animation">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4>Kayıt Başarılı!</h4>
                            <p class="text-muted mb-4">Hesabınız başarıyla oluşturuldu. Artık giriş yapabilirsiniz.</p>
                            <a href="/login.php" class="btn btn-gradient w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Registration Form -->
                        <div class="text-center mb-4">
                            <div class="brand-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Danışan Kayıt</h3>
                            <p class="subtitle">Sağlıklı yaşam yolculuğunuza bugün başlayın</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-custom">
                                <strong><i class="fas fa-exclamation-circle me-2"></i>Lütfen aşağıdaki hataları düzeltin:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= clean($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/register-client.php" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                            <div class="form-floating-custom">
                                <input
                                    type="text"
                                    name="full_name"
                                    id="full_name"
                                    placeholder=" "
                                    value="<?= clean($_POST['full_name'] ?? '') ?>"
                                    required
                                    autofocus
                                >
                                <label for="full_name">Ad Soyad<span class="required">*</span></label>
                            </div>

                            <div class="form-floating-custom">
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    placeholder=" "
                                    value="<?= clean($_POST['email'] ?? '') ?>"
                                    required
                                >
                                <label for="email">Email Adresi<span class="required">*</span></label>
                            </div>

                            <div class="form-floating-custom">
                                <input
                                    type="tel"
                                    name="phone"
                                    id="phone"
                                    placeholder=" "
                                    value="<?= clean($_POST['phone'] ?? '') ?>"
                                    required
                                >
                                <label for="phone">Telefon (05551234567)<span class="required">*</span></label>
                            </div>

                            <div class="form-floating-custom password-wrapper">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    placeholder=" "
                                    required
                                >
                                <label for="password">Şifre<span class="required">*</span></label>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                                <div class="strength-feedback" id="strengthFeedback"></div>
                            </div>

                            <div class="form-floating-custom password-wrapper">
                                <input
                                    type="password"
                                    name="password_confirm"
                                    id="password_confirm"
                                    placeholder=" "
                                    required
                                >
                                <label for="password_confirm">Şifre Tekrar<span class="required">*</span></label>
                                <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                                    <i class="fas fa-eye" id="password_confirm-icon"></i>
                                </button>
                            </div>

                            <div class="custom-checkbox">
                                <input
                                    type="checkbox"
                                    name="terms"
                                    id="terms"
                                    required
                                >
                                <label for="terms">
                                    <a href="/page/kullanim-sartlari" target="_blank">Kullanım Şartları</a> ve
                                    <a href="/page/gizlilik-politikasi" target="_blank">Gizlilik Politikası</a>'nı
                                    kabul ediyorum.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-gradient w-100">
                                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                            </button>

                            <hr style="margin: 30px 0; border-color: #e2e8f0;">

                            <div class="text-center">
                                <p class="text-muted mb-3">Zaten hesabınız var mı?</p>
                                <a href="/login.php" class="btn btn-outline-custom me-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </a>
                                <a href="/register-dietitian.php" class="btn btn-outline-custom">
                                    <i class="fas fa-user-md me-2"></i>Diyetisyen Kayıt
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="text-center">
                    <a href="/" class="back-link">
                        <i class="fas fa-arrow-left"></i>Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Toggle
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');

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

        // Password Strength Indicator
        const passwordInput = document.getElementById('password');
        const strengthContainer = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const strengthFeedback = document.getElementById('strengthFeedback');

        passwordInput?.addEventListener('input', function() {
            const password = this.value;

            if (password.length === 0) {
                strengthContainer.classList.remove('active');
                return;
            }

            strengthContainer.classList.add('active');

            let strength = 0;
            const feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push('8 karakter');

            if (/[a-z]/.test(password)) strength++;
            else feedback.push('küçük harf');

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('büyük harf');

            if (/[0-9]/.test(password)) strength++;
            else feedback.push('rakam');

            if (/[^A-Za-z0-9]/.test(password)) strength++;

            const colors = ['#f56565', '#ed8936', '#ecc94b', '#48bb78', '#38a169'];
            const labels = ['Çok Zayıf', 'Zayıf', 'Orta', 'İyi', 'Güçlü'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];

            strengthFill.style.width = widths[strength];
            strengthFill.style.background = colors[strength];
            strengthText.textContent = labels[strength];
            strengthText.style.color = colors[strength];

            if (feedback.length > 0) {
                strengthFeedback.textContent = 'Eksik: ' + feedback.join(', ');
            } else {
                strengthFeedback.textContent = 'Şifreniz güçlü!';
                strengthFeedback.style.color = '#38a169';
            }
        });
    </script>
</body>
</html>
