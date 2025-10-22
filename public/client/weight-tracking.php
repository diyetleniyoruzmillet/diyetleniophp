<?php
/**
 * Diyetlenio - Kilo Takibi
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Yeni kayıt ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_weight'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $weight = (float)$_POST['weight'];
        $measurementDate = $_POST['measurement_date'];
        $notes = trim($_POST['notes'] ?? '');

        if ($weight > 0) {
            $stmt = $conn->prepare("
                INSERT INTO weight_tracking (client_id, weight, measurement_date, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $weight, $measurementDate, $notes]);
            setFlash('success', 'Kilo kaydı başarıyla eklendi.');
            redirect('/client/weight-tracking.php');
        }
    }
}

// Kilo geçmişini çek
$stmt = $conn->prepare("
    SELECT * FROM weight_tracking
    WHERE client_id = ?
    ORDER BY measurement_date DESC
");
$stmt->execute([$userId]);
$weightHistory = $stmt->fetchAll();

// Hedef kilo
$stmt = $conn->prepare("SELECT target_weight FROM client_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
$targetWeight = $profile['target_weight'] ?? null;

$pageTitle = 'Kilo Takibi';
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
        .card-custom {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
