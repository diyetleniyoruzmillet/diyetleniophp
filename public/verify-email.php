<?php
/**
 * Diyetlenio - E-posta Doğrulama
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$success = false;
$error = null;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    try {
        // Token ile kullanıcıyı bul ve doğrula
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT id, full_name, email 
            FROM users 
            WHERE email_verification_token = ? 
            AND is_email_verified = 0
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // E-postayı doğrula
            $stmt = $conn->prepare("
                UPDATE users 
                SET is_email_verified = 1, 
                    email_verification_token = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([$user['id']])) {
                $success = true;
                
                // Aktiviteyi logla
                $logStmt = $conn->prepare("
                    INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                    VALUES (?, 'email_verification', 'E-posta adresi doğrulandı', ?, NOW())
                ");
                $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
            } else {
                $error = 'Doğrulama sırasında bir hata oluştu.';
            }
        } else {
            $error = 'Geçersiz veya süresi dolmuş doğrulama linki.';
        }
    } catch (Exception $e) {
        error_log('Email verification error: ' . $e->getMessage());
        $error = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
} else {
    $error = 'Doğrulama kodu bulunamadı.';
}

$pageTitle = 'E-posta Doğrulama';
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
        body {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .verification-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .icon-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin-bottom: 30px;
        }
        .icon-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .icon-error {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }
        .message {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .btn-action {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
        }
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(17, 153, 142, 0.5);
            color: white;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <?php if ($success): ?>
                <div class="icon-wrapper icon-success">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="title">E-posta Doğrulandı!</h1>
                <p class="message">
                    E-posta adresiniz başarıyla doğrulandı. Artık hesabınızı kullanabilirsiniz.
                </p>
                <a href="/login.php" class="btn-action">
                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                </a>
            <?php else: ?>
                <div class="icon-wrapper icon-error">
                    <i class="fas fa-times"></i>
                </div>
                <h1 class="title">Doğrulama Başarısız</h1>
                <p class="message">
                    <?= clean($error) ?>
                </p>
                <a href="/" class="btn-action">
                    <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
