<?php
/**
 * Diyetlenio - Ana Sayfa
 */

// Bootstrap dosyasını yükle
require_once __DIR__ . '/../includes/bootstrap.php';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diyetlenio - Diyetisyen ve Danışan Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-heartbeat"></i> Diyetlenio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($auth->check()): ?>
                        <li class="nav-item">
                            <span class="navbar-text text-white me-3">
                                Hoş geldin, <?= clean($auth->user()->getFullName()) ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">Çıkış</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="text-center mb-5">
                    <h1 class="display-4">
                        <i class="fas fa-heartbeat text-success"></i>
                        Diyetlenio
                    </h1>
                    <p class="lead text-muted">
                        Diyetisyenler ve Danışanları Bir Araya Getiren Platform
                    </p>
                </div>

                <?php if (hasFlash()): ?>
                    <?php if ($msg = getFlash('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= clean($msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($msg = getFlash('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= clean($msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-check-circle text-success"></i>
                            Sistem Başarıyla Kuruldu!
                        </h5>
                        <p class="card-text">
                            Diyetlenio platformunun temel PHP dosyaları başarıyla oluşturuldu.
                        </p>

                        <h6 class="mt-4">Oluşturulan Dosyalar:</h6>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item">
                                <i class="fas fa-folder text-warning"></i>
                                <strong>config/</strong> - Yapılandırma dosyaları
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-code text-primary"></i>
                                <strong>classes/</strong> - Database, User, Auth sınıfları
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-tools text-info"></i>
                                <strong>includes/</strong> - Yardımcı fonksiyonlar ve session
                            </li>
                        </ul>

                        <h6 class="mt-4">Sistem Bilgileri:</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>PHP Versiyonu:</strong></td>
                                <td><?= phpversion() ?></td>
                            </tr>
                            <tr>
                                <td><strong>Veritabanı:</strong></td>
                                <td>
                                    <?php
                                    try {
                                        $db->getConnection();
                                        echo '<span class="badge bg-success">Bağlantı Başarılı</span>';
                                    } catch (Exception $e) {
                                        echo '<span class="badge bg-danger">Bağlantı Hatası</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Session:</strong></td>
                                <td>
                                    <?= session_status() === PHP_SESSION_ACTIVE
                                        ? '<span class="badge bg-success">Aktif</span>'
                                        : '<span class="badge bg-danger">Pasif</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Uygulama:</strong></td>
                                <td><?= config('app.name') ?> v<?= APP_VERSION ?></td>
                            </tr>
                        </table>

                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-info-circle"></i> Sonraki Adımlar:</h6>
                            <ol class="mb-0">
                                <li>Veritabanını oluşturun: <code>mysql -u root -p &lt; database.sql</code></li>
                                <li>.env dosyasını yapılandırın</li>
                                <li>Giriş ve kayıt sayfalarını oluşturun</li>
                                <li>Admin panelini geliştirin</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-video fa-3x text-primary mb-3"></i>
                                <h5>Video Görüşme</h5>
                                <p class="small text-muted">WebRTC destekli online görüşme</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                <h5>Randevu Sistemi</h5>
                                <p class="small text-muted">Kolay randevu yönetimi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
                                <h5>Tarifler</h5>
                                <p class="small text-muted">Sağlıklı yemek tarifleri</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container text-center text-muted">
            <p class="mb-0">
                &copy; <?= date('Y') ?> Diyetlenio. Tüm hakları saklıdır.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
