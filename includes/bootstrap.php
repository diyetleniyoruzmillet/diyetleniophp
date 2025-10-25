<?php
/**
 * Diyetlenio - Bootstrap Dosyası
 *
 * Tüm sayfalarda kullanılacak temel dosyaları yükler
 */

// Sabitleri yükle (constants.php'de dizinler tanımlanıyor)
require_once dirname(__DIR__) . '/config/constants.php';

// Hata raporlamayı yapılandır
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    if (defined('LOGS_DIR')) {
        ini_set('error_log', LOGS_DIR . '/error.log');
    }
}

// Storage ve logs dizinlerini oluştur
if (!is_dir(STORAGE_DIR . '/sessions')) {
    @mkdir(STORAGE_DIR . '/sessions', 0700, true);
}
if (!is_dir(LOGS_DIR)) {
    @mkdir(LOGS_DIR, 0755, true);
}

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
$conn = null;
$auth = null;

try {
    // Veritabanı bağlantısı
    $db = Database::getInstance();

    // PDO connection (used throughout the codebase)
    $conn = $db->getConnection();

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
        error_log('Database connection error: ' . $e->getMessage() . ' | Host: ' . ($_ENV['DB_HOST'] ?? 'not set'));
        die('Veritabanı bağlantı hatası oluştu. Lütfen daha sonra tekrar deneyin.<br>Hata kodu: DB_CONN_ERR');
    } else {
        http_response_code(500);
        error_log('Database connection error: ' . $e->getMessage());
        die('Sistem bakımdadır. Lütfen daha sonra tekrar deneyin.');
    }
}
