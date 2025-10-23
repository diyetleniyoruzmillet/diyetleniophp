<?php
/**
 * Diyetlenio - Danışan Dashboard
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

// Sadece client erişebilir
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Bu sayfaya erişim yetkiniz yok.');
    redirect('/login.php');
}

try {
    $conn = $db->getConnection();
    $userId = $auth->user()->getId();

    // İstatistikleri çek (appointments tablosu olmayabilir, try-catch)
    $stats = [
    'completed_appointments' => 0,
    'upcoming_appointments' => 0,
    'dietitians_worked_with' => 0,
    'active_plans' => 0
];

try {
    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM appointments WHERE client_id = ? AND status = 'completed') as completed_appointments,
            (SELECT COUNT(*) FROM appointments WHERE client_id = ? AND status = 'scheduled' AND appointment_date >= NOW()) as upcoming_appointments,
            (SELECT COUNT(DISTINCT dietitian_id) FROM appointments WHERE client_id = ?) as dietitians_worked_with
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $result = $stmt->fetch();
    if ($result) {
        $stats = array_merge($stats, $result);
    }
} catch (PDOException $e) {
    error_log('Stats query error: ' . $e->getMessage());
    // Tablo yoksa default değerleri kullan
}

// Aktif diyetisyeni çek (son randevusu olan)
$currentDietitian = null;
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, dp.title, dp.specialization, dp.rating_avg
        FROM appointments a
        INNER JOIN users u ON a.dietitian_id = u.id
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE a.client_id = ? AND a.status IN ('scheduled', 'completed')
        ORDER BY a.appointment_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $currentDietitian = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Current dietitian query error: ' . $e->getMessage());
}

// Yaklaşan randevular
$upcomingAppointments = [];
try {
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as dietitian_name, dp.title
        FROM appointments a
        INNER JOIN users u ON a.dietitian_id = u.id
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE a.client_id = ? AND a.status = 'scheduled' AND appointment_date >= NOW()
        ORDER BY a.appointment_date ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $upcomingAppointments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Upcoming appointments query error: ' . $e->getMessage());
}

// Aktif diyet planı (diet_plans tablosu henüz yok)
$activePlan = null;
/*
// TODO: diet_plans tablosu oluşturulduğunda aktifleştir
$stmt = $conn->prepare("
    SELECT dp.*, u.full_name as dietitian_name
    FROM diet_plans dp
    INNER JOIN users u ON dp.dietitian_id = u.id
    WHERE dp.client_id = ? AND dp.is_active = 1
    ORDER BY dp.start_date DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$activePlan = $stmt->fetch();
*/

// Son kilo takibi
$weightHistory = [];
try {
    $stmt = $conn->prepare("
        SELECT * FROM weight_tracking
        WHERE client_id = ?
        ORDER BY measurement_date DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $weightHistory = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Weight tracking query error: ' . $e->getMessage());
    // Tablo yoksa boş array
}

// Bugünün öğünleri (aktif plandan) - diet_plan_meals tablosu henüz yok
$todayMeals = [];
// TODO: diet_plan_meals tablosu oluşturulduğunda aktifleştir

$pageTitle = 'Danışan Paneli';

} catch (Throwable $e) {
    error_log('Client dashboard error: ' . $e->getMessage());
    error_log('File: ' . $e->getFile() . ':' . $e->getLine());
    error_log('Trace: ' . $e->getTraceAsString());

    // Debug modunda detaylı hatayı göster
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die('<h1>Dashboard Error</h1><pre>' . $e->getMessage() . "\n\nFile: " . $e->getFile() . ':' . $e->getLine() . "\n\nTrace:\n" . $e->getTraceAsString() . '</pre>');
    } else {
        die('Dashboard yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    }
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', sans-serif;
        }

        /* Modern Sidebar - Different gradient for client */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #56ab2f 0%, #a8e063 100%);
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
            position: relative;
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
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
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
            background: linear-gradient(90deg, #56ab2f 0%, #a8e063 100%);
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
            background: radial-gradient(circle, rgba(86, 171, 47, 0.08) 0%, transparent 70%);
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

        /* Icon Backgrounds - Client theme */
        .icon-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
            color: white;
        }

        .icon-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .icon-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
            color: white;
        }

        .icon-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.3);
            color: white;
        }

        /* Modern Card */
        .card-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.5);
            animation: fadeInUp 0.6s ease 0.5s both;
        }

        .card-custom .card-header {
            background: linear-gradient(135deg, #56ab2f15 0%, #a8e06315 100%);
            border: none;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px 25px;
            font-weight: 700;
            color: #2d3748;
        }

        .card-custom .card-body {
            padding: 25px;
        }

        /* Modern Action Card */
        .action-card {
            border: 2px dashed rgba(86, 171, 47, 0.3);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.4s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .action-card:hover {
            border-color: #56ab2f;
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(86, 171, 47, 0.2);
        }

        .action-card:hover::before {
            opacity: 0.05;
        }

        .action-card i {
            font-size: 3rem;
            color: #56ab2f;
            transition: all 0.4s;
        }

        .action-card:hover i {
            transform: scale(1.2) rotate(10deg);
        }

        /* Meal Item */
        .meal-item {
            padding: 20px;
            border-left: 4px solid #56ab2f;
            background: rgba(86, 171, 47, 0.05);
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .meal-item:hover {
            background: rgba(86, 171, 47, 0.1);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.1);
        }

        /* List Group Modern */
        .list-group-item {
            border: none;
            border-radius: 12px !important;
            margin-bottom: 10px;
            padding: 18px 20px;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.7);
        }

        .list-group-item:hover {
            background: rgba(86, 171, 47, 0.05);
            transform: translateX(5px);
        }

        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(86, 171, 47, 0.5);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
            }
            .welcome-header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
