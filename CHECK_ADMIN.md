# Admin Kullanıcısını Kontrol ve Düzelt

## Sorununuz devam ediyor mu?

Aşağıdaki adımları sırayla deneyin:

---

## ADIM 1: Veritabanındaki Admin'i Kontrol Et

```bash
sudo mysql diyetlenio -e "SELECT id, email, password, user_type, is_active FROM users WHERE email = 'admin@diyetlenio.com';"
```

**Beklenen çıktı:** Admin kullanıcısı gösterilmeli.

**Eğer boş gelirse:** Admin kullanıcısı yok demektir.

---

## ADIM 2: Admin Kullanıcısını Düzelt

### Yöntem A: SQL Script ile (Önerilen)

```bash
sudo mysql diyetlenio < scripts/fix-admin-now.sql
```

### Yöntem B: Doğrudan SQL Komutu

```bash
sudo mysql diyetlenio << 'EOF'
DELETE FROM users WHERE email = 'admin@diyetlenio.com';

INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);

SELECT 'Admin eklendi:' as '';
SELECT id, email, full_name, is_active FROM users WHERE email = 'admin@diyetlenio.com';
EOF
```

### Yöntem C: phpMyAdmin

1. phpMyAdmin'e girin
2. `diyetlenio` veritabanını seçin
3. `users` tablosunu açın
4. SQL sekmesine gidin
5. Şu SQL'i çalıştırın:

```sql
DELETE FROM users WHERE email = 'admin@diyetlenio.com';

INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);
```

---

## ADIM 3: Şifre Hash'ini Doğrula

```bash
php -r "echo 'Test: ' . (password_verify('Admin123!', '\$2y\$10\$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy') ? 'BAŞARILI' : 'BAŞARISIZ') . PHP_EOL;"
```

**Beklenen çıktı:** `Test: BAŞARILI`

---

## ADIM 4: Login Sayfasını Test Et

1. Tarayıcınızda `http://localhost:8000/login.php` adresine gidin
2. Email: `admin@diyetlenio.com`
3. Şifre: `Admin123!`
4. Giriş Yap'a tıklayın

---

## Hala Çalışmıyor mu?

### Debug için log kontrolü:

```bash
# PHP error loglarını kontrol et
tail -f /var/log/apache2/error.log

# VEYA nginx kullanıyorsanız
tail -f /var/log/nginx/error.log
```

### Veritabanı bağlantısını test et:

```bash
php scripts/test-admin-login.php
```

---

## Hangi Veritabanı Kullanılıyor?

```bash
grep "^DB_DATABASE" .env
```

**Çıktı şu olmalı:** `DB_DATABASE=diyetlenio`

Eğer farklıysa, .env dosyasını düzeltin:

```bash
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=diyetlenio/' .env
```

---

## 🔑 Doğru Giriş Bilgileri

- **URL:** http://localhost:8000/login.php
- **Email:** admin@diyetlenio.com
- **Şifre:** Admin123!
- **Şifre Hash:** $2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy

---

## ℹ️ Önemli Notlar

- Şifre **büyük/küçük harfe duyarlıdır**: `Admin123!` (A büyük, sondaki ünlem işareti var)
- Email küçük harf olmalı: `admin@diyetlenio.com`
- CSRF token hatası alıyorsanız, sayfayı yenileyin (Ctrl+Shift+R)
