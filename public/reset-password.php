<?php
/**
 * Diyetlenio - Şifre Sıfırlama Sayfası
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Zaten giriş yapmışsa yönlendir
if ($auth->check()) {
    redirect('/');
}

$errors = [];
$success = false;
$validToken = false;
$email = '';

// Token kontrolü
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $errors[] = 'Geçersiz sıfırlama linki.';
} else {
    try {
        // Token geçerliliğini kontrol et
        $stmt = $db->prepare("
            SELECT email
            FROM password_resets
            WHERE token = ?
            AND expires_at > NOW()
            AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $validToken = true;
            $email = $reset['email'];
        } else {
            $errors[] = 'Bu sıfırlama linki geçersiz veya süresi dolmuş.';
        }
    } catch (Exception $e) {
        error_log('Token validation error: ' . $e->getMessage());
        $errors[] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
}

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        // Validator ile validasyon
        $validator = new Validator($_POST);
        $validator
            ->required(['password', 'password_confirm'])
            ->min('password', 8)
            ->match('password_confirm', 'password');

        // Şifre güçlülük kontrolü
        $validator->custom('password', function($value) {
            return preg_match('/[A-Z]/', $value) &&
                   preg_match('/[a-z]/', $value) &&
                   preg_match('/[0-9]/', $value);
        }, 'Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.');

        if ($validator->fails()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
        }

        // Şifre güncelleme
        if (empty($errors)) {
            $password = $_POST['password'];
            try {
                $db->beginTransaction();

                // Şifreyi güncelle
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
                $stmt->execute([$hashedPassword, $email]);

                // Token'ı kullanıldı olarak işaretle
                $stmt = $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
                $stmt->execute([$token]);

                $db->commit();

                $success = true;
                setFlash('success', 'Şifreniz başarıyla güncellendi. Şimdi giriş yapabilirsiniz.');

                // 3 saniye sonra login sayfasına yönlendir
                header("refresh:3;url=/login.php");

            } catch (Exception $e) {
                $db->rollBack();
                error_log('Password reset error: ' . $e->getMessage());
                $errors[] = 'Şifre güncellenirken bir hata oluştu. Lütfen tekrar deneyin.';
            }
        }
    }
}

$pageTitle = 'Şifre Sıfırlama';
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
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
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

        .reset-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
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

        .reset-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
        }

        .icon-wrapper {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon-wrapper i {
            font-size: 4rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
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
            color: #0ea5e9;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(14, 165, 233, 0.5);
            color: white;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #0284c7;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 25px;
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

        .password-requirements {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .password-requirements ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="icon-wrapper">
                <i class="fas fa-lock-open"></i>
            </div>

            <h2 class="form-title">Yeni Şifre Belirle</h2>
            <p class="form-subtitle">Hesabınız için yeni bir şifre oluşturun</p>

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
                    Şifreniz başarıyla güncellendi! Giriş sayfasına yönlendiriliyorsunuz...
                </div>
            <?php elseif ($validToken): ?>
                <div class="password-requirements">
                    <strong><i class="fas fa-info-circle me-1"></i>Şifre Gereksinimleri:</strong>
                    <ul>
                        <li>En az 8 karakter uzunluğunda olmalı</li>
                        <li>Güvenli bir şifre seçin</li>
                    </ul>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="form-floating position-relative">
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            id="password"
                            placeholder="Yeni Şifre"
                            required
                            autofocus
                        >
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Yeni Şifre
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>

                    <div class="form-floating position-relative">
                        <input
                            type="password"
                            name="password_confirm"
                            class="form-control"
                            id="password_confirm"
                            placeholder="Yeni Şifre (Tekrar)"
                            required
                        >
                        <label for="password_confirm">
                            <i class="fas fa-lock me-2"></i>Yeni Şifre (Tekrar)
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirm', 'toggleIcon2')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-check me-2"></i>Şifreyi Güncelle
                    </button>
                </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="/login.php">
                    <i class="fas fa-arrow-left me-1"></i>Giriş sayfasına dön
                </a>
            </div>
        </div>

        <div class="back-home">
            <a href="/">
                <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

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
