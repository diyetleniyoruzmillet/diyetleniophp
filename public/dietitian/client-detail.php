<?php
/**
 * Diyetlenio - Danışan Detay Sayfası
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();
$clientId = $_GET['id'] ?? null;

if (!$clientId) {
    setFlash('error', 'Danışan bulunamadı.');
    redirect('/dietitian/clients.php');
}

// Danışan bilgileri
$stmt = $conn->prepare("
    SELECT u.*, cp.*
    FROM users u
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    WHERE u.id = ? AND u.user_type = 'client'
");
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    setFlash('error', 'Danışan bulunamadı.');
    redirect('/dietitian/clients.php');
}

// Randevu istatistikleri
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM appointments
    WHERE client_id = ? AND dietitian_id = ?
");
$stmt->execute([$clientId, $userId]);
$appointmentStats = $stmt->fetch();

// Son randevular
$stmt = $conn->prepare("
    SELECT * FROM appointments
    WHERE client_id = ? AND dietitian_id = ?
    ORDER BY appointment_date DESC
    LIMIT 5
");
$stmt->execute([$clientId, $userId]);
$recentAppointments = $stmt->fetchAll();

// Kilo geçmişi
$stmt = $conn->prepare("
    SELECT * FROM weight_tracking
    WHERE client_id = ?
    ORDER BY measurement_date DESC
    LIMIT 10
");
$stmt->execute([$clientId]);
$weightHistory = $stmt->fetchAll();

// Aktif diyet planı
$stmt = $conn->prepare("
    SELECT * FROM diet_plans
    WHERE client_id = ? AND dietitian_id = ? AND is_active = 1
    LIMIT 1
");
$stmt->execute([$clientId, $userId]);
$activePlan = $stmt->fetch();

$pageTitle = 'Danışan Detayı';
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
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
