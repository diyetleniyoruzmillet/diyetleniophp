# Diyetlenio - Kapsamlı Güvenlik ve Kod Kalitesi Denetim Raporu

**Tarih:** 2025-10-26
**Denetim Kapsamı:** Veritabanı, Backend, Frontend, API, Güvenlik
**Denetçi:** Claude AI Code Analyzer

---

## 📊 Executive Summary

### Genel Değerlendirme: **7/10**

Proje **sağlam bir temele** sahip:
- ✅ CSRF koruması tüm formlarda mevcut
- ✅ SQL injection'a karşı prepared statements kullanılıyor
- ✅ XSS koruması clean() fonksiyonu ile sağlanmış
- ✅ Rate limiting sistemi var
- ✅ Modern PHP standartları kullanılıyor

Ancak **5 kritik** ve **15 yüksek öncelikli** güvenlik sorunu tespit edildi.

### Kritik İstatistikler

| Kategori | Sayı | Durum |
|----------|------|-------|
| **KRİTİK Sorunlar** | 10 | 🔴 Acil Düzeltme Gerekli |
| **YÜKSEK Öncelik** | 15 | 🟠 1-2 Hafta İçinde |
| **ORTA Öncelik** | 12 | 🟡 1 Ay İçinde |
| **DÜŞÜK Öncelik** | 8 | 🟢 Zaman İçinde |
| **TOPLAM** | 45 | |

---

## 🚨 KRİTİK SORUNLAR (Hemen Düzeltilmeli)

### 1. Database Host Bilgisi Açığa Çıkıyor
**Dosya:** `includes/bootstrap.php:74-75`
**Risk:** Saldırganlar altyapı bilgisi edinir

```php
// SORUNLU:
die('Sistem hatası: ' . $e->getMessage() . '<br><br>DB Host: ' . ($_ENV['DB_HOST'] ?? 'not set'));

// DÜZELTİLMİŞ:
error_log('Database error: ' . $e->getMessage());
die('Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.');
```

**Etki:** Yüksek - Bilgi sızıntısı
**Düzeltme Süresi:** 5 dakika

---

### 2. .env Dosyasında Açık Credentials
**Dosya:** `.env`
**Risk:** GitHub'da tüm production şifreleri görünür

```bash
# AÇIK:
DB_PASSWORD=HrpWATAjzmJhHeUuUWuItKmmwvtVXGZf
MAIL_PASSWORD=diyetlenio2025_smtp_password
```

**Etki:** Kritik - Tam sistem ele geçirme riski
**Aksiyon:**
1. ✅ Tüm şifreleri değiştir
2. ✅ `.env` dosyasını `.gitignore`'a ekle
3. ✅ Git geçmişinden `.env`'i temizle

```bash
# Geçmişten temizleme:
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch .env" \
  --prune-empty --tag-name-filter cat -- --all
```

**Düzeltme Süresi:** 30 dakika

---

### 3. Tanımlanmamış Değişken - Fatal Error
**Dosya:** `public/verify-email.php:39-43`
**Risk:** Email doğrulama çalışmıyor

```php
// SORUNLU:
$logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
// $logStmt hiç prepare edilmemiş!

// DÜZELTİLMİŞ:
$logStmt = $conn->prepare("INSERT INTO activity_logs ...");
$logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
```

**Etki:** Kritik - Kullanıcılar email doğrulayamıyor
**Düzeltme Süresi:** 5 dakika

---

### 4. Yanlış Veritabanı Kolon Adı
**Dosya:** `public/forgot-password.php:39`
**Risk:** Şifre sıfırlama çalışmıyor

```php
// SORUNLU:
WHERE email = ? AND status = 'active'
// users tablosunda 'status' kolonu yok!

// DÜZELTİLMİŞ:
WHERE email = ? AND is_active = 1
```

**Etki:** Kritik - Kullanıcılar şifre sıfırlayamıyor
**Düzeltme Süresi:** 2 dakika

