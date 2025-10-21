<?php
/**
 * Diyetlenio - Diyetisyen Kayıt Sayfası
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
        // Form verileri
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Diyetisyen bilgileri
        $title = trim($_POST['title'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $experienceYears = (int)($_POST['experience_years'] ?? 0);
        $education = trim($_POST['education'] ?? '');
        $aboutMe = trim($_POST['about_me'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $consultationFee = (float)($_POST['consultation_fee'] ?? 0);
        $terms = isset($_POST['terms']);

        // Temel validasyon
        if (empty($fullName) || strlen($fullName) < 3) {
            $errors[] = 'Ad Soyad en az 3 karakter olmalıdır.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir email adresi girin.';
        }

        if (empty($phone) || !preg_match('/^[0-9]{10,11}$/', preg_replace('/\D/', '', $phone))) {
            $errors[] = 'Geçerli bir telefon numarası girin.';
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Şifreler eşleşmiyor.';
        }

        // Diyetisyen bilgileri validasyonu
        if (empty($title)) {
            $errors[] = 'Unvan gereklidir.';
        }

        if (empty($specialization)) {
            $errors[] = 'Uzmanlık alanı gereklidir.';
        }

        if ($experienceYears < 0 || $experienceYears > 50) {
            $errors[] = 'Geçerli bir tecrübe yılı girin.';
        }

        if (empty($education)) {
            $errors[] = 'Eğitim bilgisi gereklidir.';
        }

        if (empty($aboutMe) || strlen($aboutMe) < 50) {
            $errors[] = 'Hakkımda bölümü en az 50 karakter olmalıdır.';
        }

        if (!empty($iban)) {
            $ibanClean = preg_replace('/\s/', '', $iban);
            if (!preg_match('/^TR\d{24}$/', $ibanClean)) {
                $errors[] = 'Geçerli bir IBAN numarası girin (TR ile başlamalı, 26 karakter).';
            }
        }

        if ($consultationFee < 0 || $consultationFee > 10000) {
            $errors[] = 'Geçerli bir danışmanlık ücreti girin.';
        }

        if (!$terms) {
            $errors[] = 'Kullanım şartlarını kabul etmelisiniz.';
        }

        // Dosya yükleme kontrolü
        $diplomaFile = null;
        if (isset($_FILES['diploma']) && $_FILES['diploma']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            $fileExt = strtolower(pathinfo($_FILES['diploma']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowed)) {
                $errors[] = 'Diploma dosyası sadece PDF, JPG veya PNG formatında olmalıdır.';
            } elseif ($_FILES['diploma']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Diploma dosyası en fazla 5MB olabilir.';
            } else {
                $diplomaFile = $_FILES['diploma'];
            }
        } else {
            $errors[] = 'Diploma dosyası yüklemeniz gereklidir.';
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

                // Email doğrulama token'ı
                $verificationToken = bin2hex(random_bytes(32));
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Diploma dosyasını kaydet
                $diplomaPath = null;
                if ($diplomaFile) {
                    $uploadDir = __DIR__ . '/../assets/uploads/documents/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $diplomaFileName = 'diploma_' . uniqid() . '.' . strtolower(pathinfo($diplomaFile['name'], PATHINFO_EXTENSION));
                    $diplomaPath = $uploadDir . $diplomaFileName;

                    if (!move_uploaded_file($diplomaFile['tmp_name'], $diplomaPath)) {
                        throw new Exception('Dosya yükleme hatası.');
                    }

                    $diplomaPath = 'documents/' . $diplomaFileName;
                }

                // Kullanıcıyı kaydet
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        email, password, full_name, phone, user_type,
                        email_verification_token, is_active, created_at
                    ) VALUES (?, ?, ?, ?, 'dietitian', ?, 0, NOW())
                ");

                $stmt->execute([
                    $email,
                    $hashedPassword,
                    $fullName,
                    $phone,
                    $verificationToken
                ]);

                $userId = $conn->lastInsertId();

                // Diyetisyen profilini oluştur
                $stmt = $conn->prepare("
                    INSERT INTO dietitian_profiles (
                        user_id, title, specialization, experience_years,
                        about_me, education, diploma_file, iban,
                        consultation_fee, is_approved, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
                ");

                $stmt->execute([
                    $userId,
                    $title,
                    $specialization,
                    $experienceYears,
                    $aboutMe,
                    $education,
                    $diplomaPath,
                    $iban,
                    $consultationFee
                ]);

                $conn->commit();
                $success = true;

                // TODO: Email gönderme ve admin bildirimi
                // sendVerificationEmail($email, $verificationToken);
                // notifyAdminNewDietitian($userId);

            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $errors[] = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                error_log('Dietitian registration error: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Diyetisyen Kayıt';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
            position: relative;
            overflow-x: hidden;
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
            .register-card {
                padding: 30px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            50% { transform: scale(1.05); box-shadow: 0 0 20px 10px rgba(102, 126, 234, 0); }
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

        .form-floating-custom input,
        .form-floating-custom select,
        .form-floating-custom textarea {
            width: 100%;
            padding: 18px 15px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }

        .form-floating-custom textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-floating-custom input:focus,
        .form-floating-custom select:focus,
        .form-floating-custom textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        .form-floating-custom input:not(:placeholder-shown) + label,
        .form-floating-custom select:focus + label,
        .form-floating-custom select:not([value=""]):valid + label,
        .form-floating-custom textarea:focus + label,
        .form-floating-custom textarea:not(:placeholder-shown) + label {
            top: -8px;
            font-size: 12px;
            color: #667eea;
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
            color: #667eea;
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

        /* Section Titles */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: #667eea;
        }

        .section-title:first-of-type {
            margin-top: 0;
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-label {
            display: block;
            padding: 20px;
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f7fafc;
        }

        .file-upload-label:hover {
            border-color: #667eea;
            background: #edf2f7;
        }

        .file-upload-label.has-file {
            border-color: #48bb78;
            background: #f0fff4;
        }

        .file-upload-icon {
            font-size: 36px;
            color: #a0aec0;
            margin-bottom: 10px;
        }

        .file-upload-label.has-file .file-upload-icon {
            color: #48bb78;
        }

        .file-upload-text {
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }

        .file-upload-hint {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 5px;
        }

        /* Buttons */
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid #667eea;
            color: #667eea;
            background: white;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-custom:hover {
            background: #667eea;
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
            color: #667eea;
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

        .alert-info {
            background: #ebf8ff;
            color: #2c5282;
            border-left: 4px solid #4299e1;
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

        .success-steps {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .success-steps ol {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .success-steps li {
            margin: 8px 0;
            color: #4a5568;
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

        /* Small Text */
        .small-text {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
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
            <div class="col-lg-9">
                <div class="register-card">
                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="success-animation">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4>Kayıt Başarılı!</h4>
                            <p class="text-muted">Diyetisyen kaydınız alınmıştır. Hesabınız admin onayından sonra aktif edilecektir.</p>

                            <div class="success-steps">
                                <strong>Sonraki Adımlar:</strong>
                                <ol>
                                    <li>Email adresinize gönderilen doğrulama linkine tıklayın</li>
                                    <li>Yönetici onayını bekleyin (1-2 iş günü)</li>
                                    <li>Onay sonrası giriş yaparak profilinizi tamamlayın</li>
                                </ol>
                            </div>

                            <a href="/" class="btn btn-gradient mt-3">
                                <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Registration Form -->
                        <div class="text-center mb-4">
                            <div class="brand-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h3>Diyetisyen Kayıt</h3>
                            <p class="subtitle">Profesyonel kariyerinize online olarak devam edin</p>
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

                        <form method="POST" action="/register-dietitian.php" enctype="multipart/form-data" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                            <!-- Kişisel Bilgiler -->
                            <div class="section-title">
                                <i class="fas fa-user"></i> Kişisel Bilgiler
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input
                                            type="text"
                                            name="full_name"
                                            id="full_name"
                                            placeholder=" "
                                            value="<?= clean($_POST['full_name'] ?? '') ?>"
                                            required
                                        >
                                        <label for="full_name">Ad Soyad<span class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <select name="title" id="title" required>
                                            <option value="">Seçiniz</option>
                                            <option value="Diyetisyen" <?= ($_POST['title'] ?? '') === 'Diyetisyen' ? 'selected' : '' ?>>Diyetisyen</option>
                                            <option value="Uzman Diyetisyen" <?= ($_POST['title'] ?? '') === 'Uzman Diyetisyen' ? 'selected' : '' ?>>Uzman Diyetisyen</option>
                                            <option value="Beslenme Uzmanı" <?= ($_POST['title'] ?? '') === 'Beslenme Uzmanı' ? 'selected' : '' ?>>Beslenme Uzmanı</option>
                                            <option value="Klinik Diyetisyen" <?= ($_POST['title'] ?? '') === 'Klinik Diyetisyen' ? 'selected' : '' ?>>Klinik Diyetisyen</option>
                                        </select>
                                        <label for="title">Unvan<span class="required">*</span></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
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
                                        <label for="email">Email Adresi<span class="required">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating-custom">
                                        <input
                                            type="tel"
                                            name="phone"
                                            id="phone"
                                            placeholder=" "
                                            value="<?= clean($_POST['phone'] ?? '') ?>"
                                            required
                                        >
                                        <label for="phone">Telefon<span class="required">*</span></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
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
                                </div>
                                <div class="col-md-6">
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
                                </div>
                            </div>

                            <!-- Mesleki Bilgiler -->
                            <div class="section-title">
                                <i class="fas fa-briefcase"></i> Mesleki Bilgiler
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-floating-custom">
                                        <input
                                            type="text"
                                            name="specialization"
                                            id="specialization"
                                            placeholder=" "
                                            value="<?= clean($_POST['specialization'] ?? '') ?>"
                                            required
                                        >
                                        <label for="specialization">Uzmanlık Alanları<span class="required">*</span></label>
                                        <div class="small-text">Virgül ile ayırarak yazınız (Örn: Kilo Yönetimi, Sporcu Beslenmesi)</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating-custom">
                                        <input
                                            type="number"
                                            name="experience_years"
                                            id="experience_years"
                                            placeholder=" "
                                            min="0"
                                            max="50"
                                            value="<?= clean($_POST['experience_years'] ?? '') ?>"
                                            required
                                        >
                                        <label for="experience_years">Tecrübe (Yıl)<span class="required">*</span></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating-custom">
                                <textarea
                                    name="education"
                                    id="education"
                                    placeholder=" "
                                    rows="3"
                                    required
                                ><?= clean($_POST['education'] ?? '') ?></textarea>
                                <label for="education">Eğitim Bilgileri<span class="required">*</span></label>
                                <div class="small-text">Örn: İstanbul Üniversitesi, Beslenme ve Diyetetik Bölümü (2015-2019)</div>
                            </div>

                            <div class="form-floating-custom">
                                <textarea
                                    name="about_me"
                                    id="about_me"
                                    placeholder=" "
                                    rows="4"
                                    required
                                ><?= clean($_POST['about_me'] ?? '') ?></textarea>
                                <label for="about_me">Hakkımda<span class="required">*</span></label>
                                <div class="small-text">Kendinizi tanıtın, çalışma alanlarınız hakkında bilgi verin (En az 50 karakter)</div>
                            </div>

                            <!-- Belgeler -->
                            <div class="section-title">
                                <i class="fas fa-file-alt"></i> Belgeler
                            </div>

                            <div class="file-upload-wrapper">
                                <input
                                    type="file"
                                    name="diploma"
                                    id="diploma"
                                    class="file-upload-input"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    required
                                >
                                <label for="diploma" class="file-upload-label" id="diplomaLabel">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="file-upload-text">Diploma Yükle</div>
                                    <div class="file-upload-hint">PDF, JPG veya PNG (Max 5MB)</div>
                                </label>
                            </div>

                            <!-- Ödeme Bilgileri -->
                            <div class="section-title">
                                <i class="fas fa-credit-card"></i> Ödeme Bilgileri
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-floating-custom">
                                        <input
                                            type="text"
                                            name="iban"
                                            id="iban"
                                            placeholder=" "
                                            maxlength="32"
                                            value="<?= clean($_POST['iban'] ?? '') ?>"
                                        >
                                        <label for="iban">IBAN Numarası (Opsiyonel)</label>
                                        <div class="small-text">Ödemelerinizin aktarılacağı hesap (TR ile başlamalı)</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating-custom">
                                        <input
                                            type="number"
                                            name="consultation_fee"
                                            id="consultation_fee"
                                            placeholder=" "
                                            min="0"
                                            step="0.01"
                                            value="<?= clean($_POST['consultation_fee'] ?? '') ?>"
                                            required
                                        >
                                        <label for="consultation_fee">Danışmanlık Ücreti (₺)<span class="required">*</span></label>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info alert-custom">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Bilgilendirme:</strong> Kaydınız tamamlandıktan sonra belgeleriniz yönetici tarafından
                                incelenecek ve onaylanacaktır. Onay süreci 1-2 iş günü sürebilir.
                            </div>

                            <div class="custom-checkbox">
                                <input
                                    type="checkbox"
                                    name="terms"
                                    id="terms"
                                    required
                                >
                                <label for="terms">
                                    <a href="/page/kullanim-sartlari" target="_blank">Kullanım Şartları</a>,
                                    <a href="/page/gizlilik-politikasi" target="_blank">Gizlilik Politikası</a> ve
                                    <a href="/page/diyetisyen-sozlesmesi" target="_blank">Diyetisyen Sözleşmesi</a>'ni
                                    okudum, kabul ediyorum.
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
                                <a href="/register-client.php" class="btn btn-outline-custom">
                                    <i class="fas fa-user me-2"></i>Danışan Kayıt
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

        // File Upload Handler
        const diplomaInput = document.getElementById('diploma');
        const diplomaLabel = document.getElementById('diplomaLabel');

        diplomaInput?.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                diplomaLabel.classList.add('has-file');
                diplomaLabel.innerHTML = `
                    <div class="file-upload-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="file-upload-text">${file.name}</div>
                    <div class="file-upload-hint">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                `;
            }
        });
    </script>
</body>
</html>
