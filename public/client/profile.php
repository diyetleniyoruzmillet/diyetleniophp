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
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dateOfBirth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
        $targetWeight = !empty($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;
        $healthConditions = trim($_POST['health_conditions'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        $dietaryPreferences = trim($_POST['dietary_preferences'] ?? '');
        $activityLevel = $_POST['activity_level'] ?? null;

        $errors = [];

        // Validasyon
        if (empty($fullName)) $errors[] = 'Ad Soyad gereklidir.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir email adresi gereklidir.';
        if (empty($phone)) $errors[] = 'Telefon numarası gereklidir.';

        // Email kontrolü (başka kullanıcıda kullanılıyor mu?)
        if (!empty($email) && $email !== $profile['email']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu email adresi başka bir kullanıcı tarafından kullanılıyor.';
            }
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

                // Şifre değişikliği
                if (!empty($_POST['new_password'])) {
                    $newPassword = $_POST['new_password'];
                    $confirmPassword = $_POST['confirm_password'];

                    if ($newPassword === $confirmPassword) {
                        if (strlen($newPassword) >= 8) {
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$hashedPassword, $userId]);
                        } else {
                            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
                        }
                    } else {
                        $errors[] = 'Şifreler eşleşmiyor.';
                    }
                }

                if (count($errors) === 0) {
                    $conn->commit();
                    setFlash('success', 'Profiliniz başarıyla güncellendi.');
                    redirect('/client/profile.php');
                } else {
                    $conn->rollBack();
                }

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
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="/client/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="/client/dietitians.php">
                            <i class="fas fa-user-md me-2"></i>Diyetisyenler
                        </a>
                        <a class="nav-link" href="/client/appointments.php">
                            <i class="fas fa-calendar-check me-2"></i>Randevularım
                        </a>
                        <a class="nav-link" href="/client/diet-plans.php">
                            <i class="fas fa-clipboard-list me-2"></i>Diyet Planlarım
                        </a>
                        <a class="nav-link" href="/client/weight-tracking.php">
                            <i class="fas fa-weight me-2"></i>Kilo Takibi
                        </a>
                        <a class="nav-link" href="/client/messages.php">
                            <i class="fas fa-envelope me-2"></i>Mesajlar
                        </a>
                        <a class="nav-link active" href="/client/profile.php">
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
                                <?= $msg ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($msg = getFlash('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= $msg ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
