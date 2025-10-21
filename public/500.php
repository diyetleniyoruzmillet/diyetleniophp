<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Sunucu Hatası - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
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
            animation: shake 0.5s ease-in-out infinite alternate;
        }
        @keyframes shake {
            0% { transform: translateX(-5px); }
            100% { transform: translateX(5px); }
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
            color: #dc2626;
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
            color: #dc2626;
        }
        .error-details {
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.9rem;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">500</div>
        <h1>Sunucu Hatası</h1>
        <p>Üzgünüz, sunucuda bir hata oluştu. Teknik ekibimiz bilgilendirildi ve sorunu en kısa sürede çözecek.</p>
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
        </a>
        <div class="error-details">
            <p><strong>Ne yapabilirsiniz?</strong></p>
            <ul>
                <li>Sayfayı yenilemeyi deneyin</li>
                <li>Birkaç dakika sonra tekrar deneyin</li>
                <li>Sorun devam ederse <a href="/contact.php" style="color: white; text-decoration: underline;">bizimle iletişime geçin</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
