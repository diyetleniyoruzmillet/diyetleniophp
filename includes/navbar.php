<?php
/**
 * Standart Navbar - Tüm Frontend Sayfaları İçin
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Modern Navbar -->
<nav class="navbar navbar-expand-lg sticky-top" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <div class="container">
        <a class="navbar-brand" href="/" style="font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <i class="fas fa-heartbeat me-2"></i>Diyetlenio
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="/">Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dietitians.php' ? 'active' : '' ?>" href="/dietitians.php">Diyetisyenler</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'blog.php' ? 'active' : '' ?>" href="/blog.php">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'recipes.php' ? 'active' : '' ?>" href="/recipes.php">Tarifler</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-bold <?= $currentPage === 'emergency.php' ? 'active' : '' ?>" href="/emergency.php">
                        <i class="fas fa-ambulance me-1"></i>Acil Nöbetçi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="/about.php">Hakkımızda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="/contact.php">İletişim</a>
                </li>
                <?php if ($auth->check()): ?>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="/<?= $auth->user()->getUserType() ?>/dashboard.php" style="border-radius: 50px; padding: 0.6rem 1.5rem;">
                            <i class="fas fa-user me-1"></i>Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php" title="Çıkış Yap">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">Giriş Yap</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="/register-client.php" style="border-radius: 50px; padding: 0.6rem 1.5rem;">
                            Hemen Başla
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar-brand {
        transition: transform 0.3s;
        font-size: 1.3rem !important;
    }
    .navbar-brand:hover {
        transform: scale(1.05);
    }
    .nav-link {
        color: #0f172a !important;
        font-weight: 500;
        font-size: 0.9rem;
        margin: 0 0.25rem;
        padding: 0.5rem 0.6rem !important;
        transition: all 0.3s;
        position: relative;
        white-space: nowrap;
    }
    .nav-link:hover {
        color: #0ea5e9 !important;
    }
    .nav-link.active {
        color: #0ea5e9 !important;
        font-weight: 600;
    }
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 30px;
        height: 3px;
        background: #0ea5e9;
        border-radius: 2px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
        border: none;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        padding: 0.5rem 1.2rem !important;
        border-radius: 50px;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5);
        color: white;
    }
    /* Collapse navbar earlier for better spacing */
    @media (max-width: 1200px) {
        .nav-link {
            font-size: 0.85rem;
            margin: 0 0.15rem;
            padding: 0.5rem 0.5rem !important;
        }
        .navbar-brand {
            font-size: 1.2rem !important;
        }
    }
    @media (max-width: 992px) {
        .nav-link {
            margin: 0.5rem 0;
            padding: 0.5rem 0 !important;
            font-size: 0.95rem;
        }
        .btn-primary {
            margin-top: 0.5rem;
            width: 100%;
            text-align: center;
        }
    }
</style>
