<?php
/**
 * Diyetlenio - Randevu Detay
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$appointmentId = $_GET['id'] ?? null;

if (!$appointmentId) {
    redirect('/dietitian/appointments.php');
}

// Randevu bilgileri
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as client_name, u.email as client_email, u.phone as client_phone
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    WHERE a.id = ? AND a.dietitian_id = ?
");
$stmt->execute([$appointmentId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/dietitian/appointments.php');
}

// Not ekleme/güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $notes = trim($_POST['notes']);
        $stmt = $conn->prepare("UPDATE appointments SET notes = ? WHERE id = ?");
        $stmt->execute([$notes, $appointmentId]);
        setFlash('success', 'Notlar kaydedildi.');
        redirect('/dietitian/appointment-detail.php?id=' . $appointmentId);
    }
}

$pageTitle = 'Randevu Detayı';
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
