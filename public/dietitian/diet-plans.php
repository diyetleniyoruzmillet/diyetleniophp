<?php
/**
 * Diyetlenio - Diyetisyen Diyet Planları
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Plan oluşturma veya güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_plan'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $clientId = (int)$_POST['client_id'];
        $planName = trim($_POST['plan_name']);
        $description = trim($_POST['description']);
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $dailyCalories = (int)$_POST['daily_calories'];
        $dailyProtein = (int)$_POST['daily_protein'];
        $dailyCarbs = (int)$_POST['daily_carbs'];
        $dailyFat = (int)$_POST['daily_fat'];

        try {
            // Danışanın başka aktif planı varsa pasif yap
            $stmt = $conn->prepare("
                UPDATE diet_plans SET is_active = 0
                WHERE client_id = ? AND is_active = 1
            ");
            $stmt->execute([$clientId]);

            // Yeni plan oluştur
            $stmt = $conn->prepare("
                INSERT INTO diet_plans (
                    client_id, dietitian_id, plan_name, description,
                    start_date, end_date, daily_calories, daily_protein,
                    daily_carbs, daily_fat, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $clientId, $userId, $planName, $description,
                $startDate, $endDate, $dailyCalories, $dailyProtein,
                $dailyCarbs, $dailyFat
            ]);

            setFlash('success', 'Diyet planı başarıyla oluşturuldu.');
            redirect('/dietitian/diet-plans.php');
        } catch (Exception $e) {
            $error = 'Plan oluşturulurken bir hata oluştu.';
        }
    }
}

// Planları listele
$stmt = $conn->prepare("
    SELECT dp.*, u.full_name as client_name
    FROM diet_plans dp
    INNER JOIN users u ON dp.client_id = u.id
    WHERE dp.dietitian_id = ?
    ORDER BY dp.is_active DESC, dp.created_at DESC
");
$stmt->execute([$userId]);
$plans = $stmt->fetchAll();

// Danışanları listele (plan oluşturmak için)
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT client_id FROM appointments
        WHERE dietitian_id = ? AND status = 'completed'
    )
    ORDER BY u.full_name
");
$stmt->execute([$userId]);
$clients = $stmt->fetchAll();

$pageTitle = 'Diyet Planları';
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
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #28a745;
        }
        .plan-card.active {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
