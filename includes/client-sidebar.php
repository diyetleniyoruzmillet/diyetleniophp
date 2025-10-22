<?php
/**
 * Client Sidebar - Tüm client sayfaları için ortak menü
 */

// Check if we're on the current page
function isClientPage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page;
}
?>
<div class="col-md-2 sidebar p-0">
    <div class="p-4">
        <h4 class="sidebar-brand">
            <i class="fas fa-heartbeat me-2"></i>Diyetlenio
        </h4>
        <p class="sidebar-subtitle mb-4">Danışan Paneli</p>

        <nav class="nav flex-column">
            <a class="nav-link <?= isClientPage('dashboard.php') ? 'active' : '' ?>" href="/client/dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Anasayfa
            </a>
            <a class="nav-link <?= isClientPage('dietitians.php') ? 'active' : '' ?>" href="/client/dietitians.php">
                <i class="fas fa-user-md me-2"></i>Diyetisyenler
            </a>
            <a class="nav-link <?= isClientPage('appointments.php') ? 'active' : '' ?>" href="/client/appointments.php">
                <i class="fas fa-calendar-check me-2"></i>Randevularım
            </a>
            <a class="nav-link <?= isClientPage('diet-plans.php') ? 'active' : '' ?>" href="/client/diet-plans.php">
                <i class="fas fa-clipboard-list me-2"></i>Diyet Planlarım
            </a>
            <a class="nav-link <?= isClientPage('weight-tracking.php') ? 'active' : '' ?>" href="/client/weight-tracking.php">
                <i class="fas fa-weight me-2"></i>Kilo Takibi
            </a>
            <a class="nav-link <?= isClientPage('messages.php') ? 'active' : '' ?>" href="/client/messages.php">
                <i class="fas fa-envelope me-2"></i>Mesajlar
            </a>
            <a class="nav-link <?= isClientPage('payment-upload.php') ? 'active' : '' ?>" href="/client/payment-upload.php">
                <i class="fas fa-file-invoice-dollar me-2"></i>Ödemelerim
            </a>
            <a class="nav-link <?= isClientPage('profile.php') ? 'active' : '' ?>" href="/client/profile.php">
                <i class="fas fa-user me-2"></i>Profilim
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
