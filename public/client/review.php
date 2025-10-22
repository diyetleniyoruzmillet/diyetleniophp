<?php
/**
 * Diyetlenio - Danışan Değerlendirme
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$appointmentId = $_GET['appointment'] ?? null;

if (!$appointmentId) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/client/appointments.php');
}

// Randevu bilgilerini çek
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as dietitian_name, dp.title as dietitian_title
    FROM appointments a
    INNER JOIN users u ON a.dietitian_id = u.id
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE a.id = ? AND a.client_id = ? AND a.status = 'completed'
");
$stmt->execute([$appointmentId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Geçerli bir tamamlanmış randevu bulunamadı.');
    redirect('/client/appointments.php');
}

// Daha önce değerlendirme yapılmış mı kontrol et
$stmt = $conn->prepare("SELECT * FROM reviews WHERE appointment_id = ?");
$stmt->execute([$appointmentId]);
$existingReview = $stmt->fetch();

$success = false;

// Değerlendirme gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $rating = (int)$_POST['rating'];
        $review = trim($_POST['review']);

        $errors = [];

        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Lütfen 1-5 arası puan verin.';
        }

        if (empty($review) || strlen($review) < 10) {
            $errors[] = 'Değerlendirme en az 10 karakter olmalıdır.';
        }

        if (empty($errors)) {
            if ($existingReview) {
                // Güncelle
                $stmt = $conn->prepare("
                    UPDATE reviews
                    SET rating = ?, review = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$rating, $review, $existingReview['id']]);
            } else {
                // Yeni ekle
                $stmt = $conn->prepare("
                    INSERT INTO reviews (
                        client_id, dietitian_id, appointment_id,
                        rating, review, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $appointment['dietitian_id'],
                    $appointmentId,
                    $rating,
                    $review
                ]);
            }

            $success = true;
        }
    }
}

$pageTitle = 'Değerlendirme Yap';
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
        .rating-stars {
            font-size: 2.5rem;
            cursor: pointer;
        }
        .rating-stars .star {
            color: #ddd;
            transition: color 0.2s;
        }
        .rating-stars .star.active,
        .rating-stars .star:hover {
            color: #ffc107;
        }
        .success-animation {
            text-align: center;
            padding: 50px 0;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
