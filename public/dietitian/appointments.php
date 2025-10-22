<?php
/**
 * Diyetlenio - Diyetisyen Randevularım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'scheduled';
$clientId = $_GET['client_id'] ?? null;

// Randevuları çek
$whereClause = "WHERE a.dietitian_id = ?";
$params = [$userId];

if ($status === 'scheduled') {
    $whereClause .= " AND a.status = 'scheduled' AND a.appointment_date >= NOW()";
} elseif ($status === 'completed') {
    $whereClause .= " AND a.status = 'completed'";
} elseif ($status === 'cancelled') {
    $whereClause .= " AND a.status = 'cancelled'";
}

if ($clientId) {
    $whereClause .= " AND a.client_id = ?";
    $params[] = $clientId;
}

$stmt = $conn->prepare("
    SELECT a.*, u.full_name as client_name, u.email as client_email,
           u.phone as client_phone
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    {$whereClause}
    ORDER BY a.appointment_date DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Randevularım';
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
        .appointment-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
