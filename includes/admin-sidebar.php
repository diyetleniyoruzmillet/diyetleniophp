<?php
/**
 * Admin Sidebar - Tüm admin sayfaları için ortak menü
 */

// Check if we're on the current page
function isAdminPage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page;
}

// Get pending dietitian count
$pendingDietitians = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'dietitian' AND is_active = 0");
    $result = $stmt->fetch();
    $pendingDietitians = $result['count'] ?? 0;
} catch (Exception $e) {
    // Ignore errors
}
?>
<div class="col-md-2 sidebar p-0">
    <div class="p-4">
        <h4 class="sidebar-brand">
            <i class="fas fa-heartbeat me-2"></i>Diyetlenio
        </h4>
        <p class="sidebar-subtitle mb-4">Admin Panel</p>

        <nav class="nav flex-column">
            <a class="nav-link <?= isAdminPage('dashboard.php') ? 'active' : '' ?>" href="/admin/dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Anasayfa
            </a>
            <a class="nav-link <?= isAdminPage('users.php') ? 'active' : '' ?>" href="/admin/users.php">
                <i class="fas fa-users me-2"></i>Kullanıcılar
            </a>
            <a class="nav-link <?= isAdminPage('dietitians.php') ? 'active' : '' ?>" href="/admin/dietitians.php">
                <i class="fas fa-user-md me-2"></i>Diyetisyenler
                <?php if ($pendingDietitians > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $pendingDietitians ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?= isAdminPage('appointments.php') ? 'active' : '' ?>" href="/admin/appointments.php">
                <i class="fas fa-calendar-check me-2"></i>Randevular
            </a>
            <a class="nav-link <?= isAdminPage('articles.php') ? 'active' : '' ?>" href="/admin/articles.php">
                <i class="fas fa-newspaper me-2"></i>Blog Yazıları
            </a>
            <a class="nav-link <?= isAdminPage('recipes.php') ? 'active' : '' ?>" href="/admin/recipes.php">
                <i class="fas fa-utensils me-2"></i>Tarifler
            </a>
            <a class="nav-link <?= isAdminPage('payments.php') ? 'active' : '' ?>" href="/admin/payments.php">
                <i class="fas fa-money-bill me-2"></i>Ödemeler
            </a>
            <a class="nav-link <?= isAdminPage('reviews.php') ? 'active' : '' ?>" href="/admin/reviews.php">
                <i class="fas fa-star me-2"></i>Yorumlar
            </a>
            <a class="nav-link <?= isAdminPage('analytics.php') ? 'active' : '' ?>" href="/admin/analytics.php">
                <i class="fas fa-chart-bar me-2"></i>Analitik
            </a>
            <a class="nav-link <?= isAdminPage('settings.php') ? 'active' : '' ?>" href="/admin/settings.php">
                <i class="fas fa-cog me-2"></i>Ayarlar
            </a>

            <hr class="text-white-50 my-3">

            <a class="nav-link" href="/">
                <i class="fas fa-home me-2"></i>Ana Sayfa
            </a>
            <a class="nav-link" href="/admin/profile.php">
                <i class="fas fa-user me-2"></i>Profil
            </a>
            <a class="nav-link" href="/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Çıkış
            </a>
        </nav>
    </div>
</div>
