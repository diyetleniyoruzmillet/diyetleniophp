# 🔧 VERİTABANI SORUNUNU ÇÖZME KILAVUZU

## 🔍 Sorun Tespit Edildi

Admin girişi yapamıyorsunuz çünkü **veritabanı bağlantısı kurulamıyor**.

Test sonuçları:
- ✗ Veritabanı kullanıcısı `diyetlenio_user` erişim hatası veriyor
- ✗ Veritabanına bağlantı kurulamıyor

---

## ✅ HIZLI ÇÖZÜM (3 Adımda)

### Adım 1: Veritabanını ve Kullanıcıyı Oluştur

Terminalde şu komutu çalıştırın:

```bash
sudo mysql
```

MySQL konsolunda şu komutları sırayla girin:

```sql
-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS diyetlenio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Kullanıcıyı oluştur
CREATE USER IF NOT EXISTS 'diyetlenio_user'@'localhost' IDENTIFIED BY 'diyetlenio2025';

-- Yetkileri ver
GRANT ALL PRIVILEGES ON diyetlenio.* TO 'diyetlenio_user'@'localhost';

-- Yenile
FLUSH PRIVILEGES;

-- Çıkış
EXIT;
```

### Adım 2: .env Dosyasını Güncelle

`.env` dosyasını açın ve şu satırları bulun:

```env
DB_DATABASE=diyetlenio_db
DB_USERNAME=diyetlenio_user
DB_PASSWORD=Vw88kX74Y_P5@_
```

Şu şekilde değiştirin:

```env
DB_DATABASE=diyetlenio
DB_USERNAME=diyetlenio_user
DB_PASSWORD=diyetlenio2025
```

### Adım 3: Veritabanı Şemasını ve Admin Kullanıcısını Oluştur

```bash
# Veritabanı şemasını import et
mysql -u diyetlenio_user -p'diyetlenio2025' diyetlenio < database.sql

# Admin kullanıcısını ekle
mysql -u diyetlenio_user -p'diyetlenio2025' diyetlenio < scripts/create-admin.sql
```

---

## 🔑 Giriş Bilgileri

İşlemler tamamlandıktan sonra:

**URL:** http://localhost:8000/login.php
**Email:** admin@diyetlenio.com
**Şifre:** Admin123!

---

## ✅ Doğrulama

Herşeyin çalıştığını doğrulamak için:

```bash
php scripts/test-admin-login.php
```

Bu script size:
- ✓ Veritabanı bağlantısı başarılı mı?
- ✓ Admin kullanıcısı var mı?
- ✓ Şifre doğru mu?
- ✓ Login sistemi çalışıyor mu?

gibi bilgileri verecektir.

---

## 🆘 Alternatif: Tek Komutla Kurulum

Eğer manuel yapmak istemezseniz, şu bash script'i çalıştırın:

```bash
chmod +x scripts/setup-database.sh
./scripts/setup-database.sh
```

---

## 📋 Sorun Devam Ederse

1. MySQL'in çalıştığını kontrol edin:
   ```bash
   systemctl status mysql
   ```

2. Hangi veritabanlarının var olduğunu kontrol edin:
   ```bash
   sudo mysql -e "SHOW DATABASES;"
   ```

3. Hangi kullanıcıların var olduğunu kontrol edin:
   ```bash
   sudo mysql -e "SELECT user, host FROM mysql.user;"
   ```

4. Bağlantı testi yapın:
   ```bash
   php scripts/check-db-info.php
   ```
