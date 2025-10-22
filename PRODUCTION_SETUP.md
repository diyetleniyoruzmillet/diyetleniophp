# Production Setup - Weight Tracking & Name Capitalization

Bu dokümantasyon production ortamında çalıştırılması gereken komutları içerir.

## 🎯 Yapılacaklar

1. ✅ Weight tracking tablosu oluşturma
2. ✅ Kullanıcı adlarının ilk harflerini büyük yapma

---

## 📋 Adım 1: Weight Tracking Tablosu

### SSH ile Production'a Bağlanın

```bash
ssh monster@diyetlenio.com
cd /home/monster/diyetlenio
```

### Migration'ı Çalıştırın

**Seçenek 1: SQL dosyası ile**
```bash
mysql -u root -p diyetlenio_db < scripts/01-create-weight-tracking.sql
```

**Seçenek 2: Doğrudan SQL**
```bash
mysql -u root -p diyetlenio_db
```

Sonra şu SQL'i çalıştırın:
```sql
CREATE TABLE IF NOT EXISTS weight_tracking (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id INT UNSIGNED NOT NULL,
    dietitian_id INT UNSIGNED,
    weight DECIMAL(5,2) NOT NULL COMMENT 'Kilogram',
    measurement_date DATE NOT NULL,
    notes TEXT,
    entered_by ENUM('client', 'dietitian') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_date (measurement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Doğrulama

```sql
SHOW TABLES LIKE 'weight_tracking';
DESCRIBE weight_tracking;
```

---

## 📋 Adım 2: Kullanıcı Adlarını Düzelt

### ⚠️ ÖNEMLİ: Önce Backup Alın!

```bash
mysqldump -u root -p diyetlenio_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Seçenek 1: PHP Script ile (Önerilen - Türkçe Karakter Desteği)

```bash
cd /home/monster/diyetlenio
php scripts/capitalize-names.php
```

**Çıktı örneği:**
```
Starting name capitalization...

Found 125 users to process.

Updated: 'mehmet yılmaz' → 'Mehmet Yılmaz'
Updated: 'AYŞE DEMİR' → 'Ayşe Demir'
Updated: 'ali KAYA' → 'Ali Kaya'

✅ Completed!
Total users: 125
Updated: 45
Unchanged: 80
```

### Seçenek 2: SQL ile (Basit İsimler İçin)

**Önce PREVIEW:**
```bash
mysql -u root -p diyetlenio_db < scripts/02-capitalize-names.sql
```

İlk 10 kaydı gösterecek. Eğer sonuçlar uygunsa, dosyadaki UPDATE sorgu comment'ını kaldırıp tekrar çalıştırın.

---

## ✅ Doğrulama

### Weight Tracking Testi

1. Client olarak giriş yapın
2. `/client/weight-tracking.php` sayfasını ziyaret edin
3. Yeni kilo kaydı ekleyin
4. Grafik ve liste görünmeli

### İsim Kontrolü

```sql
-- Küçük harfle başlayan isim kaldı mı?
SELECT full_name FROM users
WHERE full_name IS NOT NULL
AND full_name REGEXP '^[a-z]'
LIMIT 10;

-- Bazı örnekleri göster
SELECT full_name FROM users
WHERE full_name IS NOT NULL
ORDER BY RAND()
LIMIT 10;
```

---

## 🔄 Geri Alma (Eğer Gerekirse)

### Backup'tan Geri Yükleme

```bash
mysql -u root -p diyetlenio_db < backup_20241022_123456.sql
```

### Sadece Weight Tracking Tablosunu Silme

```sql
DROP TABLE IF EXISTS weight_tracking;
```

---

## 📊 Beklenen Sonuçlar

### Başarılı Kurulum Sonrası:

- ✅ Weight tracking tablosu oluşturuldu
- ✅ Client panel'de kilo takibi çalışıyor
- ✅ Tüm kullanıcı isimleri düzgün formatlandı
- ✅ Türkçe karakterler korundu (İ, Ş, Ğ, Ü, Ö, Ç)

### İsim Örnekleri:

| Eski Format | Yeni Format |
|-------------|-------------|
| mehmet yılmaz | Mehmet Yılmaz |
| AYŞE DEMİR | Ayşe Demir |
| ali KAYA | Ali Kaya |
| İbrahim öztürk | İbrahim Öztürk |

---

## ❓ Sorun Giderme

### "Table already exists" Hatası

Normal, tablo zaten var demektir. Kontrol edin:
```sql
SHOW CREATE TABLE weight_tracking;
```

### "Access denied" Hatası

Root şifresi gerekiyor:
```bash
sudo mysql diyetlenio_db < script.sql
```

### PHP Script Çalışmıyor

Alternatif SQL metodunu kullanın veya:
```bash
php -d display_errors=1 scripts/capitalize-names.php
```

---

## 📞 Destek

Sorun yaşarsanız:
1. Hata mesajının screenshot'unu alın
2. Hangi adımda olduğunuzu belirtin
3. Log dosyalarını kontrol edin: `/var/log/mysql/error.log`

---

**Son Güncelleme:** 2025-10-22
**Versiyon:** 1.0
