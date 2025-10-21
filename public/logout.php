<?php
/**
 * Diyetlenio - Logout (Çıkış)
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Kullanıcı çıkışı
$auth->logout();

// Başarı mesajı
setFlash('success', 'Başarıyla çıkış yaptınız.');

// Anasayfaya yönlendir
redirect('/');
