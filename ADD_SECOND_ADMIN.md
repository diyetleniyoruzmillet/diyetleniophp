# İkinci Admin Kullanıcısı Ekleme

Sisteminize ikinci bir admin kullanıcısı eklemek için aşağıdaki yöntemlerden birini kullanın:

---

## 🚀 Yöntem 1: SQL Script ile (ÖNERİLEN)

Terminal'de aşağıdaki komutu çalıştırın:

```bash
sudo mysql diyetlenio < scripts/add-second-admin.sql
```

**Sudo şifresi istemiyorsa:**
```bash
mysql -u root -p diyetlenio < scripts/add-second-admin.sql
```

---

## 🚀 Yöntem 2: Direkt MySQL Komutu

```bash
sudo mysql diyetlenio -e "INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified) VALUES ('admin2@diyetlenio.com', '\$2y\$10\$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy', 'Admin Kullanıcı 2', '05009876543', 'admin', 1, 1);"
```

---

## 🚀 Yöntem 3: phpMyAdmin veya MySQL Workbench

1. phpMyAdmin veya MySQL Workbench'i açın
2. `diyetlenio` veritabanını seçin
3. SQL sekmesine gidin
4. Aşağıdaki SQL'i yapıştırıp çalıştırın:

```sql
INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin2@diyetlenio.com',
    '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    'Admin Kullanıcı 2',
    '05009876543',
    'admin',
    1,
    1
);
```

---

## ✅ Doğrulama

Admin kullanıcısının eklendiğini doğrulamak için:

```bash
sudo mysql diyetlenio -e "SELECT id, email, full_name, user_type FROM users WHERE user_type='admin';"
```

---

## 🔑 Giriş Bilgileri

### Admin 1:
- **Email:** admin@diyetlenio.com
- **Şifre:** Admin123!

### Admin 2 (YENİ):
- **Email:** admin2@diyetlenio.com
- **Şifre:** Admin123!

**Giriş URL:** http://localhost:8000/login.php

---

## ⚠️ Not

Eğer `admin2@diyetlenio.com` zaten mevcutsa, şifresini güncellemek için:

```sql
UPDATE users
SET password = '$2y$10$hKRj0zDQUCZ3OjiAD8OZ..UOt14xElB6tGoIW1LJfYMTc9eJ8qMfy',
    is_active = 1,
    is_email_verified = 1
WHERE email = 'admin2@diyetlenio.com';
```
