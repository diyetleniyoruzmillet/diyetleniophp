<?php
/**
 * Diyetlenio - Danışan Profil Yönetimi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece client erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Kullanıcı ve client profil bilgilerini çek
$stmt = $conn->prepare("
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
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
            ->required(['full_name', 'email', 'phone'])
            ->min('full_name', 3)
            ->max('full_name', 100)
            ->email('email')
            ->phone('phone');

        // Opsiyonel alanların validasyonu
        if (!empty($_POST['height'])) {
            $validator->between('height', 50, 250); // cm cinsinden
        }

        if (!empty($_POST['target_weight'])) {
            $validator->between('target_weight', 30, 300); // kg cinsinden
        }

        if (!empty($_POST['date_of_birth'])) {
            $validator->date('date_of_birth', 'Y-m-d');
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
            $dateOfBirth = $_POST['date_of_birth'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
            $targetWeight = !empty($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;
            $healthConditions = trim($_POST['health_conditions'] ?? '');
            $allergies = trim($_POST['allergies'] ?? '');
            $dietaryPreferences = trim($_POST['dietary_preferences'] ?? '');
            $activityLevel = $_POST['activity_level'] ?? null;
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

                // Client profil bilgilerini güncelle (yoksa oluştur)
                if ($profile['user_id']) {
                    // Update
                    $stmt = $conn->prepare("
                        UPDATE client_profiles
                        SET date_of_birth = ?,
                            gender = ?,
                            height = ?,
                            target_weight = ?,
                            health_conditions = ?,
                            allergies = ?,
                            dietary_preferences = ?,
                            activity_level = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $dateOfBirth ?: null,
                        $gender ?: null,
                        $height,
                        $targetWeight,
                        $healthConditions,
                        $allergies,
                        $dietaryPreferences,
                        $activityLevel,
                        $userId
                    ]);
                } else {
                    // Insert
                    $stmt = $conn->prepare("
                        INSERT INTO client_profiles
                        (user_id, date_of_birth, gender, height, target_weight, health_conditions, allergies, dietary_preferences, activity_level)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $userId,
                        $dateOfBirth ?: null,
                        $gender ?: null,
                        $height,
                        $targetWeight,
                        $healthConditions,
                        $allergies,
                        $dietaryPreferences,
                        $activityLevel
                    ]);
                }

                // Şifre değişikliği (Validator zaten doğruladı)
                if (!empty($_POST['new_password'])) {
                    $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                }

                $conn->commit();
                setFlash('success', 'Profiliniz başarıyla güncellendi.');
                redirect('/client/profile.php');

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
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Son kilo ölçümü
$stmt = $conn->prepare("
    SELECT weight FROM weight_tracking
    WHERE client_id = ?
    ORDER BY measurement_date DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$lastWeight = $stmt->fetch();

$pageTitle = 'Profil Ayarları';
include __DIR__ . '/../../includes/client_header.php';
?>

<style>
    .profile-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .section-title {
        border-bottom: 2px solid #56ab2f;
        padding-bottom: 10px;
        margin-bottom: 25px;
    }
    .info-box {
        background: #f8f9fa;
        border-left: 4px solid #56ab2f;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>

<h2 class="mb-4">Profil Ayarları</h2>

<?php if ($lastWeight): ?>
    <div class="info-box">
        <div class="d-flex align-items-center">
            <i class="fas fa-weight fa-2x text-success me-3"></i>
            <div>
                <strong>Mevcut Kilonuz:</strong> <?= number_format($lastWeight['weight'], 1) ?> kg
                <?php if ($profile['target_weight']): ?>
                    <br><small class="text-muted">Hedef: <?= number_format($profile['target_weight'], 1) ?> kg</small>
                <?php endif; ?>
            </div>
        </div>
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
                <label class="form-label">Doğum Tarihi</label>
                <input type="date" name="date_of_birth" class="form-control"
                       value="<?= $profile['date_of_birth'] ?? '' ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cinsiyet</label>
                <select name="gender" class="form-select">
                    <option value="">Seçiniz</option>
                    <option value="male" <?= ($profile['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Erkek</option>
                    <option value="female" <?= ($profile['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Kadın</option>
                    <option value="other" <?= ($profile['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Diğer</option>
                </select>
            </div>
        </div>
    </div>

    <div class="profile-card p-4 mb-4">
        <h5 class="section-title">
            <i class="fas fa-heartbeat me-2"></i>Sağlık Bilgileri
        </h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Boy (cm)</label>
                <input type="number" name="height" class="form-control" min="0" max="250" step="0.1"
                       value="<?= $profile['height'] ?? '' ?>" placeholder="170">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hedef Kilo (kg)</label>
                <input type="number" name="target_weight" class="form-control" min="0" max="300" step="0.1"
                       value="<?= $profile['target_weight'] ?? '' ?>" placeholder="70">
            </div>
            <div class="col-md-6">
                <label class="form-label">Aktivite Seviyesi</label>
                <select name="activity_level" class="form-select">
                    <option value="">Seçiniz</option>
                    <option value="sedentary" <?= ($profile['activity_level'] ?? '') === 'sedentary' ? 'selected' : '' ?>>Hareketsiz (Ofis işi)</option>
                    <option value="light" <?= ($profile['activity_level'] ?? '') === 'light' ? 'selected' : '' ?>>Az Hareketli (Haftada 1-3 gün egzersiz)</option>
                    <option value="moderate" <?= ($profile['activity_level'] ?? '') === 'moderate' ? 'selected' : '' ?>>Orta (Haftada 3-5 gün egzersiz)</option>
                    <option value="active" <?= ($profile['activity_level'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif (Haftada 6-7 gün egzersiz)</option>
                    <option value="very_active" <?= ($profile['activity_level'] ?? '') === 'very_active' ? 'selected' : '' ?>>Çok Aktif (Günde 2 kez egzersiz)</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Sağlık Durumu / Hastalıklar</label>
                <textarea name="health_conditions" class="form-control" rows="3"
                          placeholder="Kronik hastalıklarınız, kullandığınız ilaçlar vb."><?= clean($profile['health_conditions'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Alerjiler</label>
                <textarea name="allergies" class="form-control" rows="2"
                          placeholder="Besin alerjileriniz veya intoleranslarınız"><?= clean($profile['allergies'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Diyet Tercihleri</label>
                <textarea name="dietary_preferences" class="form-control" rows="2"
                          placeholder="Vejetaryen, vegan, glutensiz vb."><?= clean($profile['dietary_preferences'] ?? '') ?></textarea>
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
        <a href="/client/dashboard.php" class="btn btn-secondary me-2">
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
