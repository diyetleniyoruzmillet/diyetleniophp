<?php
/**
 * Logout - Çıkış Yap
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Auth ile çıkış yap
if ($auth->check()) {
    $auth->logout();
}

// Ana sayfaya yönlendir
header('Location: /');
exit;
