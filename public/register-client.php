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
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $terms = isset($_POST['terms']);

        // Validasyon
        if (empty($fullName)) {
            $errors[] = 'Ad Soyad gereklidir.';
        } elseif (strlen($fullName) < 3) {
            $errors[] = 'Ad Soyad en az 3 karakter olmalıdır.';
        }

        if (empty($email)) {
            $errors[] = 'Email adresi gereklidir.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir email adresi girin.';
        }

        if (empty($phone)) {
            $errors[] = 'Telefon numarası gereklidir.';
        } elseif (!preg_match('/^[0-9]{10,11}$/', preg_replace('/\D/', '', $phone))) {
            $errors[] = 'Geçerli bir telefon numarası girin.';
        }

        if (empty($password)) {
            $errors[] = 'Şifre gereklidir.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Şifre en az bir büyük harf içermelidir.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Şifre en az bir küçük harf içermelidir.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Şifre en az bir rakam içermelidir.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Şifreler eşleşmiyor.';
        }

        if (!$terms) {
            $errors[] = 'Kullanım şartlarını kabul etmelisiniz.';
        }

        // Email kontrolü
        if (empty($errors)) {
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu email adresi zaten kayıtlı.';
            }
        }

        // Kayıt işlemi
        if (empty($errors)) {
            try {
                $conn = $db->getConnection();
                $conn->beginTransaction();

                // Email doğrulama token'ı oluştur
                $verificationToken = bin2hex(random_bytes(32));
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Kullanıcıyı kaydet
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        email, password, full_name, phone, user_type,
                        email_verification_token, is_active, created_at
                    ) VALUES (?, ?, ?, ?, 'client', ?, 1, NOW())
                ");

                $stmt->execute([
                    $email,
                    $hashedPassword,
                    $fullName,
                    $phone,
                    $verificationToken
                ]);

                $conn->commit();
                $success = true;

                // TODO: Email gönderme işlemi
                // sendVerificationEmail($email, $verificationToken);

            } catch (Exception $e) {
                $conn->rollBack();
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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card register-card border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h3>Danışan Kayıt</h3>
                            <p class="text-muted">Sağlıklı yaşam yolculuğunuza başlayın</p>
                        </div>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Kayıt Başarılı!</h5>
                                <p class="mb-0">
                                    Hesabınız oluşturuldu. Email adresinize gönderilen doğrulama linkine tıklayarak
                                    hesabınızı aktif edebilirsiniz.
                                </p>
                                <hr>
                                <a href="/login.php" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </a>
                            </div>
                        <?php else: ?>

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

                            <form method="POST" action="/register-client.php" id="registerForm">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user text-muted me-2"></i>Ad Soyad
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="full_name"
                                        class="form-control"
                                        placeholder="Ahmet Yılmaz"
                                        value="<?= clean($_POST['full_name'] ?? '') ?>"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-envelope text-muted me-2"></i>Email Adresi
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        placeholder="ornek@email.com"
                                        value="<?= clean($_POST['email'] ?? '') ?>"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-phone text-muted me-2"></i>Telefon
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        class="form-control"
                                        placeholder="0555 123 4567"
                                        value="<?= clean($_POST['phone'] ?? '') ?>"
                                        required
                                    >
                                    <small class="text-muted">Örnek: 05551234567</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-lock text-muted me-2"></i>Şifre
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        id="password"
                                        placeholder="En az 8 karakter"
                                        required
                                    >
                                    <div class="password-strength mt-2 bg-secondary" id="passwordStrength"></div>
                                    <small class="text-muted">
                                        Şifreniz en az 8 karakter, bir büyük harf, bir küçük harf ve bir rakam içermelidir.
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-lock text-muted me-2"></i>Şifre Tekrar
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="password"
                                        name="password_confirm"
                                        class="form-control"
                                        placeholder="Şifrenizi tekrar girin"
                                        required
                                    >
                                </div>

                                <div class="mb-3 form-check">
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
                                        okudum, kabul ediyorum.
                                    </label>
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                                    </button>
                                </div>

                                <hr>

                                <div class="text-center">
                                    <p class="text-muted mb-2">Zaten hesabınız var mı?</p>
                                    <a href="/login.php" class="btn btn-outline-success">
                                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                    </a>
                                </div>

                                <div class="text-center mt-3">
                                    <p class="text-muted mb-2">Diyetisyen misiniz?</p>
                                    <a href="/register-dietitian.php" class="btn btn-outline-success">
                                        <i class="fas fa-user-md me-2"></i>Diyetisyen Kayıt
                                    </a>
                                </div>
                            </form>

                        <?php endif; ?>
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
    <script>
        // Şifre güvenlik göstergesi
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');

        passwordInput?.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            const colors = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];

            strengthBar.className = 'password-strength mt-2 ' + (colors[strength - 1] || 'bg-secondary');
            strengthBar.style.width = widths[strength - 1] || '0%';
        });
    </script>
</body>
</html>
