<?php
/**
 * Client Edit Profile
 * Danışan profil düzenleme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Client kontrolü
if (!$auth->check() || $auth->user()['user_type'] !== 'client') {
    header('Location: /login.php');
    exit;
}

$user_id = $auth->user()['id'];
$conn = $db->getConnection();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
        $target_weight = !empty($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;
        $health_conditions = trim($_POST['health_conditions'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        $dietary_preferences = trim($_POST['dietary_preferences'] ?? '');
        $activity_level = $_POST['activity_level'] ?? null;

        // Users tablosunu güncelle
        $stmt = $conn->prepare("
            UPDATE users
            SET full_name = :full_name, phone = :phone
            WHERE id = :user_id
        ");
        $stmt->execute([
            'full_name' => $full_name,
            'phone' => $phone,
            'user_id' => $user_id
        ]);

        // Client profile var mı kontrol et
        $check = $conn->prepare("SELECT id FROM client_profiles WHERE user_id = :user_id");
        $check->execute(['user_id' => $user_id]);
        $exists = $check->fetch();

        if ($exists) {
            // Güncelle
            $stmt = $conn->prepare("
                UPDATE client_profiles
                SET date_of_birth = :dob,
                    gender = :gender,
                    height = :height,
                    target_weight = :target_weight,
                    health_conditions = :health_conditions,
                    allergies = :allergies,
                    dietary_preferences = :dietary_preferences,
                    activity_level = :activity_level,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ");
        } else {
            // Oluştur
            $stmt = $conn->prepare("
                INSERT INTO client_profiles (
                    user_id, date_of_birth, gender, height, target_weight,
                    health_conditions, allergies, dietary_preferences, activity_level
                ) VALUES (
                    :user_id, :dob, :gender, :height, :target_weight,
                    :health_conditions, :allergies, :dietary_preferences, :activity_level
                )
            ");
        }

        $stmt->execute([
            'user_id' => $user_id,
            'dob' => $date_of_birth ?: null,
            'gender' => $gender ?: null,
            'height' => $height,
            'target_weight' => $target_weight,
            'health_conditions' => $health_conditions ?: null,
            'allergies' => $allergies ?: null,
            'dietary_preferences' => $dietary_preferences ?: null,
            'activity_level' => $activity_level ?: null
        ]);

        $_SESSION['success_message'] = 'Profiliniz başarıyla güncellendi';
        header('Location: /client/profile.php');
        exit;

    } catch (Exception $e) {
        $error_message = 'Profil güncellenirken bir hata oluştu';
        error_log('Profile update error: ' . $e->getMessage());
    }
}

// Profil bilgilerini çek
$stmt = $conn->prepare("
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    WHERE u.id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$profile = $stmt->fetch();

$pageTitle = 'Profili Düzenle';
include __DIR__ . '/../../includes/partials/header.php';
?>

<style>
    .edit-container {
        max-width: 900px;
        margin: 100px auto 50px;
        padding: 0 2rem;
    }

    .edit-card {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.5rem;
        display: block;
    }

    .required {
        color: #ef4444;
    }

    .form-control, .form-select {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #56ab2f;
        outline: none;
        box-shadow: 0 0 0 3px rgba(86, 171, 47, 0.1);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .btn {
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
    }

    .btn-secondary {
        background: #64748b;
        color: white;
    }

    .btn-secondary:hover {
        background: #475569;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: #d1fae5;
        color: #059669;
        border: 2px solid #10b981;
    }

    .alert-danger {
        background: #fee2e2;
        color: #dc2626;
        border: 2px solid #ef4444;
    }

    .form-help {
        font-size: 0.875rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
</style>

<div class="edit-container">
    <div class="edit-card">
        <h1 class="page-title">
            <i class="fas fa-edit"></i>
            Profili Düzenle
        </h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Kişisel Bilgiler -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-user me-2"></i>
                    Kişisel Bilgiler
                </h2>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Ad Soyad <span class="required">*</span>
                            </label>
                            <input type="text"
                                   name="full_name"
                                   class="form-control"
                                   value="<?= clean($profile['full_name']) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="tel"
                                   name="phone"
                                   class="form-control"
                                   value="<?= clean($profile['phone'] ?? '') ?>"
                                   placeholder="5XX XXX XX XX">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email"
                                   class="form-control"
                                   value="<?= clean($profile['email']) ?>"
                                   disabled>
                            <div class="form-help">E-posta adresi değiştirilemez</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Doğum Tarihi</label>
                            <input type="date"
                                   name="date_of_birth"
                                   class="form-control"
                                   value="<?= $profile['date_of_birth'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Cinsiyet</label>
                            <select name="gender" class="form-select">
                                <option value="">Seçiniz</option>
                                <option value="male" <?= ($profile['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Erkek</option>
                                <option value="female" <?= ($profile['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Kadın</option>
                                <option value="other" <?= ($profile['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Diğer</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Boy (cm)</label>
                            <input type="number"
                                   name="height"
                                   class="form-control"
                                   value="<?= $profile['height'] ?? '' ?>"
                                   min="50"
                                   max="250"
                                   step="0.01">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Hedef Kilo (kg)</label>
                            <input type="number"
                                   name="target_weight"
                                   class="form-control"
                                   value="<?= $profile['target_weight'] ?? '' ?>"
                                   min="20"
                                   max="300"
                                   step="0.01">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Aktivite Seviyesi</label>
                    <select name="activity_level" class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="sedentary" <?= ($profile['activity_level'] ?? '') === 'sedentary' ? 'selected' : '' ?>>Hareketsiz</option>
                        <option value="light" <?= ($profile['activity_level'] ?? '') === 'light' ? 'selected' : '' ?>>Az Hareketli (Haftada 1-3 gün)</option>
                        <option value="moderate" <?= ($profile['activity_level'] ?? '') === 'moderate' ? 'selected' : '' ?>>Orta Seviye (Haftada 3-5 gün)</option>
                        <option value="active" <?= ($profile['activity_level'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif (Haftada 6-7 gün)</option>
                        <option value="very_active" <?= ($profile['activity_level'] ?? '') === 'very_active' ? 'selected' : '' ?>>Çok Aktif (Günde 2 kez)</option>
                    </select>
                </div>
            </div>

            <!-- Sağlık Bilgileri -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-heartbeat me-2"></i>
                    Sağlık Bilgileri
                </h2>

                <div class="form-group">
                    <label class="form-label">Sağlık Durumu</label>
                    <textarea name="health_conditions"
                              class="form-control"
                              rows="4"
                              placeholder="Kronik hastalıklarınız, sağlık sorunlarınız varsa belirtiniz..."><?= clean($profile['health_conditions'] ?? '') ?></textarea>
                    <div class="form-help">Diyetisyeninizin doğru program hazırlaması için önemlidir</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alerjiler</label>
                    <textarea name="allergies"
                              class="form-control"
                              rows="3"
                              placeholder="Besin alerjileriniz varsa belirtiniz..."><?= clean($profile['allergies'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Beslenme Tercihleri -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-utensils me-2"></i>
                    Beslenme Tercihleri
                </h2>

                <div class="form-group">
                    <label class="form-label">Diyet Tercihleri</label>
                    <textarea name="dietary_preferences"
                              class="form-control"
                              rows="3"
                              placeholder="Örn: Vejeteryan, vegan, glutensiz, laktozsuz..."><?= clean($profile['dietary_preferences'] ?? '') ?></textarea>
                    <div class="form-help">Beslenme tercihlerinizi, sevmediğiniz yiyecekleri belirtebilirsiniz</div>
                </div>
            </div>

            <!-- Butonlar -->
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Değişiklikleri Kaydet
                </button>
                <a href="/client/profile.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    İptal
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
