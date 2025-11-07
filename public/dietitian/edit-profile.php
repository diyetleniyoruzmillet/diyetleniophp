<?php
/**
 * Dietitian Edit Profile
 * Diyetisyen profil düzenleme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Diyetisyen kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
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
        $title = trim($_POST['title'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $about_me = trim($_POST['about_me'] ?? '');
        $experience_years = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : null;
        $consultation_fee = !empty($_POST['consultation_fee']) ? (float)$_POST['consultation_fee'] : null;

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

        // Dietitian profiles tablosunu güncelle
        $stmt = $conn->prepare("
            UPDATE dietitian_profiles
            SET title = :title,
                specialization = :specialization,
                about_me = :about_me,
                experience_years = :experience_years,
                consultation_fee = :consultation_fee,
                updated_at = NOW()
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            'title' => $title,
            'specialization' => $specialization,
            'about_me' => $about_me,
            'experience_years' => $experience_years,
            'consultation_fee' => $consultation_fee,
            'user_id' => $user_id
        ]);

        $_SESSION['success_message'] = 'Profiliniz başarıyla güncellendi';
        header('Location: /dietitian/profile.php');
        exit;

    } catch (Exception $e) {
        $error_message = 'Profil güncellenirken bir hata oluştu';
        error_log('Profile update error: ' . $e->getMessage());
    }
}

// Profil bilgilerini çek
$stmt = $conn->prepare("
    SELECT u.*, dp.*
    FROM users u
    LEFT JOIN dietitian_profiles dp ON u.id = dp.user_id
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
        min-height: 120px;
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

                <div class="form-group">
                    <label class="form-label">E-posta</label>
                    <input type="email"
                           class="form-control"
                           value="<?= clean($profile['email']) ?>"
                           disabled>
                    <div class="form-help">E-posta adresi değiştirilemez</div>
                </div>
            </div>

            <!-- Profesyonel Bilgiler -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-briefcase me-2"></i>
                    Profesyonel Bilgiler
                </h2>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Unvan <span class="required">*</span>
                            </label>
                            <input type="text"
                                   name="title"
                                   class="form-control"
                                   value="<?= clean($profile['title'] ?? '') ?>"
                                   placeholder="Örn: Uzman Diyetisyen"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Uzmanlık Alanı</label>
                            <input type="text"
                                   name="specialization"
                                   class="form-control"
                                   value="<?= clean($profile['specialization'] ?? '') ?>"
                                   placeholder="Örn: Spor Beslenmesi">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Deneyim (Yıl)</label>
                            <input type="number"
                                   name="experience_years"
                                   class="form-control"
                                   value="<?= $profile['experience_years'] ?? '' ?>"
                                   min="0"
                                   max="50">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Danışma Ücreti (₺)</label>
                            <input type="number"
                                   name="consultation_fee"
                                   class="form-control"
                                   value="<?= $profile['consultation_fee'] ?? '' ?>"
                                   min="0"
                                   step="0.01">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Hakkımda</label>
                    <textarea name="about_me"
                              class="form-control"
                              rows="5"
                              placeholder="Kendiniz ve deneyiminiz hakkında bilgi verin..."><?= clean($profile['about_me'] ?? '') ?></textarea>
                    <div class="form-help">Danışanların sizi daha iyi tanıması için detaylı bilgi ekleyin</div>
                </div>
            </div>

            <!-- Butonlar -->
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Değişiklikleri Kaydet
                </button>
                <a href="/dietitian/profile.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    İptal
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/partials/footer.php'; ?>
