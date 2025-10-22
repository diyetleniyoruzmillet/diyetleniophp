<?php
/**
 * Diyetlenio - Diyetisyen Ödeme Takibi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'all';
$month = $_GET['month'] ?? date('Y-m');

// Ödemeleri çek
$whereClause = "WHERE p.dietitian_id = ?";
$params = [$userId];

if ($status !== 'all') {
    $whereClause .= " AND p.status = ?";
    $params[] = $status;
}

if ($month) {
    $whereClause .= " AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
    $params[] = $month;
}

$stmt = $conn->prepare("
    SELECT p.*, u.full_name as client_name, a.appointment_date
    FROM payments p
    INNER JOIN appointments a ON p.appointment_id = a.id
    INNER JOIN users u ON p.client_id = u.id
    {$whereClause}
    ORDER BY p.payment_date DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// İstatistikler
$stmt = $conn->prepare("
    SELECT
        COUNT(*) as total_count,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount
    FROM payments
    WHERE dietitian_id = ?
    " . ($month ? "AND DATE_FORMAT(payment_date, '%Y-%m') = ?" : "")
);
$statsParams = [$userId];
if ($month) $statsParams[] = $month;
$stmt->execute($statsParams);
$stats = $stmt->fetch();

$pageTitle = 'Ödeme Takibi';
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
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
