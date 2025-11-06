<?php
/**
 * Dietitian Registration Page
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if ($auth->check()) {
    header('Location: /dietitian/dashboard.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $termsAccepted = isset($_POST['terms_accepted']);

    // Validasyon
    if (empty($fullName) || strlen($fullName) < 3) {
        $errors[] = 'Ad soyad en az 3 karakter olmalıdır';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir email adresi girin';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Şifreler eşleşmiyor';
    }

    if (!$termsAccepted) {
        $errors[] = 'Kullanım şartlarını kabul etmelisiniz';
    }

    // Email kontrolü
    if (empty($errors)) {
        try {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu email adresi zaten kayıtlı';
            }
        } catch (Exception $e) {
            error_log('Registration check error: ' . $e->getMessage());
            $errors[] = 'Bir hata oluştu, lütfen tekrar deneyin';
        }
    }

    // Kayıt işlemi
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, password_hash, phone, user_type, is_active, email_verified, created_at)
                VALUES (?, ?, ?, ?, 'dietitian', 1, 0, NOW())
            ");

            $stmt->execute([$fullName, $email, $passwordHash, $phone]);
            $userId = $conn->lastInsertId();

            // Otomatik giriş yap
            $_SESSION['user_id'] = $userId;
            $_SESSION['success'] = 'Kayıt başarılı! Hoş geldiniz.';

            header('Location: /dietitian/dashboard.php');
            exit;

        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'Kayıt sırasında bir hata oluştu';
        }
    }
}

$pageTitle = 'Diyetisyen Kayıt';
$metaDescription = 'Diyetlenio\'ya danışan olarak kayıt olun ve sağlıklı yaşam yolculuğunuza başlayın.';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <meta name="description" content="<?= clean($metaDescription) ?>">
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
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .register-container {
            max-width: 500px;
            width: 100%;
        }

        .register-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            opacity: 0.95;
            font-size: 0.95rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1.25rem;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .form-floating label {
            padding: 1rem;
        }

        .btn-register {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
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
            background: white;
            padding: 0 1rem;
            color: #64748b;
            font-size: 0.9rem;
            position: relative;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .form-check {
            margin: 1.5rem 0;
        }

        .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #64748b;
        }

        .form-check-label a {
            color: #10b981;
            text-decoration: none;
        }

        .form-check-label a:hover {
            text-decoration: underline;
        }

        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }

        @media (max-width: 576px) {
            .card-header h1 {
                font-size: 1.5rem;
            }

            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="card-header">
                <i class="fas fa-heartbeat" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                <h1>Diyetlenio'ya Katılın</h1>
                <p>Profesyonel diyetisyen olarak platform'a katılın</p>
            </div>

            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php foreach ($errors as $error): ?>
                            <div><?= clean($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="form-floating">
                        <input type="text" class="form-control" id="fullName" name="full_name"
                               placeholder="Ad Soyad" value="<?= clean($_POST['full_name'] ?? '') ?>" required>
                        <label for="fullName"><i class="fas fa-user me-2"></i>Ad Soyad</label>
                    </div>

                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="Email" value="<?= clean($_POST['email'] ?? '') ?>" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email Adresi</label>
                    </div>

                    <div class="form-floating">
                        <input type="tel" class="form-control" id="phone" name="phone"
                               placeholder="Telefon" value="<?= clean($_POST['phone'] ?? '') ?>">
                        <label for="phone"><i class="fas fa-phone me-2"></i>Telefon (Opsiyonel)</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Şifre" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Şifre</label>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="passwordConfirm" name="password_confirm"
                               placeholder="Şifre Tekrar" required>
                        <label for="passwordConfirm"><i class="fas fa-lock me-2"></i>Şifre Tekrar</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms_accepted" required>
                        <label class="form-check-label" for="terms">
                            <a href="/page/kullanim-sartlari" target="_blank">Kullanım Şartları</a>'nı ve
                            <a href="/page/gizlilik-politikasi" target="_blank">Gizlilik Politikası</a>'nı okudum, kabul ediyorum.
                        </label>
                    </div>

                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-2"></i>
                        Kayıt Ol
                    </button>
                </form>

                <div class="divider">
                    <span>veya</span>
                </div>

                <div class="login-link">
                    Zaten hesabınız var mı? <a href="/login.php">Giriş Yapın</a>
                </div>

                <div class="login-link mt-3">
                    Danışan mısınız? <a href="/register-client.php">Danışan Olarak Kayıt Olun</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password strength meter
        const password = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');

        password.addEventListener('input', function() {
            const val = this.value;
            let strength = 0;

            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;
            if (/\d/.test(val)) strength++;
            if (/[^a-zA-Z\d]/.test(val)) strength++;

            strengthBar.className = 'password-strength-bar';

            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Password confirmation check
        const passwordConfirm = document.getElementById('passwordConfirm');

        passwordConfirm.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Şifreler eşleşmiyor');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
