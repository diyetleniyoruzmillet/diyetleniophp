<?php
/**
 * Diyetlenio - Genel Fonksiyonlar
 *
 * Uygulama genelinde kullanılan yardımcı fonksiyonlar
 */

/**
 * XSS koruması için HTML karakterlerini encode eder
 *
 * @param string|null $string
 * @return string
 */
function clean($string): string
{
    if ($string === null) {
        return '';
    }

    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * HTML içeriği güvenli şekilde görüntüler (editör içeriği için)
 *
 * @param string|null $html
 * @return string
 */
function cleanHtml($html): string
{
    if ($html === null) {
        return '';
    }

    // Basit bir implementasyon - gerçek uygulamada HTML Purifier kullanılmalı
    return strip_tags($html, '<p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6>');
}

/**
 * URL oluşturur
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Asset URL oluşturur
 *
 * @param string $path
 * @return string
 */
function asset(string $path): string
{
    return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Upload URL oluşturur
 *
 * @param string $path
 * @return string
 */
function upload(string $path): string
{
    return rtrim(UPLOAD_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Yönlendirme yapar
 *
 * @param string $url
 * @param int $statusCode
 * @return void
 */
function redirect(string $url, int $statusCode = 302): void
{
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Geri yönlendirir
 *
 * @return void
 */
function redirectBack(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? url();
    redirect($referer);
}

/**
 * Flash mesaj ekler
 *
 * @param string $type (success, error, warning, info)
 * @param string $message
 * @return void
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Flash mesajı döndürür ve siler
 *
 * @param string $type
 * @return string|null
 */
function getFlash(string $type): ?string
{
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    return null;
}

/**
 * Tüm flash mesajları kontrol eder
 *
 * @return bool
 */
function hasFlash(): bool
{
    return !empty($_SESSION['flash']);
}

/**
 * Tarih formatlar (Türkçe)
 *
 * @param string|null $date
 * @param string $format
 * @return string
 */
function formatDate(?string $date, string $format = DATE_FORMAT): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    $formattedDate = date($format, $timestamp);

    // Türkçe ay isimleri
    $months = [
        'January' => 'Ocak',
        'February' => 'Şubat',
        'March' => 'Mart',
        'April' => 'Nisan',
        'May' => 'Mayıs',
        'June' => 'Haziran',
        'July' => 'Temmuz',
        'August' => 'Ağustos',
        'September' => 'Eylül',
        'October' => 'Ekim',
        'November' => 'Kasım',
        'December' => 'Aralık'
    ];

    // Türkçe gün isimleri
    $days = [
        'Monday' => 'Pazartesi',
        'Tuesday' => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe',
        'Friday' => 'Cuma',
        'Saturday' => 'Cumartesi',
        'Sunday' => 'Pazar'
    ];

    $formattedDate = str_replace(array_keys($months), array_values($months), $formattedDate);
    $formattedDate = str_replace(array_keys($days), array_values($days), $formattedDate);

    return $formattedDate;
}

/**
 * İnsan okunabilir tarih formatı (örn: "2 saat önce")
 *
 * @param string|null $date
 * @return string
 */
function timeAgo(?string $date): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Az önce';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' dakika önce';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' saat önce';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' gün önce';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' hafta önce';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' ay önce';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' yıl önce';
    }
}

/**
 * Slug oluşturur (URL-friendly)
 *
 * @param string $text
 * @return string
 */
function createSlug(string $text): string
{
    // Türkçe karakterleri dönüştür
    $turkish = ['ı', 'İ', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'];
    $english = ['i', 'i', 's', 's', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'];
    $text = str_replace($turkish, $english, $text);

    // Küçük harfe çevir
    $text = mb_strtolower($text, 'UTF-8');

    // Alfanumerik olmayan karakterleri tire ile değiştir
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    // Başta ve sonda tire varsa temizle
    $text = trim($text, '-');

    return $text;
}

/**
 * Dosya boyutunu human-readable formatta döndürür
 *
 * @param int $bytes
 * @param int $decimals
 * @return string
 */
function formatFileSize(int $bytes, int $decimals = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $decimals) . ' ' . $units[$i];
}

/**
 * E-posta validasyonu
 *
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Telefon validasyonu (Türkiye)
 *
 * @param string $phone
 * @return bool
 */
function isValidPhone(string $phone): bool
{
    return preg_match(REGEX_PHONE, $phone) === 1;
}

/**
 * IBAN validasyonu (Türkiye)
 *
 * @param string $iban
 * @return bool
 */
function isValidIban(string $iban): bool
{
    $iban = strtoupper(str_replace(' ', '', $iban));
    return preg_match(REGEX_IBAN, $iban) === 1;
}

/**
 * Şifre güvenlik kontrolü
 *
 * @param string $password
 * @return bool
 */
function isStrongPassword(string $password): bool
{
    // En az 8 karakter, 1 büyük, 1 küçük harf, 1 rakam, 1 özel karakter
    return preg_match(REGEX_PASSWORD, $password) === 1;
}

/**
 * Rastgele string oluşturur
 *
 * @param int $length
 * @return string
 */
function generateRandomString(int $length = 16): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Para formatlar (Türk Lirası)
 *
 * @param float|null $amount
 * @param bool $showCurrency
 * @return string
 */
function formatMoney(?float $amount, bool $showCurrency = true): string
{
    if ($amount === null) {
        return '';
    }

    $formatted = number_format($amount, 2, ',', '.');

    return $showCurrency ? $formatted . ' ₺' : $formatted;
}

/**
 * Sayıyı formatlar (Türkçe)
 *
 * @param float|null $number
 * @param int $decimals
 * @return string
 */
function formatNumber(?float $number, int $decimals = 0): string
{
    if ($number === null) {
        return '';
    }

    return number_format($number, $decimals, ',', '.');
}

/**
 * Resim yükler
 *
 * @param array $file $_FILES array'inden gelen dosya
 * @param string $directory Upload dizini
 * @param array $allowedTypes İzin verilen dosya tipleri
 * @param int $maxSize Maksimum dosya boyutu (bytes)
 * @return string|false Başarılıysa dosya adı, değilse false
 */
function uploadImage(array $file, string $directory, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'], int $maxSize = FILE_SIZE_10MB)
{
    // Dosya yüklendi mi?
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    // Dosya boyutu kontrolü
    if ($file['size'] > $maxSize) {
        setFlash('error', 'Dosya boyutu çok büyük. Maksimum: ' . formatFileSize($maxSize));
        return false;
    }

    // Dosya tipi kontrolü
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        setFlash('error', 'Geçersiz dosya tipi. İzin verilenler: ' . implode(', ', $allowedTypes));
        return false;
    }

    // Benzersiz dosya adı oluştur
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = rtrim($directory, '/') . '/' . $filename;

    // Dizin yoksa oluştur
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    // Dosyayı taşı
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }

    return false;
}

/**
 * Sayfalama için offset hesaplar
 *
 * @param int $page
 * @param int $limit
 * @return int
 */
function getOffset(int $page, int $limit): int
{
    return ($page - 1) * $limit;
}

/**
 * Toplam sayfa sayısını hesaplar
 *
 * @param int $totalItems
 * @param int $itemsPerPage
 * @return int
 */
function getTotalPages(int $totalItems, int $itemsPerPage): int
{
    return (int) ceil($totalItems / $itemsPerPage);
}

/**
 * Debug için değişkeni yazdırır ve durdurur
 *
 * @param mixed ...$vars
 * @return void
 */
function dd(...$vars): void
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Debug için değişkeni yazdırır
 *
 * @param mixed ...$vars
 * @return void
 */
function dump(...$vars): void
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
}

/**
 * JSON yanıt döndürür
 *
 * @param mixed $data
 * @param int $statusCode
 * @return void
 */
function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Hata JSON yanıtı döndürür
 *
 * @param string $message
 * @param int $statusCode
 * @return void
 */
function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Başarı JSON yanıtı döndürür
 *
 * @param mixed $data
 * @param string|null $message
 * @return void
 */
function jsonSuccess($data = null, ?string $message = null): void
{
    $response = ['success' => true];

    if ($message) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        $response['data'] = $data;
    }

    jsonResponse($response);
}

/**
 * CSRF token oluşturur ve döndürür (wrapper for generateCsrfToken)
 *
 * @return string
 */
function getCsrfToken(): string
{
    return generateCsrfToken();
}

/**
 * CSRF token HTML inputu oluşturur
 *
 * @return string
 */
function csrfField(): string
{
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Metni kısaltır
 *
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Config değeri döndürür
 *
 * @param string $key (dot notation: app.name)
 * @param mixed $default
 * @return mixed
 */
function config(string $key, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = require CONFIG_DIR . '/config.php';
    }

    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value;
}

/**
 * Aktif sayfa kontrolü (navigation için)
 *
 * @param string $path
 * @return string
 */
function isActive(string $path): string
{
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($currentPath, $path) === 0 ? 'active' : '';
}
