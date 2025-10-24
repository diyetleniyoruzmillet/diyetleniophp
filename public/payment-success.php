<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!$auth->check()) {
    redirect('/login.php');
}

$user = $auth->user();
$userType = $user->getUserType();
?>
<?php $pageTitle = 'Ödeme Başarılı'; include __DIR__ . '/../includes/partials/header.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: white;
            border-radius: 25px;
            padding: 60px 50px;
            text-align: center;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .success-icon i {
            font-size: 3rem;
            color: white;
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }
        .redirect-info {
            color: #718096;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Ödeme Başarılı!</h1>
        <p class="lead">Ödemeniz başarıyla alındı.</p>
        <p class="redirect-info">5 saniye içinde yönlendirileceksiniz...</p>
        <a href="/<?= $userType ?>/dashboard.php" class="btn btn-primary btn-lg">
            <i class="fas fa-home me-2"></i>Panele Dön
        </a>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = '/<?= $userType ?>/dashboard.php';
        }, 5000);
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