---

### 5. Debug Stack Traces Açığa Çıkıyor
**Dosya:** `public/register-dietitian.php:194-196`
**Risk:** Dosya yolları ve stack trace kullanıcıya gösteriliyor

```php
// SORUNLU:
die('<pre>' . $e->getMessage() . "\n\nFile: " . $e->getFile() . ':' . $e->getLine());

// DÜZELTİLMİŞ:
error_log('Registration error: ' . $e->getMessage() . ' in ' . $e->getFile());
setFlash('error', 'Kayıt sırasında hata oluştu.');
redirect('/register-dietitian.php');
```

**Etki:** Yüksek - Sistem yapısı ifşa ediliyor
**Düzeltme Süresi:** 10 dakika

---

### 6-7. Database Service Class'larında Method Uyumsuzluğu
**Dosya:** `classes/NotificationService.php:29`, `classes/RateLimiter.php:194`
**Risk:** Runtime hataları

```php
// SORUNLU:
$this->db->prepare("..."); // Database sınıfı prepare() metodu sunmuyor!

// DÜZELTİLMİŞ:
$conn = $this->db->getConnection();
$stmt = $conn->prepare("...");
```

**Etki:** Yüksek - Notification ve rate limiting çalışmıyor
**Düzeltme Süresi:** 20 dakika

---

### 8. API Endpoint - Authentication Eksik
**Dosya:** `api/get-available-slots.php`
**Risk:** Herkes tüm diyetisyenlerin programını görebilir

```php
// SORUNLU:
// Auth kontrolü yok - public endpoint

// DÜZELTİLMİŞ:
require_once __DIR__ . '/../includes/bootstrap.php';
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Giriş gerekli']);
    exit;
}
```

**Etki:** Yüksek - Bilgi sızıntısı
**Düzeltme Süresi:** 5 dakika

---

### 9. CSRF Token Mantığı Ters
**Dosya:** `public/admin/settings.php:17`
**Risk:** CSRF koruması çalışmıyor

```php
// SORUNLU:
if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    // Doğrulama başarılı ise çalışır
    // Ama verifyCsrfToken() true döndüğünde devam eder!
}

// DÜZELTİLMİŞ:
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $errors[] = 'Geçersiz form gönderimi.';
} else {
    // İşlem yap
}
```

**Etki:** Kritik - CSRF saldırısına açık
**Düzeltme Süresi:** 2 dakika

---

### 10. Tahmin Edilebilir Security Token'lar
**Dosya:** `public/admin/setup-demo.php:10`, `public/admin/run-migrations.php:12`
**Risk:** Herkes migration çalıştırabilir

```php
// SORUNLU:
$validToken = md5('diyetlenio-migrate-2025'); // Statik string!

// DÜZELTİLMİŞ:
// Bu dosyaları production'da SİL!
// Veya .env'de güçlü token kullan:
$validToken = $_ENV['MIGRATION_TOKEN'];
```

**Etki:** Kritik - Veritabanı manipülasyonu
**Aksiyon:** Bu dosyaları production'da **SİL**
**Düzeltme Süresi:** 1 dakika (dosyaları sil)

---

## 🔶 YÜKSEK ÖNCELİKLİ SORUNLAR (1-2 Hafta İçinde)

### 11. Database Migration Sistemi Tutarsız

**Sorun:** Migration 001-006 eksik, base schema ile çakışmalar var

**Etkilenen Tablolar:**
- `payments` - 2 kez tanımlanmış (database.sql + migration 016)
- `video_sessions` - 2 kez tanımlanmış
- `notifications` - 2 kez tanımlanmış
- `article_comments` - Farklı şemalarla 2 kez

**Düzeltme:**
1. Migration 001-006'yı database.sql'den oluştur
2. Duplicate tanımları kaldır
3. Migration tracking tablosu ekle

