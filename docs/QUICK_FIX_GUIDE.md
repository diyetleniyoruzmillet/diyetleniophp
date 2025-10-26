# Hızlı Düzeltme Kılavuzu - Kritik Sorunlar

Bu kılavuz, **ilk 8 saatte** düzeltilmesi gereken kritik sorunların adım adım çözümlerini içerir.

---

## ✅ Checklist (Düzeltme Sırası)

- [ ] 1. .env dosyasını güvenli hale getir (30dk)
- [ ] 2. Database host sızıntısını düzelt (5dk)
- [ ] 3. verify-email.php undefined variable düzelt (5dk)
- [ ] 4. forgot-password.php kolon adını düzelt (2dk)
- [ ] 5. Stack trace sızıntılarını düzelt (10dk)
- [ ] 6. Admin settings.php CSRF düzelt (2dk)
- [ ] 7. Service class method uyumsuzluklarını düzelt (20dk)
- [ ] 8. API authentication ekle (5dk)
- [ ] 9. Setup dosyalarını sil/güvenli yap (5dk)
- [ ] 10. Strong secrets oluştur (5dk)

**Toplam:** ~1.5 saat

---

## 1. .env Dosyasını Güvenli Hale Getir (30dk)

### Adım 1.1: .env'i .gitignore'a ekle

```bash
# .gitignore dosyasına ekle
echo ".env" >> .gitignore
```

### Adım 1.2: Git geçmişinden .env'i kaldır

```bash
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch .env" \
  --prune-empty --tag-name-filter cat -- --all

git push origin --force --all
```

### Adım 1.3: Tüm credentials'ı değiştir

**Database:**
```bash
# Railway.app panelinden yeni database password oluştur
# .env dosyasını güncelle
```

**Email:**
```bash
# SMTP provider panelinden yeni password al
MAIL_PASSWORD=yeni_guvenli_sifre_buraya
```

### Adım 1.4: .env.example güncellenmiş haliyle commit et

```bash
git add .env.example
git commit -m "security: remove .env from git history and update example"
git push origin main
```

---

## 2. Database Host Sızıntısını Düzelt (5dk)

**Dosya:** `includes/bootstrap.php`

```php
// SATIR 74-75'i bul ve değiştir:

// ESKI:
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    http_response_code(500);
    die('Sistem hatası: ' . $e->getMessage() . '<br><br>DB Host: ' . ($_ENV['DB_HOST'] ?? 'not set'));
}

// YENİ:
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    http_response_code(500);
    error_log('Database connection error: ' . $e->getMessage() . ' | Host: ' . ($_ENV['DB_HOST'] ?? 'not set'));
    die('Veritabanı bağlantı hatası oluştu. Lütfen daha sonra tekrar deneyin.<br>Hata kodu: DB_CONN_ERR');
} else {
    error_log('Database connection error: ' . $e->getMessage());
    die('Sistem bakımdadır. Lütfen daha sonra tekrar deneyin.');
}
```

---

## 3. verify-email.php Undefined Variable Düzelt (5dk)

**Dosya:** `public/verify-email.php`

```php
// SATIR 39-43'ü bul:

// ESKI:
// Bu satır eksik veya yanlış:
$logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);

// YENİ:
// Önce prepare et:
$logStmt = $conn->prepare("
    INSERT INTO activity_logs (user_id, action, ip_address, created_at)
    VALUES (?, 'email_verified', ?, NOW())
");
$logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
```

---

## 4. forgot-password.php Kolon Adını Düzelt (2dk)

**Dosya:** `public/forgot-password.php`

```php
// SATIR 39'u bul:

// ESKI:
$stmt = $db->prepare("SELECT id, email, first_name FROM users
                     WHERE email = ? AND status = 'active'");

// YENİ:
$stmt = $db->prepare("SELECT id, email, first_name FROM users
                     WHERE email = ? AND is_active = 1");
```

---

## 5. Stack Trace Sızıntılarını Düzelt (10dk)

### 5.1: register-dietitian.php

**Dosya:** `public/register-dietitian.php`

```php
// SATIR 194-196'yı bul:

// ESKI:
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    die('<h1>Registration Error</h1><pre>' . $e->getMessage() . "\n\nFile: " .
        $e->getFile() . ':' . $e->getLine() . "\n\nTrace:\n" .
        $e->getTraceAsString() . '</pre>');
}

// YENİ:
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log('Registration error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
}
$errors[] = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
```

### 5.2: register-client.php (aynı düzeltme)

```php
// Aynı pattern'i register-client.php'de de uygula
```

---

## 6. Admin settings.php CSRF Düzelt (2dk)

**Dosya:** `public/admin/settings.php`

```php
// SATIR 17'yi bul:

// ESKI:
if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    // İşlemleri yap
}

// YENİ:
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $errors[] = 'Geçersiz form gönderimi.';
} else {
    // İşlemleri yap
}
```

---

## 7. Service Class Method Uyumsuzluklarını Düzelt (20dk)

### 7.1: NotificationService.php

**Dosya:** `classes/NotificationService.php`

```php
// TÜM $this->db->prepare() çağrılarını değiştir:

// ESKI:
$stmt = $this->db->prepare("SELECT ...");

// YENİ:
$conn = $this->db->getConnection();
$stmt = $conn->prepare("SELECT ...");

// Etkilenen satırlar: 29, 45, 62, 78, 94, 110
```

### 7.2: RateLimiter.php

**Dosya:** `classes/RateLimiter.php`

```php
// SATIR 194'ü bul:

// ESKI:
$result = $this->db->query("SELECT id, attempts FROM rate_limits
                            WHERE rate_key = ? AND expires_at > NOW()", [$key])->fetch();

// YENİ:
$conn = $this->db->getConnection();
$stmt = $conn->prepare("SELECT id, attempts FROM rate_limits
                        WHERE rate_key = ? AND expires_at > NOW()");
$stmt->execute([$key]);
$result = $stmt->fetch();
```

