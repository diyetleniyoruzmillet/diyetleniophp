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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .section-title {
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .file-upload-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-upload-label {
            cursor: pointer;
            display: inline-block;
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .file-upload-label:hover {
            background: #e9ecef;
            border-color: #28a745;
        }
        .file-uploaded {
            background: #d4edda;
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card register-card border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-md fa-3x text-success mb-3"></i>
                            <h3>Diyetisyen Kayıt</h3>
                            <p class="text-muted">Profesyonel kariyerinize online olarak devam edin</p>
                        </div>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Kayıt Başarılı!</h5>
                                <p>
                                    Diyetisyen kaydınız alınmıştır. Hesabınız admin onayından sonra aktif edilecektir.
                                </p>
                                <p class="mb-0">
                                    <strong>Sonraki Adımlar:</strong>
                                </p>
                                <ol>
                                    <li>Email adresinize gönderilen doğrulama linkine tıklayın</li>
                                    <li>Yönetici onayını bekleyin (1-2 iş günü)</li>
                                    <li>Onay sonrası giriş yaparak profilinizi tamamlayın</li>
                                </ol>
                                <hr>
                                <a href="/" class="btn btn-success">
                                    <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                                </a>
                            </div>
                        <?php else: ?>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <strong>Lütfen aşağıdaki hataları düzeltin:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= clean($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="/register-dietitian.php" enctype="multipart/form-data" id="registerForm">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                <!-- Kişisel Bilgiler -->
                                <h5 class="section-title">
                                    <i class="fas fa-user me-2"></i>Kişisel Bilgiler
                                </h5>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Ad Soyad <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="full_name"
                                            class="form-control"
                                            placeholder="Dr. Ahmet Yılmaz"
                                            value="<?= clean($_POST['full_name'] ?? '') ?>"
                                            required
                                        >
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Unvan <span class="text-danger">*</span>
                                        </label>
                                        <select name="title" class="form-select" required>
                                            <option value="">Seçiniz</option>
                                            <option value="Diyetisyen" <?= ($_POST['title'] ?? '') === 'Diyetisyen' ? 'selected' : '' ?>>Diyetisyen</option>
                                            <option value="Uzman Diyetisyen" <?= ($_POST['title'] ?? '') === 'Uzman Diyetisyen' ? 'selected' : '' ?>>Uzman Diyetisyen</option>
                                            <option value="Beslenme Uzmanı" <?= ($_POST['title'] ?? '') === 'Beslenme Uzmanı' ? 'selected' : '' ?>>Beslenme Uzmanı</option>
                                            <option value="Klinik Diyetisyen" <?= ($_POST['title'] ?? '') === 'Klinik Diyetisyen' ? 'selected' : '' ?>>Klinik Diyetisyen</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Email Adresi <span class="text-danger">*</span>
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
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Telefon <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="tel"
                                            name="phone"
                                            class="form-control"
                                            placeholder="0555 123 4567"
                                            value="<?= clean($_POST['phone'] ?? '') ?>"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Şifre <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="password"
                                            name="password"
                                            class="form-control"
                                            id="password"
                                            placeholder="En az 8 karakter"
                                            required
                                        >
                                        <small class="text-muted">Büyük harf, küçük harf ve rakam içermeli</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Şifre Tekrar <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="password"
                                            name="password_confirm"
                                            class="form-control"
                                            placeholder="Şifrenizi tekrar girin"
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Mesleki Bilgiler -->
                                <h5 class="section-title mt-4">
                                    <i class="fas fa-briefcase me-2"></i>Mesleki Bilgiler
                                </h5>

                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label">
                                            Uzmanlık Alanları <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="specialization"
                                            class="form-control"
                                            placeholder="Örn: Kilo Yönetimi, Sporcu Beslenmesi, Diyabet"
                                            value="<?= clean($_POST['specialization'] ?? '') ?>"
                                            required
                                        >
                                        <small class="text-muted">Virgül ile ayırarak yazınız</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Tecrübe (Yıl) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            name="experience_years"
                                            class="form-control"
                                            min="0"
                                            max="50"
                                            value="<?= clean($_POST['experience_years'] ?? '') ?>"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Eğitim Bilgileri <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        name="education"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Örn: İstanbul Üniversitesi, Beslenme ve Diyetetik Bölümü (2015-2019)"
                                        required
                                    ><?= clean($_POST['education'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Hakkımda <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        name="about_me"
                                        class="form-control"
                                        rows="4"
                                        placeholder="Kendinizi tanıtın, çalışma alanlarınız, yaklaşımınız hakkında bilgi verin (En az 50 karakter)"
                                        required
                                    ><?= clean($_POST['about_me'] ?? '') ?></textarea>
                                    <small class="text-muted">Danışanlar bu bilgiyi görecektir</small>
                                </div>

                                <!-- Belgeler -->
                                <h5 class="section-title mt-4">
                                    <i class="fas fa-file-alt me-2"></i>Belgeler
                                </h5>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Diploma <span class="text-danger">*</span>
                                    </label>
                                    <div class="file-upload-wrapper">
                                        <input
                                            type="file"
                                            name="diploma"
                                            id="diploma"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            required
                                        >
                                        <label for="diploma" class="file-upload-label" id="diplomaLabel">
                                            <i class="fas fa-upload me-2"></i>Diploma Yükle (PDF, JPG, PNG - Max 5MB)
                                        </label>
                                    </div>
                                    <div id="diplomaInfo" class="text-success mt-2" style="display: none;">
                                        <i class="fas fa-check-circle me-2"></i><span id="diplomaFileName"></span>
                                    </div>
                                </div>

                                <!-- Ödeme Bilgileri -->
                                <h5 class="section-title mt-4">
                                    <i class="fas fa-credit-card me-2"></i>Ödeme Bilgileri
                                </h5>

                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label class="form-label">
                                            IBAN Numarası <small class="text-muted">(Opsiyonel)</small>
                                        </label>
                                        <input
                                            type="text"
                                            name="iban"
                                            class="form-control"
                                            placeholder="TR00 0000 0000 0000 0000 0000 00"
                                            value="<?= clean($_POST['iban'] ?? '') ?>"
                                            maxlength="32"
                                        >
                                        <small class="text-muted">Ödemelerinizin aktarılacağı hesap</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Danışmanlık Ücreti (₺) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            name="consultation_fee"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            value="<?= clean($_POST['consultation_fee'] ?? '') ?>"
                                            required
                                        >
                                    </div>
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
                                        <a href="/page/kullanim-sartlari" target="_blank">Kullanım Şartları</a>,
                                        <a href="/page/gizlilik-politikasi" target="_blank">Gizlilik Politikası</a> ve
                                        <a href="/page/diyetisyen-sozlesmesi" target="_blank">Diyetisyen Sözleşmesi</a>'ni
                                        okudum, kabul ediyorum.
                                    </label>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Bilgilendirme:</strong> Kaydınız tamamlandıktan sonra belgeleriniz yönetici tarafından
                                    incelenecek ve onaylanacaktır. Onay süreci 1-2 iş günü sürebilir.
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                                    </button>
                                </div>

                                <hr>

                                <div class="text-center">
                                    <p class="text-muted mb-2">Zaten hesabınız var mı?</p>
                                    <a href="/login.php" class="btn btn-outline-success me-2">
                                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                    </a>
                                    <a href="/register-client.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user me-2"></i>Danışan Kayıt
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
        // Diploma dosyası seçildiğinde
        document.getElementById('diploma')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const label = document.getElementById('diplomaLabel');
            const info = document.getElementById('diplomaInfo');
            const fileName = document.getElementById('diplomaFileName');

            if (file) {
                label.classList.add('file-uploaded');
                label.innerHTML = '<i class="fas fa-check-circle me-2"></i>Diploma Yüklendi';
                fileName.textContent = file.name;
                info.style.display = 'block';
            }
        });
    </script>
</body>
</html>
