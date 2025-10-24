<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!$auth->check()) {
    redirect('/login.php');
}

$user = $auth->user();
$userType = $user->getUserType();
?>
<?php $pageTitle = 'Ödeme İptal'; include __DIR__ . '/../includes/partials/header.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cancel-card {
            background: white;
            border-radius: 25px;
            padding: 60px 50px;
            text-align: center;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .cancel-icon {
            width: 100px;
            height: 100px;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .cancel-icon i {
            font-size: 3rem;
            color: white;
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="cancel-card">
        <div class="cancel-icon">
            <i class="fas fa-times"></i>
        </div>
        <h1>Ödeme İptal Edildi</h1>
        <p class="lead">Ödeme işlemi iptal edildi.</p>
        <p class="text-muted mb-4">Herhangi bir ücret tahsil edilmedi.</p>
        <a href="/<?= $userType ?>/dashboard.php" class="btn btn-primary btn-lg">
            <i class="fas fa-home me-2"></i>Panele Dön
        </a>
    </div>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
