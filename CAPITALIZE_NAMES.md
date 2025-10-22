# Kullanıcı İsimlerini Büyük Harfle Başlatma

## Sorun
Bazı kullanıcı adları küçük harfle kayıtlı. Tüm isimlerin her kelimesinin ilk harfi büyük olmalı.

## Çözüm

### Production'da Çalıştırma

SSH ile production sunucusuna bağlanın ve şu komutu çalıştırın:

```bash
cd /home/monster/diyetlenio
php scripts/capitalize-names.php
```

### Script Ne Yapar?

- Tüm kullanıcıları (`users` tablosu) tarar
- Her kullanıcının `full_name` alanını kontrol eder
- Her kelimenin ilk harfini büyük yapar (Türkçe karakterler dahil)
- Değişiklikleri veritabanına kaydeder

### Örnek Değişiklikler

```
mehmet yılmaz    → Mehmet Yılmaz
AYŞE DEMİR     → Ayşe Demir
ali KAYA       → Ali Kaya
İbrahim ÖZTÜRK → İbrahim Öztürk
```

### Güvenlik

- Script sadece `full_name` alanını günceller
- Hiçbir kayıt silinmez
- Değişiklikler geri alınamaz, öncesinde backup alın!

### Backup (Önerilen)

Önce veritabanı yedeği alın:

```bash
mysqldump -u root -p diyetlenio_db > backup_before_capitalize.sql
```

### Test (Opsiyonel)

Önce kaç kayıt değişeceğini görmek isterseniz:

```sql
SELECT id, full_name,
       CONCAT(UPPER(SUBSTRING(full_name, 1, 1)),
              LOWER(SUBSTRING(full_name, 2))) as new_name
FROM users
WHERE full_name IS NOT NULL
AND full_name != CONCAT(UPPER(SUBSTRING(full_name, 1, 1)),
                        LOWER(SUBSTRING(full_name, 2)));
```

## Weight Tracking Tablosu

Eğer `weight_tracking` tablosu yoksa, production'da şu migration'ı çalıştırın:

```bash
# Migration dosyası zaten database.sql içinde mevcut
# Sadece production veritabanında çalıştırın:
mysql -u root -p diyetlenio_db < database.sql
```

Ya da sadece weight_tracking tablosunu oluşturmak için:

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
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_date (measurement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
