<?php
/**
 * Diyetlenio - Danışan Diyet Planları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$status = $_GET['status'] ?? 'active';
$planId = $_GET['id'] ?? null;

// Diyet planlarını çek
$whereClause = "WHERE dp.client_id = ?";
$params = [$userId];

if ($status === 'active') {
    $whereClause .= " AND dp.is_active = 1";
} elseif ($status === 'past') {
    $whereClause .= " AND dp.is_active = 0";
}

$stmt = $conn->prepare("
    SELECT dp.*, u.full_name as dietitian_name, dpr.title as dietitian_title
    FROM diet_plans dp
    INNER JOIN users u ON dp.dietitian_id = u.id
    INNER JOIN dietitian_profiles dpr ON u.id = dpr.user_id
    {$whereClause}
    ORDER BY dp.created_at DESC
");
$stmt->execute($params);
$plans = $stmt->fetchAll();

// Seçili planın detaylarını çek
$selectedPlan = null;
$planMeals = [];
if ($planId) {
    $stmt = $conn->prepare("
        SELECT dp.*, u.full_name as dietitian_name, dpr.title as dietitian_title,
               dpr.phone as dietitian_phone
        FROM diet_plans dp
        INNER JOIN users u ON dp.dietitian_id = u.id
        INNER JOIN dietitian_profiles dpr ON u.id = dpr.user_id
        WHERE dp.id = ? AND dp.client_id = ?
    ");
    $stmt->execute([$planId, $userId]);
    $selectedPlan = $stmt->fetch();

    if ($selectedPlan) {
        // Plan öğünlerini çek
        $stmt = $conn->prepare("
            SELECT * FROM plan_meals
            WHERE plan_id = ?
            ORDER BY day_of_week, meal_time
        ");
        $stmt->execute([$planId]);
        $planMeals = $stmt->fetchAll();
    }
}

$pageTitle = 'Diyet Planlarım';
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
        .plan-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #28a745;
        }
        .plan-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .plan-card.active {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .meal-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .day-section {
            margin-bottom: 30px;
        }
        .day-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .meal-time-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .breakfast { background: #fff3cd; color: #856404; }
        .lunch { background: #d1ecf1; color: #0c5460; }
        .dinner { background: #f8d7da; color: #721c24; }
        .snack { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