### 7.3: Validator.php

**Dosya:** `classes/Validator.php`

```php
// SATIR 267'yi bul:

// ESKI:
global $conn;
$stmt = $conn->prepare($sql);

// YENİ:
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->prepare($sql);
```

---

## 8. API Authentication Ekle (5dk)

**Dosya:** `api/get-available-slots.php`

```php
// SATIR 7'den sonra ekle (bootstrap.php'den sonra):

// EKLE:
// Authentication check
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Bu işlem için giriş yapmalısınız'
    ]);
    exit;
}
```

---

## 9. Setup Dosyalarını Güvenli Yap (5dk)

### 9.1: setup-demo.php'yi sil veya güvenli yap

**Seçenek 1: SİL (Önerilen)**
```bash
rm public/admin/setup-demo.php
```

**Seçenek 2: Güvenli Yap**
```php
// SATIR 10-14'ü değiştir:

// ESKI:
$validToken = md5('setup-demo-2025-' . date('Y-m-d'));

// YENİ:
$validToken = $_ENV['SETUP_DEMO_TOKEN'] ?? '';
if (empty($validToken)) {
    die('Setup demo token not configured in .env');
}
```

### 9.2: run-migrations.php'yi güvenli yap

```php
// SATIR 12'yi değiştir:

// ESKI:
$validToken = md5('diyetlenio-migrate-2025');

// YENİ:
$validToken = $_ENV['MIGRATION_TOKEN'] ?? '';
if (empty($validToken)) {
    die('Migration token not configured in .env');
}
```

---

## 10. Strong Secrets Oluştur (5dk)

### 10.1: APP_KEY Oluştur

```bash
# Terminal'de çalıştır:
php -r 'echo "APP_KEY=" . "base64:" . base64_encode(random_bytes(32)) . "\n";'

# Çıktıyı .env dosyasına yapıştır
```

### 10.2: MIGRATION_TOKEN Oluştur

```bash
# Terminal'de çalıştır:
php -r 'echo "MIGRATION_TOKEN=" . bin2hex(random_bytes(32)) . "\n";'

# Çıktıyı .env dosyasına yapıştır
```

### 10.3: SETUP_DEMO_TOKEN Oluştur (eğer setup-demo.php silmediyseniz)

```bash
php -r 'echo "SETUP_DEMO_TOKEN=" . bin2hex(random_bytes(32)) . "\n";'
```

### 10.4: .env dosyasını güncelle

```bash
# .env dosyasına ekle:
APP_KEY=base64:YourNewlyGeneratedKey...
MIGRATION_TOKEN=yournewlygeneratedtoken123456...
```

---

## ✅ Doğrulama Testleri

Düzeltmeleri yaptıktan sonra test et:

### Test 1: Login
```
https://diyetlenio.com/login.php
- Login çalışıyor mu?
- Hata mesajları güvenli mi?
```

### Test 2: Email Verification
```
- Yeni kullanıcı kaydol
- Email verification linki çalışıyor mu?
- Hata almıyor musun?
```

### Test 3: Password Reset
```
https://diyetlenio.com/forgot-password.php
- Email gönderimi çalışıyor mu?
- Reset linki çalışıyor mu?
```

### Test 4: API
```bash
curl -X GET "https://diyetlenio.com/api/get-available-slots.php?dietitian_id=1&date=2025-11-01"
# 401 Unauthorized dönmeli (giriş yapmadan)
```

### Test 5: Admin Panel
```
https://diyetlenio.com/admin/settings.php
- CSRF koruması çalışıyor mu?
- Form gönderimi başarılı mı?
```

---

## 🔄 Commit Önerisi

```bash
# Tüm değişiklikleri stage'le
git add -A

# Commit
git commit -m "security: fix 10 critical security vulnerabilities

- Remove .env from git history and regenerate credentials
- Fix database host information disclosure
- Fix undefined variable in verify-email.php
- Fix wrong column name in forgot-password.php
- Remove stack trace exposure in registration
- Fix inverted CSRF logic in admin settings
- Fix database method calls in service classes
- Add authentication to API endpoints
- Secure setup/migration files with strong tokens
- Generate strong APP_KEY and MIGRATION_TOKEN

Fixes #SECURITY-001 through #SECURITY-010"

# Push
git push origin main
```

---

## 📊 Sonraki Adımlar

Bu kritik düzeltmelerden sonra:

1. **Hafta 2:** Yüksek öncelikli sorunlar
   - Database migration sistemini düzenle
   - Foreign key'leri ekle
   - MIME type kontrolü ekle
   - API rate limiting ekle

2. **Ay 1:** Orta öncelikli sorunlar
   - Hardcoded URL'leri düzelt
   - Token expiration kısalt
   - DoS koruması ekle

3. **Sürekli:** İyileştirmeler
   - Test coverage ekle
   - Security headers ekle
   - Audit logging ekle

---

## 🆘 Sorun Çözme

### Düzeltme Sonrası Hata Alırsanız:

1. **500 Internal Server Error**
   ```bash
   # PHP error log'ları kontrol et
   tail -f /var/log/php_errors.log
   ```

2. **Database Connection Error**
   ```bash
   # .env credentials'larını kontrol et
   # Database sunucunun çalıştığını doğrula
   ```

3. **Git Push Hatası (.env için)**
   ```bash
   # Cache'i temizle
   git rm --cached .env
   git commit -m "Remove .env from cache"
   ```

---

**Başarılar!**

Bu kılavuzu takip ederek kritik güvenlik açıklarını ~1.5 saatte düzeltebilirsiniz.
