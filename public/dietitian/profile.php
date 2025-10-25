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

                // Profil fotoğrafı yükleme
                if (!empty($_FILES['profile_photo']['tmp_name'])) {
                    $upload = FileUpload::uploadImage($_FILES['profile_photo'], 'profiles', [
                        'maxSize' => 5 * 1024 * 1024,
                        'maxWidth' => 1200,
                        'maxHeight' => 1200,
                    ]);
                    if ($upload['success']) {
                        // Eski fotoğrafı sil (varsa ve farklıysa)
                        if (!empty($profile['profile_photo']) && $profile['profile_photo'] !== $upload['filename']) {
                            FileUpload::delete($profile['profile_photo']);
                        }
                        // Not: upload['filename'] 'profiles/...' şeklinde döner. users.profile_photo alanına bu şekilde kaydederiz.
                        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                        $stmt->execute([$upload['filename'], $userId]);
                    } else {
                        throw new Exception($upload['error'] ?? 'Profil fotoğrafı yüklenemedi.');
                    }
                }

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
include __DIR__ . '/../../includes/dietitian_header.php';
?>

<style>
    .profile-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .section-title {
        border-bottom: 2px solid #f093fb;
        padding-bottom: 10px;
        margin-bottom: 25px;
    }
    .profile-photo-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f093fb;
    }
</style>

<h2 class="mb-4">Profil Ayarları</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

    <div class="profile-card p-4 mb-4">
        <h5 class="section-title">
            <i class="fas fa-user-circle me-2"></i>Kişisel Bilgiler
        </h5>

        <div class="row g-3">
            <div class="col-md-12 text-center mb-3">
                <?php if (!empty($profile['profile_photo'])): ?>
                    <img src="/<?= clean($profile['profile_photo']) ?>" class="profile-photo-preview mb-3" alt="Profil Fotoğrafı">
                <?php else: ?>
                    <div class="profile-photo-preview bg-light d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="fas fa-user fa-4x text-muted"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-camera me-2"></i>Fotoğraf Değiştir
                        <input type="file" name="profile_photo" accept="image/*" class="d-none">
                    </label>
                </div>
            </div>

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
                       value="<?= clean($profile['title']) ?>" placeholder="Diyetisyen, Uzman Diyetisyen vb." required>
            </div>
        </div>
    </div>

    <div class="profile-card p-4 mb-4">
        <h5 class="section-title">
            <i class="fas fa-briefcase me-2"></i>Mesleki Bilgiler
        </h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Uzmanlık Alanı *</label>
                <input type="text" name="specialization" class="form-control"
                       value="<?= clean($profile['specialization']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Deneyim (Yıl) *</label>
                <input type="number" name="experience_years" class="form-control" min="0" max="50"
                       value="<?= $profile['experience_years'] ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Hakkımda</label>
                <textarea name="about_me" class="form-control" rows="4"
                          placeholder="Kendinizden bahsedin..."><?= clean($profile['about_me'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Eğitim</label>
                <textarea name="education" class="form-control" rows="3"
                          placeholder="Eğitim bilgileriniz..."><?= clean($profile['education'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="profile-card p-4 mb-4">
        <h5 class="section-title">
            <i class="fas fa-coins me-2"></i>Ücretlendirme ve Hizmet
        </h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Danışmanlık Ücreti (₺) *</label>
                <input type="number" name="consultation_fee" class="form-control" min="0" step="0.01"
                       value="<?= $profile['consultation_fee'] ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">IBAN</label>
                <input type="text" name="iban" class="form-control"
                       value="<?= clean($profile['iban'] ?? '') ?>" placeholder="TR000000000000000000000000">
            </div>
            <div class="col-12">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="accepts_online" id="acceptsOnline"
                           <?= $profile['accepts_online_sessions'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="acceptsOnline">
                        Online Görüşme Kabul Ediyorum
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="accepts_in_person" id="acceptsInPerson"
                           <?= $profile['accepts_in_person'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="acceptsInPerson">
                        Yüz Yüze Görüşme Kabul Ediyorum
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

                </div> <!-- .content-wrapper -->
            </div> <!-- .col-md-10 -->
        </div> <!-- .row -->
    </div> <!-- .container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