```sql
CREATE TABLE schema_migrations (
    version VARCHAR(255) PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Düzeltme Süresi:** 2-3 saat

---

### 12. Eksik Foreign Keys

**Dosya:** `database/migrations/016_create_payments_table.sql`

```sql
-- EKLE:
FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
```

**Düzeltme Süresi:** 10 dakika

---

### 13. UNSIGNED Data Type Eksik

Birçok tabloda `INT` kullanılmış, `INT UNSIGNED` olmalı:
- contact_messages.id
- password_resets.id
- video_sessions.id
- dietitian_availability.id

**Düzeltme Süresi:** 30 dakika

---

### 14. Eksik Index'ler (Performans)

| Tablo | Eksik Index | Sebep |
|-------|-------------|--------|
| emergency_consultations | (assigned_to) | Admin dashboard sorguları |
| emergency_consultations | (urgency_level, status) | Filtreleme |
| contact_messages | (email) | Arama |
| rate_limits | (rate_key, expires_at) | Temizleme |

**Düzeltme Süresi:** 20 dakika

---

### 15. Yetersiz Email Validasyonu
**Dosya:** `public/forgot-password.php`

```php
// EKLE:
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Geçersiz e-posta formatı');
}
```

**Düzeltme Süresi:** 5 dakika

---

### 16-17. MIME Type Kontrolü Yok
**Dosya:** `public/register-client.php`, `public/register-dietitian.php`

```php
// SORUNLU:
// Sadece uzantı kontrolü var

// EKLE:
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mimeType, $allowedMimes)) {
    throw new Exception('Geçersiz dosya tipi');
}
finfo_close($finfo);
```

**Düzeltme Süresi:** 15 dakika

---

### 18. API Rate Limiting Yok
**Dosya:** `api/jitsi-room.php`, `api/get-available-slots.php`

```php
// EKLE (Her iki API'nin başına):
$rateLimiter = new RateLimiter();
if (!$rateLimiter->attempt('api_slots', $auth->id(), 60, 5)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit;
}
```

**Düzeltme Süresi:** 10 dakika

---

### 19. Image URL Validation Yetersiz
**Dosya:** `public/blog-detail.php:465-467`

```php
// SORUNLU:
$photoUrl = (strpos($p,'http')===0) ? $p : ('/assets/uploads/' . ltrim($p,'/'));

// DÜZELTİLMİŞ:
if (strpos($p, 'http') === 0) {
    // External URL - validate domain
    $allowedDomains = ['diyetlenio.com', 'cdn.diyetlenio.com'];
    $urlParts = parse_url($p);
    if (!in_array($urlParts['host'] ?? '', $allowedDomains)) {
        $photoUrl = '/assets/uploads/default-article.jpg';
    } else {
        $photoUrl = $p;
    }
} else {
    $photoUrl = '/assets/uploads/' . ltrim($p, '/');
}
```

**Düzeltme Süresi:** 10 dakika

---

### 20-25. Service Class Eksiklikleri

#### Auth.php - Remember Me Token Eksik
**Satır:** 73-78, 181-182
- Remember-me token kontrolü implementasyonu yok
- TODO yorumu var ama kod yok

#### PDFReport.php - PDF Generation Yok
**Satır:** 10-26
- HTML fallback var, gerçek PDF yok
- TCPDF veya DomPDF kullanılmalı

#### Validator.php - Global $conn Kullanımı
**Satır:** 267
- Tanımlanmamış global değişken
- Database::getInstance() kullanılmalı

**Toplam Düzeltme Süresi:** 2-3 saat

---

## 🟡 ORTA ÖNCELİKLİ SORUNLAR (1 Ay İçinde)

### 26. Hardcoded Domain URL'leri

**Dosya:** `public/blog-detail.php`, `public/index.php`

```php
// SORUNLU:
$shareUrl = 'https://diyetlenio.com/' . $currentPath;

