<?php
/**
 * Diyetlenio - 403 Yetkisiz Erişim
 */

http_response_code(403);
$pageTitle = 'Yetkisiz Erişim';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
            padding: 40px;
        }
        .error-code {
            font-size: 150px;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 30px 0 20px;
        }
        .error-message {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        .btn-home {
            background: white;
            color: #fa709a;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin: 0 10px;
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            color: #fa709a;
        }
        .icon-wrapper {
            font-size: 100px;
            margin-bottom: 30px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon-wrapper">
            <i class="fas fa-lock"></i>
        </div>
        <div class="error-code">403</div>
        <h1 class="error-title">Yetkisiz Erişim</h1>
        <p class="error-message">
            Bu sayfaya erişim yetkiniz bulunmamaktadır.
        </p>
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>Ana Sayfa
        </a>
        <a href="/login.php" class="btn-home">
            <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
        </a>
    </div>
</body>
</html>
