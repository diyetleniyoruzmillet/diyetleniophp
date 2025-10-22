# 🛡️ Güvenlik İmplementasyon Rehberi

Bu dokümandaki güvenlik araçlarını kullanarak uygulamanızı güvenli hale getirin.

---

## 📚 İçindekiler

1. [XSS Koruması](#xss-koruması)
2. [CSRF Koruması](#csrf-koruması)
3. [Input Validation](#input-validation)
4. [Rate Limiting](#rate-limiting)
5. [Dosya Yükleme Güvenliği](#dosya-yükleme-güvenliği)
6. [Örnek Kullanımlar](#örnek-kullanımlar)

---

## 1. XSS Koruması

### Temel Kullanım

**Her output'ta `clean()` fonksiyonunu kullanın:**

```php
<?php
// ❌ YANLIŞ - XSS açığı var!
echo $user['name'];
echo $_POST['comment'];

// ✅ DOĞRU - XSS korumalı
echo clean($user['name']);
echo clean($_POST['comment']);
?>
```

### HTML İçerik Temizleme

Blog yazıları, yorumlar gibi HTML içeren alanlar için:

```php
<?php
// Sadece güvenli HTML tag'lerine izin ver
echo cleanHtml($article['content']);

// Custom tag listesi
echo cleanHtml($content, ['p', 'strong', 'em', 'a']);
?>
```

### Array Temizleme

```php
<?php
// Tüm array elemanlarını temizle
$cleanData = cleanArray($_POST);
?>
```

### URL Güvenliği

```php
<?php
// URL validasyonu
if (isValidUrl($url)) {
    echo '<a href="' . clean($url) . '">Link</a>';
}

// Redirect URL güvenliği (open redirect koruması)
$safeUrl = sanitizeRedirectUrl($_GET['redirect'], '/dashboard.php');
redirect($safeUrl);
?>
```

---

## 2. CSRF Koruması

### Form'lara CSRF Token Ekleme

**HTML Formlar:**

```php
<form method="POST" action="/profile/update.php">
    <?= csrfField() ?>
    <!-- veya -->
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
    
    <input type="text" name="name" value="<?= clean($user['name']) ?>">
    <button type="submit">Kaydet</button>
</form>
```

### POST Handler'da CSRF Kontrolü

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü - MUTLAKA yapın!
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Geçersiz form gönderimi. CSRF token hatalı.');
    }

    // İşlemlere devam et...
    $name = $_POST['name'];
    // ...
}
?>
```

### AJAX İsteklerinde CSRF

```javascript
// JavaScript ile CSRF token gönderme
fetch('/api/update-profile', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': '<?= getCsrfToken() ?>'
    },
    body: JSON.stringify({
        name: 'Ahmet Yılmaz'
    })
});
```

```php
<?php
// PHP tarafında AJAX CSRF kontrolü
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    jsonError('CSRF token hatalı', 403);
}
?>
```

---

## 3. Input Validation

### Validator Sınıfı Kullanımı

```php
<?php
// Basit validasyon
$validator = new Validator($_POST);

$validator->required(['name', 'email', 'password'])
          ->email('email')
          ->min('password', 8)
          ->max('name', 100);

if ($validator->fails()) {
    // Hataları göster
    $errors = $validator->errors();
    $firstError = $validator->firstError();
    
    setFlash('error', $firstError);
    redirectBack();
}

// Temizlenmiş veriyi al
$data = $validator->validated();
```

### Gelişmiş Validasyon Örnekleri

```php
<?php
// Kullanıcı kayıt formu
$validator = new Validator($_POST);

$validator
    ->required(['full_name', 'email', 'password', 'password_confirm', 'phone'])
    ->email('email')
    ->min('password', 8)
    ->match('password_confirm', 'password')
    ->phone('phone')
    ->unique('email', 'users', 'email'); // Email benzersiz olmalı

if ($validator->fails()) {
    foreach ($validator->errors() as $field => $errors) {
        foreach ($errors as $error) {
            setFlash('error', $error);
        }
    }
    redirectBack();
}

// Profil güncelleme (mevcut kullanıcı hariç)
$validator = new Validator($_POST);
$validator->unique('email', 'users', 'email', $userId);
```

### Özel Validasyon

```php
<?php
$validator = new Validator($_POST);

$validator->custom('age', function($value) {
    return $value >= 18 && $value <= 120;
}, 'Yaş 18-120 arasında olmalıdır.');

$validator->custom('username', function($value) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $value);
}, 'Kullanıcı adı sadece harf, rakam ve _ içerebilir.');
```

### Dosya Validasyonu

```php
<?php
$validator = new Validator($_POST);

