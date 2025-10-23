<?php
/**
 * Diyetlenio - Diyetisyen Profil Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece diyetisyen erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Diyetisyen profilini çek
$stmt = $conn->prepare("
    SELECT u.*, dp.*
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
    } else {
        // Validator ile input doğrulama
        $validator = new Validator($_POST);
        $validator
            ->required(['full_name', 'email', 'phone', 'title', 'specialization', 'experience_years', 'consultation_fee'])
            ->min('full_name', 3)
            ->max('full_name', 100)
            ->email('email')
            ->phone('phone')
            ->min('title', 2)
            ->max('title', 100)
            ->min('specialization', 3)
            ->max('specialization', 100)
            ->between('experience_years', 0, 50)
            ->between('consultation_fee', 0, 10000);

        // IBAN validasyonu (opsiyonel)
        if (!empty($_POST['iban'])) {
            $validator->pattern('iban', REGEX_IBAN, 'Geçerli bir IBAN giriniz.');
        }

        // Şifre değişikliği validasyonu (opsiyonel)
        if (!empty($_POST['new_password'])) {
            $validator
                ->required(['confirm_password'])
                ->min('new_password', 8)
                ->match('new_password', 'confirm_password');
        }

        // Email benzersizlik kontrolü
        if (!empty($_POST['email']) && $_POST['email'] !== $profile['email']) {
            $validator->unique('email', 'users', 'email', $userId);
        }

        $errors = [];

        // Validation sonuçlarını kontrol et
        if ($validator->fails()) {
            $errors = array_map(function($fieldErrors) {
                return is_array($fieldErrors) ? $fieldErrors[0] : $fieldErrors;
            }, $validator->errors());
        }

        if (count($errors) === 0) {
            // Değerleri al
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $title = trim($_POST['title']);
            $specialization = trim($_POST['specialization']);
            $experienceYears = (int)$_POST['experience_years'];
            $aboutMe = trim($_POST['about_me'] ?? '');
            $education = trim($_POST['education'] ?? '');
            $consultationFee = (float)$_POST['consultation_fee'];
            $acceptsOnline = isset($_POST['accepts_online']) ? 1 : 0;
            $acceptsInPerson = isset($_POST['accepts_in_person']) ? 1 : 0;
            $iban = trim($_POST['iban'] ?? '');
        }

        if (count($errors) === 0) {
            try {
                $conn->beginTransaction();

                // Kullanıcı bilgilerini güncelle
                $stmt = $conn->prepare("
                    UPDATE users
                    SET full_name = ?, email = ?, phone = ?
                    WHERE id = ?
                ");
                $stmt->execute([$fullName, $email, $phone, $userId]);

                // Diyetisyen profil bilgilerini güncelle
                $stmt = $conn->prepare("
                    UPDATE dietitian_profiles
                    SET title = ?,
                        specialization = ?,
                        experience_years = ?,
                        about_me = ?,
                        education = ?,
                        consultation_fee = ?,
                        accepts_online_sessions = ?,
                        accepts_in_person = ?,
                        iban = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $title,
                    $specialization,
                    $experienceYears,
                    $aboutMe,
                    $education,
                    $consultationFee,
                    $acceptsOnline,
                    $acceptsInPerson,
                    $iban,
                    $userId
                ]);

                // Şifre değişikliği (Validator zaten doğruladı)
                if (!empty($_POST['new_password'])) {
                    $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                }

                $conn->commit();
                setFlash('success', 'Profiliniz başarıyla güncellendi.');
                redirect('/dietitian/profile.php');

            } catch (Exception $e) {
                $conn->rollBack();
                error_log('Profile update error: ' . $e->getMessage());
                setFlash('error', 'Profil güncellenirken bir hata oluştu.');
            }
        }

        if (count($errors) > 0) {
            setFlash('error', implode('<br>', $errors));
        }
    }
}

// Profili tekrar çek (güncellenmiş hali için)
$stmt = $conn->prepare("
    SELECT u.*, dp.*
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$pageTitle = 'Profil Ayarları';
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
        body { background: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #28a745 0%, #20c997 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.2);
        }
        .content-wrapper { padding: 30px; }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section-title {
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Anasayfa
                        </a>
                        <a class="nav-link" href="/dietitian/clients.php">
                            <i class="fas fa-users me-2"></i>Danışanlarım
                        </a>
                        <a class="nav-link" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevular
                        </a>
                        <a class="nav-link" href="/dietitian/availability.php">
                            <i class="fas fa-clock me-2"></i>Müsaitlik
                        </a>
                        <a class="nav-link" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
                        </a>
                        <a class="nav-link" href="/dietitian/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link active" href="/dietitian/profile.php">
                            <i class="fas fa-user me-2"></i>Profilim
                        </a>
                        <hr class="text-white-50 my-3">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
                    <h2 class="mb-4">Profil Ayarları</h2>

                    <?php if (hasFlash()): ?>
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
                    <?php endif; ?>

                    <?php if (!$profile['is_approved']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Onay Bekliyor:</strong> Profiliniz admin onayı bekliyor. Onaylandıktan sonra danışanlar sizi görebilecek.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                        <div class="profile-card p-4 mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-user-circle me-2"></i>Kişisel Bilgiler
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ad Soyad *</label>
                                    <input type="text" name="full_name" class="form-control"
                                           value="<?= clean($profile['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= clean($profile['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telefon *</label>
                                    <input type="tel" name="phone" class="form-control"
                                           value="<?= clean($profile['phone']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ünvan *</label>
                                    <input type="text" name="title" class="form-control"
                                           value="<?= clean($profile['title']) ?>"
                                           placeholder="Örn: Uzman Diyetisyen" required>
                                </div>
                            </div>
                        </div>

                        <div class="profile-card p-4 mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-stethoscope me-2"></i>Mesleki Bilgiler
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Uzmanlık Alanı *</label>
                                    <select name="specialization" class="form-select" required>
                                        <option value="">Seçiniz</option>
                                        <option value="Kilo Yönetimi" <?= $profile['specialization'] === 'Kilo Yönetimi' ? 'selected' : '' ?>>Kilo Yönetimi</option>
                                        <option value="Spor Beslenmesi" <?= $profile['specialization'] === 'Spor Beslenmesi' ? 'selected' : '' ?>>Spor Beslenmesi</option>
                                        <option value="Çocuk Beslenmesi" <?= $profile['specialization'] === 'Çocuk Beslenmesi' ? 'selected' : '' ?>>Çocuk Beslenmesi</option>
                                        <option value="Hamilelik Diyeti" <?= $profile['specialization'] === 'Hamilelik Diyeti' ? 'selected' : '' ?>>Hamilelik Diyeti</option>
                                        <option value="Hastalık Diyeti" <?= $profile['specialization'] === 'Hastalık Diyeti' ? 'selected' : '' ?>>Hastalık Diyeti</option>
                                        <option value="Vejetaryen/Vegan" <?= $profile['specialization'] === 'Vejetaryen/Vegan' ? 'selected' : '' ?>>Vejetaryen/Vegan</option>
                                        <option value="Detoks" <?= $profile['specialization'] === 'Detoks' ? 'selected' : '' ?>>Detoks</option>
                                        <option value="Genel Beslenme" <?= $profile['specialization'] === 'Genel Beslenme' ? 'selected' : '' ?>>Genel Beslenme</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Deneyim (Yıl) *</label>
                                    <input type="number" name="experience_years" class="form-control" min="0" max="50"
                                           value="<?= $profile['experience_years'] ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Eğitim Bilgileri *</label>
                                    <textarea name="education" class="form-control" rows="3" required><?= clean($profile['education']) ?></textarea>
                                    <small class="text-muted">Mezun olduğunuz üniversite, bölüm ve sertifikalarınızı yazınız.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Hakkımda *</label>
                                    <textarea name="about_me" class="form-control" rows="5" required><?= clean($profile['about_me']) ?></textarea>
                                    <small class="text-muted">Kendinizi ve çalışma yaklaşımınızı tanıtın.</small>
                                </div>
                            </div>
                        </div>

                        <div class="profile-card p-4 mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-money-bill me-2"></i>Hizmet Bilgileri
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Konsültasyon Ücreti (TL) *</label>
                                    <input type="number" name="consultation_fee" class="form-control" min="0" step="0.01"
                                           value="<?= $profile['consultation_fee'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">IBAN</label>
                                    <input type="text" name="iban" class="form-control"
                                           value="<?= clean($profile['iban'] ?? '') ?>"
                                           placeholder="TR00 0000 0000 0000 0000 0000 00">
                                    <small class="text-muted">Ödeme almak için IBAN numaranız</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block mb-2">Kabul Edilen Seans Tipleri</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="accepts_online"
                                               id="acceptsOnline" <?= $profile['accepts_online_sessions'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="acceptsOnline">
                                            <i class="fas fa-video me-1"></i>Online Görüşme
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="accepts_in_person"
                                               id="acceptsInPerson" <?= $profile['accepts_in_person'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="acceptsInPerson">
                                            <i class="fas fa-clinic-medical me-1"></i>Yüz Yüze Görüşme
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-card p-4 mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-lock me-2"></i>Şifre Değiştir
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Şifre</label>
                                    <input type="password" name="new_password" class="form-control"
                                           placeholder="Değiştirmek istemiyorsanız boş bırakın">
                                    <small class="text-muted">En az 8 karakter</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Şifre (Tekrar)</label>
                                    <input type="password" name="confirm_password" class="form-control"
                                           placeholder="Şifrenizi tekrar girin">
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="/dietitian/dashboard.php" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-2"></i>İptal
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
