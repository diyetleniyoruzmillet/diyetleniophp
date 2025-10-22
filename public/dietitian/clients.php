<?php
/**
 * Diyetlenio - Diyetisyen Danışanlarım
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece diyetisyen erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$conn = $db->getConnection();
$userId = $auth->user()->getId();

// Filtre
$search = trim($_GET['search'] ?? '');

// Danışanları çek (randevusu olan)
$whereClause = "WHERE a.dietitian_id = ?";
$params = [$userId];

if (!empty($search)) {
    $whereClause .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email, u.phone, u.created_at,
           cp.date_of_birth, cp.gender, cp.height, cp.target_weight,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ? AND status = 'completed') as completed_sessions,
           (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ? AND status = 'scheduled') as upcoming_sessions,
           (SELECT COUNT(*) FROM diet_plans WHERE client_id = u.id AND dietitian_id = ? AND status = 'active') as active_plans,
           (SELECT weight FROM weight_tracking WHERE client_id = u.id ORDER BY measurement_date DESC LIMIT 1) as current_weight
    FROM appointments a
    INNER JOIN users u ON a.client_id = u.id
    LEFT JOIN client_profiles cp ON u.id = cp.user_id
    {$whereClause}
    ORDER BY u.full_name ASC
");
$stmt->execute(array_merge([$userId, $userId, $userId], $params));
$clients = $stmt->fetchAll();

$pageTitle = 'Danışanlarım';
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
        .client-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .client-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .client-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-badge {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