// DÜZELTİLMİŞ:
$shareUrl = BASE_URL . $currentPath;
```

**Etki:** Staging/dev ortamlarında paylaşım linkleri bozuk
**Düzeltme Süresi:** 10 dakika

---

### 27-28. DoS Koruması - Input Length

**Dosya:** `public/blog.php`, `public/recipes.php`

```php
// EKLE:
$search = substr($_GET['search'] ?? '', 0, 100); // Max 100 karakter
```

**Düzeltme Süresi:** 5 dakika

---

### 29. APP_KEY Placeholder
**Dosya:** `.env:8`

```bash
# SORUNLU:
APP_KEY=base64:YourRandomGeneratedKeyHere

# OLUŞTUR:
php -r 'echo "base64:" . base64_encode(random_bytes(32));'
```

**Düzeltme Süresi:** 2 dakika

---

### 30. Token Expiration Çok Uzun

**Dosya:** `public/reset-password.php`, `public/verify-email.php`

```php
// SORUNLU:
WHERE expires_at > NOW() // 24 saat

// DÜZELTİLMİŞ:
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) // 1 saat
```

**Düzeltme Süresi:** 5 dakika

---

### 31-37. Diğer Orta Öncelik Sorunlar

- Missing upload path validation
- Session function name inconsistency
- Missing SMS config in config.php
- Error suppression (@) operators
- Missing admin role granularity
- Duplicate notification classes
- Type safety issues (no type hints)

**Toplam Düzeltme Süresi:** 3-4 saat

---

## 🟢 DÜŞÜK ÖNCELİKLİ İYİLEŞTİRMELER

38. DECIMAL(5,2) → DECIMAL(6,2) güvenlik payı için
39. Admin audit logging eksik
40. CORS headers eksik (gerekirse)
41. CSP (Content Security Policy) headers yok
42. X-Frame-Options header yok
43. Migration history tracking yok
44. Orphaned SQL files (database/ klasöründe)
45. Code duplication (Notification vs NotificationService)

---

## 📋 ÖNCELİKLİ DÜZELTME PLANI

### Hafta 1 (KRİTİK) - ~8 saat

**Gün 1:**
- [ ] .env dosyasını git'ten kaldır ve credentials değiştir (30dk)
- [ ] Database host sızıntısını düzelt (5dk)
- [ ] verify-email.php undefined variable düzelt (5dk)
- [ ] forgot-password.php kolon adını düzelt (2dk)
- [ ] Stack trace sızıntısını düzelt (10dk)
- [ ] Admin settings.php CSRF düzelt (2dk)

**Gün 2:**
- [ ] NotificationService ve RateLimiter method uyumsuzluğunu düzelt (20dk)
- [ ] API authentication ekle (get-available-slots.php) (5dk)
- [ ] Setup dosyalarını production'dan sil (1dk)
- [ ] Strong APP_KEY ve MIGRATION_TOKEN oluştur (5dk)

**Gün 3-5:**
- [ ] Database migration sistemini düzenle (3 saat)
  - Migration 001-006 oluştur
  - Duplicate tanımları temizle
  - Migration tracking ekle

### Hafta 2-3 (YÜKSEK) - ~8 saat

**Düzeltmeler:**
- [ ] Foreign key'leri ekle (30dk)
- [ ] UNSIGNED data type'ları düzelt (30dk)
- [ ] Missing index'leri ekle (20dk)
- [ ] Email validation ekle (10dk)
- [ ] MIME type kontrolü ekle (30dk)
- [ ] API rate limiting ekle (20dk)
- [ ] Image URL validation güçlendir (10dk)
- [ ] Service class eksikliklerini tamamla (3 saat)

### Ay 1 (ORTA) - ~4 saat

- [ ] Hardcoded URL'leri düzelt
- [ ] DoS koruması ekle
- [ ] Token expiration süresini kısalt
- [ ] Upload path validation
- [ ] Diğer iyileştirmeler

---

## 🔒 GÜVENLİK ÖNERİLERİ

### 1. Security Headers Ekle

```php
// includes/bootstrap.php sonuna ekle:
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSP (opsiyonel - script/style inline kullanımına göre ayarla):
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com;");
```

### 2. Rate Limiting Genişlet

```php
// Her kritik endpoint'e ekle:
$limits = [
    'login' => [5, 15 * 60],      // 5 attempt / 15 min
    'register' => [3, 10 * 60],   // 3 attempt / 10 min
    'api' => [60, 5 * 60],        // 60 request / 5 min
    'forgot-password' => [3, 60 * 60], // 3 attempt / 1 hour
];
```

### 3. Audit Logging

```sql
CREATE TABLE admin_audit_logs (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
```

### 4. Environment Separation

```bash
# .env.production
APP_ENV=production
APP_DEBUG=false

# .env.development
APP_ENV=development
APP_DEBUG=true

# .env.staging
APP_ENV=staging
APP_DEBUG=true
```

---

## 📈 Test Önerileri

### 1. Security Testing

```bash
# SQL Injection Test
sqlmap -u "https://diyetlenio.com/login.php" --forms --risk=3

# XSS Test
# Manual: <script>alert('XSS')</script>

# CSRF Test
# Postman ile token olmadan POST request
```

### 2. Load Testing

```bash
# Apache Bench
ab -n 1000 -c 10 https://diyetlenio.com/

# API Load Test
ab -n 100 -c 5 https://diyetlenio.com/api/get-available-slots.php?dietitian_id=1&date=2025-11-01
```

### 3. Penetration Testing

- [ ] OWASP Top 10 kontrol listesi
- [ ] Burp Suite scan
- [ ] Nikto web scanner
- [ ] Manual code review (bu rapor)

---

## 📊 Özet Metrikler

### Kod Kalitesi

| Kategori | Skor | Açıklama |
|----------|------|----------|
| Güvenlik | 7/10 | İyi temel, kritik sorunlar var |
| Kod Kalitesi | 8/10 | Temiz kod, bazı eksiklikler |
| Performans | 7/10 | İyi, index'ler eklenebilir |
| Maintainability | 7/10 | İyi organize, dokümantasyon iyi |
| Test Coverage | 3/10 | Test yok (kritik eksik) |

### Güvenlik Puanları

- **Authentication:** 9/10 ✅ Excellent
- **Authorization:** 7/10 🟡 Needs granularity
- **CSRF Protection:** 8/10 ✅ Good (1 bug)
- **SQL Injection:** 9/10 ✅ Excellent
- **XSS Protection:** 8/10 ✅ Good
- **Rate Limiting:** 6/10 🟡 Partial
- **Input Validation:** 7/10 🟡 Good, needs improvement
- **Error Handling:** 6/10 🟡 Information disclosure

---

## 💾 Backup Önerisi

Düzeltmelere başlamadan önce:

```bash
# Database backup
mysqldump -u root -p railway > backup_$(date +%Y%m%d_%H%M%S).sql

# Code backup
git tag -a "pre-security-fixes" -m "Backup before security fixes"
git push origin pre-security-fixes

# Full project backup
tar -czf diyetlenio_backup_$(date +%Y%m%d).tar.gz /home/monster/diyetlenio/
```

---

## 📞 Destek Kaynakları

- **OWASP Top 10:** https://owasp.org/www-project-top-ten/
- **PHP Security Best Practices:** https://www.php.net/manual/en/security.php
- **PDO Security:** https://www.php.net/manual/en/pdo.prepared-statements.php
- **CSRF Prevention:** https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

---

**Rapor Sonu**
**Toplam Analiz Süresi:** ~4 saat
**Tahmini Düzeltme Süresi:** ~20 saat (tüm sorunlar için)
**Kritik Düzeltme Süresi:** ~8 saat (Hafta 1 için)

Bu rapor otomatik ve manuel kod analizi ile hazırlanmıştır.
Tüm bulguları production'a deploy etmeden önce düzeltmeniz önerilir.
