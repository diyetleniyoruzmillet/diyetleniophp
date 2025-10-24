<?php
/**
 * Diyetlenio - Genel Yapılandırma Dosyası
 *
 * Site genelindeki yapılandırma ayarlarını içerir.
 */

// Hata raporlama ayarları
$environment = $_ENV['APP_ENV'] ?? 'production';

if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// APP_KEY güvenliği (production'da zorunlu)
$appKey = $_ENV['APP_KEY'] ?? null;
if ($environment !== 'development' && (empty($appKey) || $appKey === 'base64:YourRandomGeneratedKeyHere')) {
    error_log('APP_KEY eksik veya geçersiz. Production ortamında APP_KEY zorunludur.');
    http_response_code(500);
    die('Yapılandırma hatası: APP_KEY .env içinde tanımlanmalı.');
}

// Site yapılandırması
return [
    // Uygulama ayarları
    'app' => [
        'name'          => $_ENV['APP_NAME'] ?? 'Diyetlenio',
        'env'           => $environment,
        'debug'         => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url'           => $_ENV['APP_URL'] ?? 'http://localhost:8000',
        'timezone'      => 'Europe/Istanbul',
        'locale'        => 'tr_TR',
        'charset'       => 'UTF-8',
    ],

    // Güvenlik ayarları
    'security' => [
        'encryption_key' => $appKey ?: ('base64:' . base64_encode(random_bytes(32))),
        'hash_algo'      => 'sha256',
        'session_name'   => 'diyetlenio_session',
        'csrf_token_name' => 'csrf_token',
        'password_cost'  => 12, // bcrypt cost
    ],

    // Oturum ayarları
    'session' => [
        'lifetime'       => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 saat
        'expire_on_close' => false,
        'secure'         => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'http_only'      => true,
        'same_site'      => 'Lax',
        'cookie_path'    => '/',
    ],

    // Dosya yükleme ayarları
    'upload' => [
        'max_size'       => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760), // 10MB
        'allowed_types'  => [
            'images'     => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'documents'  => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'videos'     => ['mp4', 'webm', 'ogg'],
        ],
        'paths' => [
            'profiles'   => __DIR__ . '/../assets/uploads/profiles/',
            'articles'   => __DIR__ . '/../assets/uploads/articles/',
            'recipes'    => __DIR__ . '/../assets/uploads/recipes/',
            'documents'  => __DIR__ . '/../assets/uploads/documents/',
            'temp'       => __DIR__ . '/../assets/uploads/temp/',
        ],
    ],

    // E-posta ayarları
    'mail' => [
        'driver'     => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host'       => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port'       => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username'   => $_ENV['MAIL_USERNAME'] ?? '',
        'password'   => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@diyetlenio.com',
            'name'    => $_ENV['MAIL_FROM_NAME'] ?? 'Diyetlenio',
        ],
    ],

    // Sayfalama ayarları
    'pagination' => [
        'per_page'       => (int) ($_ENV['PAGINATION_PER_PAGE'] ?? 20),
        'articles'       => 12,
        'recipes'        => 16,
        'dietitians'     => 12,
        'appointments'   => 20,
    ],

    // WebRTC ayarları
    'webrtc' => [
        'signaling_server_url' => $_ENV['SIGNALING_SERVER_URL'] ?? 'http://localhost:3000',
        'stun_servers' => [
            'stun:stun.l.google.com:19302',
            'stun:stun1.l.google.com:19302',
            'stun:stun2.l.google.com:19302',
        ],
        'turn_servers' => [
            // TURN sunucu yapılandırması eklenecek
            // Örnek:
            // [
            //     'urls' => 'turn:your-turn-server.com:3478',
            //     'username' => 'your-username',
            //     'credential' => 'your-password'
            // ]
        ],
        'max_duration' => (int) ($_ENV['VIDEO_MAX_DURATION'] ?? 60), // Dakika
    ],

    // Cache ayarları
    'cache' => [
        'enabled'  => filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'driver'   => $_ENV['CACHE_DRIVER'] ?? 'file',
        'lifetime' => (int) ($_ENV['CACHE_LIFETIME'] ?? 3600), // 1 saat
        'path'     => __DIR__ . '/../storage/cache/',
    ],

    // Log ayarları
    'logging' => [
        'enabled'   => true,
        'path'      => __DIR__ . '/../logs/',
        'max_files' => 30, // Son 30 günün loglarını tut
    ],

    // Randevu ayarları
    'appointments' => [
        'default_duration'   => (int) ($_ENV['APPOINTMENT_DURATION'] ?? 45), // Dakika
        'cancellation_hours' => (int) ($_ENV['CANCELLATION_HOURS'] ?? 2),
        'reminder_hours'     => (int) ($_ENV['REMINDER_HOURS'] ?? 1),
        'working_hours' => [
            'start' => '09:00',
            'end'   => '18:00',
        ],
    ],

    // API ayarları
    'api' => [
        'rate_limit'     => (int) ($_ENV['API_RATE_LIMIT'] ?? 60), // Dakikada istek sayısı
        'timeout'        => (int) ($_ENV['API_TIMEOUT'] ?? 30), // Saniye
    ],
];
