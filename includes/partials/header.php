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
// Varsayılan olarak navbar gizli
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
    <link rel="stylesheet" href="/css/modern-design-system.css?v=<?= urlencode(defined('APP_BUILD')?APP_BUILD:time()) ?>">
    <link rel="stylesheet" href="/css/design-system.css?v=<?= urlencode(defined('APP_BUILD')?APP_BUILD:time()) ?>">
    <link rel="stylesheet" href="/css/app.css?v=<?= urlencode(defined('APP_BUILD')?APP_BUILD:time()) ?>">
    <?= $extraHead ?>
</head>
<body class="<?= clean($bodyClass) ?>">
<?php if ($showNavbar): ?>
    <nav class="navbar navbar-expand-lg navbar-main sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/"><i class="fas fa-heartbeat me-2 text-danger"></i>Diyetlenio</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/') ? ' active' : '' ?>" href="/">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/dietitians.php') ? ' active' : '' ?>" href="/dietitians.php">Diyetisyenler</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/about.php') ? ' active' : '' ?>" href="/about.php">Hakkımızda</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/blog.php') ? ' active' : '' ?>" href="/blog.php">Blog</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/recipes.php') ? ' active' : '' ?>" href="/recipes.php">Tarifler</a></li>
                    <li class="nav-item"><a class="nav-link<?= $_isActive('/contact.php') ? ' active' : '' ?>" href="/contact.php">İletişim</a></li>
                    <?php if (isset($auth) && $auth && $auth->check()): ?>
                        <?php $ut = $auth->user()->getUserType();
                              $panelLink = $ut === 'admin' ? '/admin/dashboard.php' : ($ut === 'dietitian' ? '/dietitian/dashboard.php' : '/client/dashboard.php');
                              $fullName = $auth->user()->getFullName();
                              $pp = $auth->user()->get('profile_photo') ?? '';
                              $avatar = $pp ? ('/assets/uploads/' . ltrim($pp,'/')) : '/images/default-avatar.png'; ?>
                        <li class="nav-item"><a class="nav-link<?= $_isActive($panelLink) ? ' active' : '' ?>" href="<?= $panelLink ?>">Panelim</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= clean($avatar) ?>" alt="<?= clean($fullName) ?>" class="rounded-circle me-2" style="width:28px;height:28px;object-fit:cover;">
                                <span class="d-none d-md-inline"><?= clean($fullName) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= $panelLink ?>"><i class="fas fa-gauge me-2"></i>Panel</a></li>
                                <?php if ($ut === 'dietitian'): ?>
                                    <li><a class="dropdown-item" href="/dietitian/profile.php"><i class="fas fa-user me-2"></i>Profilim</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-right-from-bracket me-2"></i>Çıkış</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-success" href="/register-client.php">Kayıt Ol</a></li>
                        <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-outline-primary<?= $_isActive('/login.php') ? ' active' : '' ?>" href="/login.php">Giriş Yap</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>
