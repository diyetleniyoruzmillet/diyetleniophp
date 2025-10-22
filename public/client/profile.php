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
