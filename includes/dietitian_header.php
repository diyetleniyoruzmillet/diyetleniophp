<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle) . ' - ' : '' ?>Diyetlenio</title>
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

        /* Modern Sidebar - Dietitian theme (pink/red gradient) */
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

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
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

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="sidebar-brand">
                        <i class="fas fa-heartbeat me-2"></i>Diyetlenio
                    </h4>
                    <p class="sidebar-subtitle mb-4">Diyetisyen Paneli</p>
                    <nav class="nav flex-column">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="/dietitian/dashboard.php">
                            <i class="fas fa-chart-line"></i>Dashboard
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="/dietitian/profile.php">
                            <i class="fas fa-user-edit"></i>Profilim
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'appointments.php' || basename($_SERVER['PHP_SELF']) == 'appointment-detail.php' ? 'active' : '' ?>" href="/dietitian/appointments.php">
                            <i class="fas fa-calendar-check"></i>Randevular
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'clients.php' || basename($_SERVER['PHP_SELF']) == 'client-detail.php' ? 'active' : '' ?>" href="/dietitian/clients.php">
                            <i class="fas fa-users"></i>Danışanlarım
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'diet-plans.php' ? 'active' : '' ?>" href="/dietitian/diet-plans.php">
                            <i class="fas fa-clipboard-list"></i>Diyet Planları
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'availability.php' ? 'active' : '' ?>" href="/dietitian/availability.php">
                            <i class="fas fa-clock"></i>Müsaitlik
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>" href="/dietitian/analytics.php">
                            <i class="fas fa-chart-bar"></i>İş Analizi
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>" href="/dietitian/messages.php">
                            <i class="fas fa-comments"></i>Mesajlar
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>" href="/dietitian/payments.php">
                            <i class="fas fa-money-bill"></i>Ödemeler
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="/dietitian/reports.php">
                            <i class="fas fa-file-alt"></i>Raporlar
                        </a>
                        <hr class="text-white-50 my-3">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="/logout.php">
                            <i class="fas fa-sign-out-alt"></i>Çıkış
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="content-wrapper">
