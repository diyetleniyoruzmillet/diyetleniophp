<?php
/**
 * Dietitian Sidebar - Tüm dietitian sayfaları için ortak menü
 */

// Check if we're on the current page
function isDietitianPage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page;
}
?>
<div class="col-md-2 sidebar p-0">
    <div class="p-4">
        <h4 class="sidebar-brand">
            <i class="fas fa-heartbeat me-2"></i>Diyetlenio
        </h4>
        <p class="sidebar-subtitle mb-4">Diyetisyen Paneli</p>

        <nav class="nav flex-column">
            <a class="nav-link <?= isDietitianPage('dashboard.php') ? 'active' : '' ?>" href="/dietitian/dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Anasayfa
            </a>
            <a class="nav-link <?= isDietitianPage('profile.php') ? 'active' : '' ?>" href="/dietitian/profile.php">
                <i class="fas fa-user-edit me-2"></i>Profilim
            </a>
            <a class="nav-link <?= isDietitianPage('appointments.php') ? 'active' : '' ?>" href="/dietitian/appointments.php">
                <i class="fas fa-calendar-check me-2"></i>Randevular
            </a>
            <a class="nav-link <?= isDietitianPage('clients.php') ? 'active' : '' ?>" href="/dietitian/clients.php">
                <i class="fas fa-users me-2"></i>Danışanlarım
            </a>
            <a class="nav-link <?= isDietitianPage('diet-plans.php') ? 'active' : '' ?>" href="/dietitian/diet-plans.php">
                <i class="fas fa-clipboard-list me-2"></i>Diyet Planları
            </a>
            <a class="nav-link <?= isDietitianPage('availability.php') ? 'active' : '' ?>" href="/dietitian/availability.php">
                <i class="fas fa-clock me-2"></i>Müsaitlik
            </a>
            <a class="nav-link <?= isDietitianPage('messages.php') ? 'active' : '' ?>" href="/dietitian/messages.php">
                <i class="fas fa-comments me-2"></i>Mesajlar
            </a>
            <a class="nav-link <?= isDietitianPage('payments.php') ? 'active' : '' ?>" href="/dietitian/payments.php">
                <i class="fas fa-money-bill me-2"></i>Ödemeler
            </a>
            <a class="nav-link <?= isDietitianPage('analytics.php') ? 'active' : '' ?>" href="/dietitian/analytics.php">
                <i class="fas fa-chart-bar me-2"></i>Analitik
            </a>
            <a class="nav-link <?= isDietitianPage('reports.php') ? 'active' : '' ?>" href="/dietitian/reports.php">
                <i class="fas fa-file-alt me-2"></i>Raporlar
            </a>

            <hr class="text-white-50 my-3">

            <a class="nav-link" href="/">
                <i class="fas fa-home me-2"></i>Ana Sayfa
            </a>
            <a class="nav-link" href="/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Çıkış
            </a>
        </nav>
    </div>
</div>
