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
 * İzin verilen tag'ler dışındakileri temizler ve güvenli hale getirir
 *
 * @param string|null $html
 * @param array|null $allowedTags İzin verilen HTML tagları
 * @return string
 */
function cleanHtml($html, ?array $allowedTags = null): string
{
    if ($html === null) {
        return '';
    }

    // Varsayılan izin verilen tag'ler
    if ($allowedTags === null) {
        $allowedTags = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    }

    // Tag'leri HTML format'a çevir
    $allowedTagsStr = '<' . implode('><', $allowedTags) . '>';

    // Tag'leri filtrele
    $cleaned = strip_tags($html, $allowedTagsStr);

    // Tehlikeli attribute'ları temizle (onclick, onerror, vb.)
    $cleaned = preg_replace('/\s*on\w+\s*=\s*["\'].*?["\']/i', '', $cleaned);

    // javascript: protokolünü temizle
    $cleaned = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', '', $cleaned);

    // data: protokolünü temizle (XSS için kullanılabilir)
    $cleaned = preg_replace('/src\s*=\s*["\']data:[^"\']*["\']/i', '', $cleaned);

    return $cleaned;
}

/**
 * Array içeriğini temizler (XSS koruması)
 *
 * @param array $data
 * @return array
 */
function cleanArray(array $data): array
{
    $cleaned = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $cleaned[$key] = cleanArray($value);
        } else {
            $cleaned[$key] = clean($value);
        }
    }
    return $cleaned;
}

/**
 * JSON output için string'i escape eder
 *
 * @param string|null $string
 * @return string
 */
function cleanJson($string): string
{
    if ($string === null) {
        return '';
    }

    return json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * URL'in güvenli olup olmadığını kontrol eder
 *
 * @param string $url
 * @return bool
 */
function isValidUrl(string $url): bool
{
    // Boş URL
    if (empty($url)) {
        return false;
    }

    // filter_var ile genel validasyon
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    // Tehlikeli protokolleri engelle
    $dangerousProtocols = ['javascript:', 'data:', 'vbscript:', 'file:'];
    foreach ($dangerousProtocols as $protocol) {
        if (stripos($url, $protocol) === 0) {
            return false;
        }
    }

    return true;
}

/**
 * Redirect için URL'i güvenli hale getirir
 *
 * @param string $url
 * @param string $fallback Geçersiz URL durumunda kullanılacak
 * @return string
 */
function sanitizeRedirectUrl(string $url, string $fallback = '/'): string
{
    // Boş ise fallback
    if (empty($url)) {
        return $fallback;
    }

    // Absolute URL kontrolü - sadece relative URL'lere izin ver
    $parsed = parse_url($url);

    // Host varsa ve kendi domain'imiz değilse engelle (open redirect)
    if (isset($parsed['host'])) {
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($parsed['host'] !== $currentHost) {
            return $fallback;
        }
    }

    // Tehlikeli protokolleri engelle
    if (isset($parsed['scheme']) && !in_array($parsed['scheme'], ['http', 'https'])) {
        return $fallback;
    }

    return $url;
}

/**
 * Input'u integer'a çevirir, geçersizse default döner
 *
 * @param mixed $value
 * @param int $default
 * @return int
 */
function sanitizeInt($value, int $default = 0): int
{
    if (is_numeric($value)) {
        return (int) $value;
    }
    return $default;
}

/**
 * Input'u float'a çevirir, geçersizse default döner
 *
 * @param mixed $value
 * @param float $default
 * @return float
 */
function sanitizeFloat($value, float $default = 0.0): float
{
    if (is_numeric($value)) {
        return (float) $value;
    }
    return $default;
}

/**
 * String'i belirli bir uzunlukta kırpar ve temizler
 *
 * @param string $value
 * @param int $maxLength
 * @return string
 */
function sanitizeString(string $value, int $maxLength = 255): string
{
    $value = trim($value);
    $value = mb_substr($value, 0, $maxLength);
    return $value;
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
 * Resim yükler (Geliştirilmiş güvenlik)
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
        setFlash('error', 'Dosya yükleme hatası.');
        return false;
    }

    // Upload hatası kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Dosya boyutu çok büyük (server limit).',
            UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu çok büyük (form limit).',
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya yüklenmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'PHP extension dosya yüklemeyi durdurdu.',
        ];
        setFlash('error', $errorMessages[$file['error']] ?? 'Bilinmeyen yükleme hatası.');
        return false;
    }

    // Dosya boyutu kontrolü
    if ($file['size'] > $maxSize) {
        setFlash('error', 'Dosya boyutu çok büyük. Maksimum: ' . formatFileSize($maxSize));
        return false;
    }

    // Boş dosya kontrolü
    if ($file['size'] == 0) {
        setFlash('error', 'Dosya boş olamaz.');
        return false;
    }

    // Dosya uzantısı kontrolü
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        setFlash('error', 'Geçersiz dosya tipi. İzin verilenler: ' . implode(', ', $allowedTypes));
        return false;
    }

    // MIME type kontrolü (gerçek dosya tipi)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // İzin verilen MIME type'lar
    $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // Extension ile MIME type eşleşmesi kontrolü
    if (isset($allowedMimeTypes[$extension]) && $mimeType !== $allowedMimeTypes[$extension]) {
        setFlash('error', 'Dosya tipi güvenli değil. Dosya içeriği uzantısıyla eşleşmiyor.');
        error_log("File upload security warning: Extension=$extension, MIME=$mimeType, File=" . $file['name']);
        return false;
    }

    // Resim ise ek kontroller
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        // getimagesize ile resim dosyası doğrulama
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            setFlash('error', 'Dosya geçerli bir resim değil.');
            return false;
        }

        // Resim boyutları kontrolü (çok büyük resimleri engelle)
        $maxWidth = 10000;
        $maxHeight = 10000;
        if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
            setFlash('error', "Resim boyutları çok büyük. Maksimum: {$maxWidth}x{$maxHeight}px");
            return false;
        }
    }

    // Güvenli benzersiz dosya adı oluştur
    $randomName = bin2hex(random_bytes(16));
    $filename = $randomName . '.' . $extension;
    $targetPath = rtrim($directory, '/') . '/' . $filename;

    // Dizin yoksa oluştur
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            setFlash('error', 'Upload dizini oluşturulamadı.');
            error_log("Failed to create upload directory: $directory");
            return false;
        }
    }

    // Dizin yazılabilir mi kontrol et
    if (!is_writable($directory)) {
        setFlash('error', 'Upload dizini yazılabilir değil.');
        error_log("Upload directory not writable: $directory");
        return false;
    }

    // Dosyayı taşı
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Dosya izinlerini güvenli hale getir (okuma + yazma, execute yok)
        chmod($targetPath, 0644);

        return $filename;
    }

    setFlash('error', 'Dosya yüklenemedi.');
    error_log("Failed to move uploaded file to: $targetPath");
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
 * CSRF token oluşturur
 *
 * @return string
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * CSRF token doğrular
 *
 * @param string $token
 * @return bool
 */
if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * CSRF token oluşturur ve döndürür (wrapper for generateCsrfToken)
 *
 * @return string
 */
if (!function_exists('getCsrfToken')) {
    function getCsrfToken(): string
    {
        return generateCsrfToken();
    }
}

/**
 * CSRF token HTML inputu oluşturur
 *
 * @return string
 */
if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        $token = getCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
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
