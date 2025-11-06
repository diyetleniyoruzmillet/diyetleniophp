<?php
/**
 * Forgot Password - Şifre Sıfırlama
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if ($auth->check()) {
    header('Location: /');
    exit;
}

$pageTitle = 'Şifre Sıfırlama';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            max-width: 450px;
            width: 100%;
            border-radius: 24px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .card-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2.5rem;
            text-align: center;
            border-radius: 24px 24px 0 0 !important;
        }

        .card-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-key"></i>
            <h2 class="mb-0">Şifrenizi mi Unuttunuz?</h2>
            <p class="mb-0 mt-2">Email adresinize sıfırlama linki gönderelim</p>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Bu özellik yakında aktif olacak. Şimdilik destek@diyetlenio.com adresine email atarak şifrenizi sıfırlayabilirsiniz.
            </div>

            <form>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope me-2"></i>Email Adresi</label>
                    <input type="email" class="form-control" placeholder="ornek@email.com" required>
                </div>

                <button type="submit" class="btn btn-primary w-100" disabled>
                    <i class="fas fa-paper-plane me-2"></i>
                    Sıfırlama Linki Gönder
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="/login.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>
                    Giriş sayfasına dön
                </a>
            </div>
        </div>
    </div>
</body>
</html>
