<?php
/**
 * Diyetlenio - Session Yönetimi
 *
 * Güvenli session başlatma ve yapılandırma
 */

// Session zaten başlatılmışsa tekrar başlatma
if (session_status() === PHP_SESSION_NONE) {

    // Session yapılandırması
    $sessionConfig = config('session', []);

    // Session cookie parametreleri
    $cookieParams = [
        'lifetime' => $sessionConfig['lifetime'] ?? 7200, // 2 saat
        'path'     => $sessionConfig['cookie_path'] ?? '/',
        'domain'   => $_SERVER['HTTP_HOST'] ?? '',
        'secure'   => $sessionConfig['secure'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => $sessionConfig['http_only'] ?? true,
        'samesite' => $sessionConfig['same_site'] ?? 'Lax'
    ];

    // Session cookie parametrelerini ayarla
    session_set_cookie_params($cookieParams);

    // Session adı
    $sessionName = $sessionConfig['session_name'] ?? 'diyetlenio_session';
    session_name($sessionName);

    // Session save path (opsiyonel)
    $sessionPath = STORAGE_DIR . '/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0700, true);
    }
    session_save_path($sessionPath);

    // Session'ı başlat
    if (!session_start()) {
        error_log('Session start failed');
        die('Session başlatılamadı. Lütfen daha sonra tekrar deneyin.');
    }

    // Session hijacking koruması
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // User agent değişmiş mi kontrol et (basit hijacking koruması)
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        // Session'ı yok et
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['initiated'] = true;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // Session timeout kontrolü
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];

        // Belirtilen süre kadar aktif değilse session'ı sonlandır
        if ($inactiveTime > $sessionConfig['lifetime']) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['initiated'] = true;
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

            // Timeout mesajı
            setFlash('warning', 'Oturumunuz zaman aşımına uğradı. Lütfen tekrar giriş yapın.');
        }
    }

    // Son aktivite zamanını güncelle
    $_SESSION['last_activity'] = time();

    // Session ID'yi periyodik olarak yenile (hijacking koruması)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 dakikada bir
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Session'a veri ekler
 *
 * @param string $key
 * @param mixed $value
 * @return void
 */
function sessionSet(string $key, $value): void
{
    $_SESSION[$key] = $value;
}

/**
 * Session'dan veri alır
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function sessionGet(string $key, $default = null)
{
    return $_SESSION[$key] ?? $default;
}

/**
 * Session'da veri var mı kontrol eder
 *
 * @param string $key
 * @return bool
 */
function sessionHas(string $key): bool
{
    return isset($_SESSION[$key]);
}

/**
 * Session'dan veri siler
 *
 * @param string $key
 * @return void
 */
function sessionRemove(string $key): void
{
    unset($_SESSION[$key]);
}

/**
 * Tüm session verilerini temizler
 *
 * @return void
 */
function sessionClear(): void
{
    session_unset();
}

/**
 * Session'ı yok eder
 *
 * @return void
 */
function sessionDestroy(): void
{
    // Session cookie'sini sil
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Session'ı temizle ve yok et
    session_unset();
    session_destroy();
}

/**
 * Flash data ekler (bir sonraki request için)
 *
 * @param string $key
 * @param mixed $value
 * @return void
 */
function flashSet(string $key, $value): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][$key] = $value;
}

/**
 * Flash data alır ve siler
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function flashGet(string $key, $default = null)
{
    if (isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    return $default;
}

/**
 * Flash data var mı kontrol eder
 *
 * @param string $key
 * @return bool
 */
function flashHas(string $key): bool
{
    return isset($_SESSION['flash'][$key]);
}

/**
 * Old input değeri saklar (form hatalarında kullanmak için)
 *
 * @param array $data
 * @return void
 */
function setOldInput(array $data): void
{
    $_SESSION['old_input'] = $data;
}

/**
 * Old input değeri alır
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function old(string $key, $default = '')
{
    if (isset($_SESSION['old_input'][$key])) {
        $value = $_SESSION['old_input'][$key];
        // İlk kullanımdan sonra sil
        return $value;
    }

    return $default;
}

/**
 * Old input verilerini temizler
 *
 * @return void
 */
function clearOldInput(): void
{
    unset($_SESSION['old_input']);
}

/**
 * CSRF token oluşturur ve session'a kaydeder
 *
 * @return string
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * CSRF token'ı doğrular
 *
 * @param string $token
 * @return bool
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Session güvenlik kontrolü yapar
 *
 * @return bool
 */
function validateSession(): bool
{
    // User agent kontrolü
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        return false;
    }

    // IP adresi kontrolü (opsiyonel - bazı durumlarda IP değişebilir)
    // if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    //     return false;
    // }

    return true;
}

/**
 * Session verilerini debug için gösterir
 *
 * @return void
 */
function debugSession(): void
{
    if (DEBUG_MODE) {
        echo '<pre>';
        echo '<h3>Session Data:</h3>';
        print_r($_SESSION);
        echo '</pre>';
    }
}

/**
 * Session lifetime'ı günceller (remember me için)
 *
 * @param int $seconds
 * @return void
 */
function extendSession(int $seconds): void
{
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        session_id(),
        time() + $seconds,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

/**
 * Session temizleme (garbage collection)
 * Cron job veya periyodik olarak çağrılmalı
 *
 * @param int $maxLifetime Saniye cinsinden maksimum yaşam süresi
 * @return int Silinen session sayısı
 */
function cleanExpiredSessions(int $maxLifetime = 7200): int
{
    $sessionPath = session_save_path();
    $count = 0;

    if (is_dir($sessionPath)) {
        $files = glob($sessionPath . '/sess_*');

        foreach ($files as $file) {
            if (filemtime($file) + $maxLifetime < time() && file_exists($file)) {
                unlink($file);
                $count++;
            }
        }
    }

    return $count;
}