// Resim yükleme
$validator->file('profile_photo', ['jpg', 'jpeg', 'png'], 5242880); // 5MB

// Döküman yükleme
$validator->file('document', ['pdf', 'doc', 'docx'], 10485760); // 10MB

if ($validator->fails()) {
    setFlash('error', $validator->firstError());
    redirectBack();
}
```

---

## 4. Rate Limiting

### Login Koruması

```php
<?php
$rateLimiter = new RateLimiter($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 5 deneme / 15 dakika
    if ($rateLimiter->tooManyAttempts('login', null, 5, 15)) {
        $remainingSeconds = $rateLimiter->availableIn('login', null, 15);
        $remainingMinutes = ceil($remainingSeconds / 60);
        
        setFlash('error', "Çok fazla deneme. {$remainingMinutes} dakika sonra tekrar deneyin.");
        redirectBack();
    }

    // Login kontrolü...
    if ($auth->attempt($email, $password)) {
        // Başarılı - önceki hataları sil
        $rateLimiter->clear(hash('sha256', 'login|ip_' . $_SERVER['REMOTE_ADDR']));
        redirect('/dashboard.php');
    } else {
        // Rate limit'e kaydet
        $rateLimiter->hit(hash('sha256', 'login|ip_' . $_SERVER['REMOTE_ADDR')), 15);
        setFlash('error', 'Email veya şifre hatalı.');
    }
}
?>
```

### API Rate Limiting

```php
<?php
// API endpoint koruması: 60 istek / dakika
$rateLimiter = new RateLimiter($db);

if ($rateLimiter->tooManyAttempts('api_call', 'user_' . $userId, 60, 1)) {
    jsonError('Rate limit exceeded. Try again later.', 429);
}

$rateLimiter->hit(hash('sha256', 'api_call|user_' . $userId), 1);

// API işlemlerine devam et...
?>
```

### Form Spam Koruması

```php
<?php
// Contact form: 3 mesaj / 10 dakika
$rateLimiter = new RateLimiter($db);

if ($rateLimiter->tooManyAttempts('contact_form', null, 3, 10)) {
    setFlash('error', 'Çok fazla mesaj gönderdiniz. Lütfen bekleyin.');
    redirectBack();
}

// Form gönder
$rateLimiter->hit(hash('sha256', 'contact_form|ip_' . $_SERVER['REMOTE_ADDR']), 10);
?>
```

### Rate Limit Tablosunu Oluşturma

```bash
# Migration'ı çalıştırın:
mysql -u username -p database_name < database/migrations/017_create_rate_limits_table.sql
```

---

## 5. Dosya Yükleme Güvenliği

### Güvenli Resim Yükleme

```php
<?php
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../assets/uploads/profiles/';
    
    // Güvenli yükleme (MIME type kontrolü dahil)
    $filename = uploadImage(
        $_FILES['profile_photo'],
        $uploadDir,
        ['jpg', 'jpeg', 'png'],  // İzin verilen tipler
        5242880                   // 5MB max
    );

    if ($filename) {
        // Başarılı
        $photoPath = 'profiles/' . $filename;
        $db->update('users', ['photo' => $photoPath], $userId);
        setFlash('success', 'Profil fotoğrafı güncellendi.');
    } else {
        // Hata (uploadImage zaten flash message set eder)
        redirectBack();
    }
}
?>
```

### Validator ile Dosya Kontrolü

```php
<?php
$validator = new Validator($_POST);
$validator->file('resume', ['pdf', 'doc', 'docx'], 10485760); // 10MB

if ($validator->fails()) {
    setFlash('error', $validator->error('resume'));
    redirectBack();
}

// Dosyayı yükle
$filename = uploadImage($_FILES['resume'], $uploadDir, ['pdf', 'doc', 'docx']);
?>
```

---

## 6. Örnek Kullanımlar

### Örnek 1: Güvenli Profil Güncelleme

```php
<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Yetki kontrolü
if (!$auth->check()) {
    redirect('/login.php');
}

