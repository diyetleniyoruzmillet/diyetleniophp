<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sayfa Bulunamadı - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            color: white;
            max-width: 600px;
        }
        .error-code {
            font-size: 10rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 30px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .error-icon {
            font-size: 5rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        .btn-home {
            background: white;
            color: #0ea5e9;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.3);
            color: #0ea5e9;
        }
        .links {
            margin-top: 40px;
        }
        .links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        .links a:hover {
            opacity: 1;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        <div class="error-code">404</div>
        <h1>Sayfa Bulunamadı</h1>
        <p>Aradığınız sayfa mevcut değil veya taşınmış olabilir.</p>
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
        </a>
        <div class="links">
            <a href="/blog.php">Blog</a>
            <a href="/recipes.php">Tarifler</a>
            <a href="/contact.php">İletişim</a>
            <a href="/login.php">Giriş Yap</a>
        </div>
    </div>
</body>
</html>
