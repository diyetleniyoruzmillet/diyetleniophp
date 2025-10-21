# Admin Kullanıcısı Kurulum Kılavuzu

## 🚀 Hızlı Kurulum

Admin kullanıcısını veritabanına eklemek için aşağıdaki adımlardan birini izleyin:

---

## Yöntem 1: SQL Dosyası ile (ÖNERİLEN)

Terminal'de şu komutu çalıştırın:

```bash
mysql -u root -p diyetlenio < scripts/create-admin.sql
```

**Not:** MySQL root şifrenizi soracaktır.

---

## Yöntem 2: Veritabanı Tam Kurulum

Eğer veritabanı henüz kurulmadıysa, önce tüm veritabanını kurun:

```bash
# 1. Veritabanını ve kullanıcıyı oluştur
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS diyetlenio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'diyetlenio_user'@'localhost' IDENTIFIED BY 'diyetlenio2025';
GRANT ALL PRIVILEGES ON diyetlenio.* TO 'diyetlenio_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 2. Veritabanı şemasını yükle
mysql -u root -p diyetlenio < database.sql

# 3. Admin kullanıcısını ekle (şema içinde zaten var)
```

---

## Yöntem 3: phpMyAdmin veya MySQL Workbench

1. phpMyAdmin veya MySQL Workbench'i açın
2. `diyetlenio` veritabanını seçin
3. SQL sekmesine gidin
4. Aşağıdaki SQL'i yapıştırın ve çalıştırın:

```sql
-- Mevcut admin kullanıcısını sil (varsa)
DELETE FROM users WHERE email = 'admin@diyetlenio.com';

-- Yeni admin kullanıcısını ekle
INSERT INTO users (email, password, full_name, phone, user_type, is_active, is_email_verified)
VALUES (
    'admin@diyetlenio.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Sistem Yöneticisi',
    '05001234567',
    'admin',
    1,
    1
);
```

---

## 🔑 Admin Giriş Bilgileri

Kurulum tamamlandıktan sonra aşağıdaki bilgilerle giriş yapabilirsiniz:

```
URL:   http://localhost:8000/login.php
Email: admin@diyetlenio.com
Şifre: Admin123!
```

---

## ✅ Kurulumu Doğrulama

Admin kullanıcısının başarıyla eklendiğini doğrulamak için:

```bash
mysql -u root -p diyetlenio -e "SELECT id, email, full_name, user_type FROM users WHERE user_type='admin';"
```

Beklenen çıktı:
```
+----+------------------------+--------------------+-----------+
| id | email                  | full_name          | user_type |
+----+------------------------+--------------------+-----------+
|  1 | admin@diyetlenio.com   | Sistem Yöneticisi  | admin     |
+----+------------------------+--------------------+-----------+
```

---

## 🔧 Sorun Giderme

### Hata: "Access denied for user"
- MySQL root şifrenizi doğru girdiğinizden emin olun
- Alternatif olarak `sudo mysql` komutunu deneyin

### Hata: "Database doesn't exist"
- Önce veritabanını oluşturun: `mysql -u root -p -e "CREATE DATABASE diyetlenio;"`
- Ardından `database.sql` dosyasını import edin

### Hata: "Table 'users' doesn't exist"
- database.sql dosyasını import edin: `mysql -u root -p diyetlenio < database.sql`

---

## 📞 İletişim

Sorun yaşarsanız, proje sahibi ile iletişime geçin.
