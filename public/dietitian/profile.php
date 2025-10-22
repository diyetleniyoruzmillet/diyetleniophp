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
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $title = trim($_POST['title']);
        $specialization = trim($_POST['specialization']);
        $experienceYears = (int)$_POST['experience_years'];
        $aboutMe = trim($_POST['about_me']);
        $education = trim($_POST['education']);
        $consultationFee = (float)$_POST['consultation_fee'];
        $acceptsOnline = isset($_POST['accepts_online']) ? 1 : 0;
        $acceptsInPerson = isset($_POST['accepts_in_person']) ? 1 : 0;
        $iban = trim($_POST['iban'] ?? '');

        $errors = [];

        // Validasyon
        if (empty($fullName)) $errors[] = 'Ad Soyad gereklidir.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir email adresi gereklidir.';
        if (empty($phone)) $errors[] = 'Telefon numarası gereklidir.';
        if (empty($title)) $errors[] = 'Ünvan gereklidir.';
        if (empty($specialization)) $errors[] = 'Uzmanlık alanı gereklidir.';
        if ($consultationFee <= 0) $errors[] = 'Konsültasyon ücreti geçerli olmalıdır.';

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
                    redirect('/dietitian/profile.php');
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
