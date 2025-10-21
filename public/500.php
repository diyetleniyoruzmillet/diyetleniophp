<?php
/**
 * Diyetlenio - 500 Sunucu Hatası
 */

http_response_code(500);
$pageTitle = 'Sunucu Hatası';
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            color: #f5576c;
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
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">500</div>
        <h1 class="error-title">Sunucu Hatası</h1>
        <p class="error-message">
            Üzgünüz, bir şeyler ters gitti. Lütfen daha sonra tekrar deneyin.
        </p>
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
        </a>
    </div>
</body>
</html>