$user = $auth->user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Geçersiz form gönderimi.');
        redirectBack();
    }

    // Validasyon
    $validator = new Validator($_POST);
    $validator
        ->required(['full_name', 'phone'])
        ->min('full_name', 3)
        ->max('full_name', 100)
        ->phone('phone');

    // Email değiştiyse benzersizlik kontrolü
    if ($_POST['email'] !== $user->getEmail()) {
        $validator->email('email')
                  ->unique('email', 'users', 'email', $user->getId());
    }

    if ($validator->fails()) {
        $errors = $validator->errors();
    } else {
        // Güncelle
        $data = [
            'full_name' => sanitizeString($_POST['full_name'], 100),
            'email' => $_POST['email'],
            'phone' => $_POST['phone']
        ];

        try {
            $db->update('users', $data, $user->getId());
            setFlash('success', 'Profil güncellendi.');
            redirect('/profile.php');
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            setFlash('error', 'Bir hata oluştu.');
        }
    }
}
?>

<!-- HTML Form -->
<form method="POST">
    <?= csrfField() ?>
    
    <input type="text" name="full_name" value="<?= clean($user->getFullName()) ?>" required>
    <?php if (isset($errors['full_name'])): ?>
        <span class="error"><?= clean($errors['full_name'][0]) ?></span>
    <?php endif; ?>
    
    <input type="email" name="email" value="<?= clean($user->getEmail()) ?>" required>
    
    <button type="submit">Kaydet</button>
</form>
```

### Örnek 2: Güvenli API Endpoint

```php
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Auth kontrolü
if (!$auth->check()) {
    jsonError('Unauthorized', 401);
}

$userId = $auth->user()->getId();

// Rate limiting
$rateLimiter = new RateLimiter($db);
if ($rateLimiter->tooManyAttempts('api_update', 'user_' . $userId, 30, 1)) {
    jsonError('Too many requests', 429);
}

// CSRF kontrolü
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    jsonError('Invalid CSRF token', 403);
}

// JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validasyon
$validator = new Validator($input);
$validator->required(['name', 'email'])
          ->email('email')
          ->min('name', 3)
          ->max('name', 100);

if ($validator->fails()) {
    jsonError($validator->firstError(), 400);
}

// Rate limit'e kaydet
$rateLimiter->hit(hash('sha256', 'api_update|user_' . $userId), 1);

// İşlem
try {
    $db->update('users', [
        'full_name' => sanitizeString($input['name'], 100),
        'email' => $input['email']
    ], $userId);

    jsonSuccess(['message' => 'Updated successfully']);
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage());
    jsonError('Internal server error', 500);
}
?>
```

### Örnek 3: Güvenli Admin İşlemi

```php
<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// Admin yetkisi kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    setFlash('error', 'Yetkiniz yok.');
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF kontrolü - KRİTİK!
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token hatalı');
    }

    $action = $_POST['action'];
    $userId = sanitizeInt($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        setFlash('error', 'Geçersiz kullanıcı.');
        redirectBack();
    }

    switch ($action) {
        case 'activate':
            $db->update('users', ['is_active' => 1], $userId);
            setFlash('success', 'Kullanıcı aktifleştirildi.');
            break;

        case 'deactivate':
            $db->update('users', ['is_active' => 0], $userId);
            setFlash('success', 'Kullanıcı deaktifleştirildi.');
            break;

        case 'delete':
            // Rate limiting (silme işlemi için)
            $rateLimiter = new RateLimiter($db);
            if ($rateLimiter->tooManyAttempts('admin_delete', 'user_' . $auth->user()->getId(), 10, 1)) {
                setFlash('error', 'Çok fazla silme işlemi. Bekleyin.');
                redirectBack();
            }

            $rateLimiter->hit(hash('sha256', 'admin_delete|user_' . $auth->user()->getId()), 1);
            $db->delete('users', $userId);
            setFlash('success', 'Kullanıcı silindi.');
            break;

        default:
            setFlash('error', 'Geçersiz işlem.');
    }

    redirectBack();
}
?>
```

---

## ✅ Güvenlik Checklist

Her yeni özellik eklerken kontrol edin:

- [ ] Tüm user input'ları `clean()` ile sanitize edildi
- [ ] POST formlarında `csrfField()` eklendi
- [ ] POST handler'larda `verifyCsrfToken()` kontrolü var
- [ ] Login ve kritik işlemlerde rate limiting eklendi
- [ ] Input'lar `Validator` class ile doğrulandı
- [ ] Dosya yüklemelerinde `uploadImage()` veya `Validator::file()` kullanıldı
- [ ] SQL injection için prepared statements kullanıldı (Database class)
- [ ] Hata mesajları log'landı (`error_log()`)
- [ ] Production'da `DEBUG_MODE=false`
- [ ] Hassas bilgiler loglanmadı

---

## 📞 Destek

Sorularınız için: dev@diyetlenio.com

**Son güncelleme:** 2025-10-22
