<?php
/**
 * Diyetlenio - Bootstrap Dosyası
 *
 * Tüm sayfalarda kullanılacak temel dosyaları yükler
 */

// Hata raporlamayı etkinleştir (geliştirme aşamasında)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Temel dizinleri tanımla
define('ROOT_DIR', dirname(__DIR__));
define('CONFIG_DIR', ROOT_DIR . '/config');
define('CLASSES_DIR', ROOT_DIR . '/classes');
define('INCLUDES_DIR', ROOT_DIR . '/includes');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('ASSETS_DIR', ROOT_DIR . '/assets');
define('UPLOAD_DIR', ASSETS_DIR . '/uploads');
define('STORAGE_DIR', ROOT_DIR . '/storage');
define('LOGS_DIR', ROOT_DIR . '/logs');
define('VIEWS_DIR', ROOT_DIR . '/views');

// Storage ve logs dizinlerini oluştur
if (!is_dir(STORAGE_DIR . '/sessions')) {
    mkdir(STORAGE_DIR . '/sessions', 0700, true);
}
if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Sabitleri yükle
require_once CONFIG_DIR . '/constants.php';

// Yardımcı fonksiyonları yükle
require_once INCLUDES_DIR . '/functions.php';

// Session'ı başlat
require_once INCLUDES_DIR . '/session.php';

// Sınıfları yükle
spl_autoload_register(function ($className) {
    $file = CLASSES_DIR . '/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Global değişkenler
$db = null;
$auth = null;

try {
    // Veritabanı bağlantısı
    $db = Database::getInstance();

    // Auth instance
    $auth = new Auth();

} catch (Exception $e) {
    // Hata durumunda loglama
    error_log('Bootstrap Error: ' . $e->getMessage());
    error_log('Error Trace: ' . $e->getTraceAsString());

    // Health check endpoint'i için veritabanı hatası önemsiz
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/health.php') !== false) {
        // Health check için devam et
        return;
    }

    // Diğer sayfalar için hata göster
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        http_response_code(500);
        die('Sistem hatası: ' . $e->getMessage() . '<br><br>DB Host: ' . ($_ENV['DB_HOST'] ?? 'not set'));
    } else {
        http_response_code(500);
        die('Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.');
    }
}
