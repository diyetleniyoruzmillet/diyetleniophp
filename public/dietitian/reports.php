<?php
/**
 * Diyetlenio - Diyetisyen Raporlar ve İstatistikler
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Genel İstatistikler
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT client_id) as total_clients,
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
    FROM appointments
    WHERE dietitian_id = ?
");
$stmt->execute([$userId]);
$appointmentStats = $stmt->fetch();

// Aylık Gelir
$stmt = $conn->prepare("
    SELECT SUM(amount) as monthly_income
    FROM payments
    WHERE dietitian_id = ? AND status = 'completed'
    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
    AND YEAR(payment_date) = YEAR(CURRENT_DATE())
");
$stmt->execute([$userId]);
$monthlyIncome = $stmt->fetch()['monthly_income'] ?? 0;

// Aktif Diyet Planları
$stmt = $conn->prepare("
    SELECT COUNT(*) as active_plans
    FROM diet_plans
    WHERE dietitian_id = ? AND is_active = 1
");
$stmt->execute([$userId]);
$activePlans = $stmt->fetch()['active_plans'];

// Ortalama Puan
$stmt = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
    FROM reviews
    WHERE dietitian_id = ?
");
$stmt->execute([$userId]);
$reviewStats = $stmt->fetch();

// Aylık Randevu Grafiği (Son 6 ay)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM appointments
        WHERE dietitian_id = ?
        AND DATE_FORMAT(appointment_date, '%Y-%m') = ?
    ");
    $stmt->execute([$userId, $date]);
    $monthlyData[] = [
        'month' => date('F', strtotime($date . '-01')),
        'count' => $stmt->fetch()['count']
    ];
}

// En Çok Randevu Alan Danışanlar
$stmt = $conn->prepare("
    SELECT u.full_name, COUNT(*) as appointment_count
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    WHERE a.dietitian_id = ?
    GROUP BY a.client_id
    ORDER BY appointment_count DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$topClients = $stmt->fetchAll();

$pageTitle = 'Raporlar ve İstatistikler';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
