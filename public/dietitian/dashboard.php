<?php
/**
 * Diyetlenio - Diyetisyen Dashboard
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece diyetisyen erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'dietitian') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

$user = $auth->user();
$userId = $user->getId();

try {
    $conn = $db->getConnection();

    // Diyetisyen profil bilgilerini çek
    $stmt = $conn->prepare("
        SELECT * FROM dietitian_profiles WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    // Bugünkü randevular
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as client_name, u.phone as client_phone
        FROM appointments a
        INNER JOIN users u ON a.client_id = u.id
        WHERE a.dietitian_id = ? AND DATE(a.appointment_date) = CURDATE()
        ORDER BY a.start_time ASC
    ");
    $stmt->execute([$userId]);
    $todayAppointments = $stmt->fetchAll();

    // Genel istatistikler
    $stmt = $conn->prepare("
        SELECT
            COUNT(DISTINCT client_id) as total_clients,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as upcoming_appointments,
            SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today_count
        FROM appointments
        WHERE dietitian_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    // Bu ay gelir
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_income
        FROM payments
        WHERE dietitian_id = ?
        AND status = 'approved'
        AND MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$userId]);
    $income = $stmt->fetch();

    // Son danışanlar
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.full_name, u.email, u.created_at,
               (SELECT COUNT(*) FROM appointments WHERE client_id = u.id AND dietitian_id = ?) as appointment_count
        FROM users u
        INNER JOIN appointments a ON u.id = a.client_id
        WHERE a.dietitian_id = ?
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId, $userId]);
    $recentClients = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Dietitian dashboard error: ' . $e->getMessage());
    $profile = null;
    $todayAppointments = [];
    $stats = $income = [];
    $recentClients = [];
}

$pageTitle = 'Diyetisyen Dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', sans-serif;
        }

        /* Modern Sidebar - Orange gradient for dietitians */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 4px 0 30px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: pulse 4s ease-in-out infinite;
        }

        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .sidebar-subtitle {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 14px 20px;
            margin: 6px 0;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
            min-width: 20px;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.25);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            font-weight: 600;
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 70%;
            background: white;
            border-radius: 0 4px 4px 0;
        }

        .content-wrapper {
            padding: 35px;
        }

        /* Welcome Header */
        .welcome-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease;
        }

        .welcome-header h2 {
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Modern Stat Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
            animation: fadeInUp 0.6s ease both;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(240, 147, 251, 0.08) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-card-1 { animation-delay: 0.1s; }
        .stat-card-2 { animation-delay: 0.2s; }
        .stat-card-3 { animation-delay: 0.3s; }
        .stat-card-4 { animation-delay: 0.4s; }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            transition: all 0.4s;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        /* Icon Backgrounds */
        .icon-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
            color: white;
        }

        .icon-primary {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .icon-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
            color: white;
        }

        .icon-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
            color: white;
        }

        /* Cards */
        .card-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.5);
            animation: fadeInUp 0.6s ease 0.5s both;
        }

        .card-custom .card-header {
            background: linear-gradient(135deg, #f093fb15 0%, #f5576c15 100%);
            border: none;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px 25px;
            font-weight: 700;
            color: #2d3748;
        }

        .card-custom .card-body {
            padding: 25px;
        }

        /* List Items */
        .list-group-item {
            border: none;
            border-radius: 12px !important;
            margin-bottom: 10px;
            padding: 18px 20px;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.7);
        }

        .list-group-item:hover {
            background: rgba(240, 147, 251, 0.05);
            transform: translateX(5px);
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper { padding: 20px; }
            .welcome-header { padding: 20px; }
        }
    </style>
    <style>
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .content-wrapper { padding: 30px; }
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .alert-warning-custom {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
