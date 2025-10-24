<?php
/**
 * Ortak Header Partial
 * Değişkenler (opsiyonel):
 *  - $pageTitle   : Sayfa başlığı
 *  - $bodyClass   : Body sınıfları
 *  - $showNavbar  : Navbar gösterilsin mi (default: true)
 */

$title = isset($pageTitle) && $pageTitle ? $pageTitle . ' - Diyetlenio' : 'Diyetlenio';
$metaDescription = $metaDescription ?? '';
$extraHead = $extraHead ?? '';
$bodyClass = $bodyClass ?? '';
$showNavbar = array_key_exists('showNavbar', get_defined_vars()) ? (bool)$showNavbar : true;

// Aktif menü için path belirleme
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$_isActive = function(string $path) use ($currentPath): bool {
    return rtrim($currentPath, '/') === rtrim($path, '/');
};
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($title) ?></title>
    <?php if ($metaDescription): ?>
        <meta name="description" content="<?= clean($metaDescription) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/modern-design-system.css">
    <link rel="stylesheet" href="/css/design-system.css">
    <link rel="stylesheet" href="/css/app.css">
    <?= $extraHead ?>
</head>
<body class="<?= clean($bodyClass) ?>">
<?php if ($showNavbar): ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom" style="box-shadow: var(--shadow-sm)">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/"><i class="fas fa-heartbeat me-2 text-danger"></i>Diyetlenio</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/') ? ' text-primary' : '' ?>" href="/">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/blog.php') ? ' text-primary' : '' ?>" href="/blog.php">Blog</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/faq.php') ? ' text-primary' : '' ?>" href="/faq.php">SSS</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-sm btn-outline-primary<?= $_isActive('/login.php') ? ' active' : '' ?>" href="/login.php">Giriş Yap</a></li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>
