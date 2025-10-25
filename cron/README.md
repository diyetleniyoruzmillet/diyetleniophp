# Diyetlenio - Cron Job Kurulumu

Bu klasör, Diyetlenio platformunun otomatik görevlerini içerir.

## 📋 Mevcut Cron Job'lar

### 1. Randevu Hatırlatmaları (`send-appointment-reminders.php`)

**Amaç:** Zamanlanmış randevu hatırlatma e-postalarını otomatik olarak gönderir.

**Özellikler:**
- 24 saat öncesi hatırlatma
- 1 saat öncesi hatırlatma
- Sadece "scheduled" durumundaki randevular için gönderir
- Başarısız/başarılı durumları veritabanına kaydeder
- Detaylı loglama

**Çalıştırma Sıklığı:** Her 5 dakikada bir

## 🚀 Kurulum

### Linux/Ubuntu Sunucu

1. Crontab dosyasını düzenleyin:
```bash
crontab -e
```

2. Aşağıdaki satırı ekleyin (path'i kendi sunucunuza göre düzenleyin):
```bash
*/5 * * * * /usr/bin/php /var/www/diyetlenio/cron/send-appointment-reminders.php >> /var/log/diyetlenio-reminders.log 2>&1
```

3. Crontab'ı kaydedin ve çıkın.

4. Log dosyası için izinleri ayarlayın:
```bash
sudo touch /var/log/diyetlenio-reminders.log
sudo chown www-data:www-data /var/log/diyetlenio-reminders.log
```

### Manuel Test

Script'i manuel olarak test etmek için:
```bash
php /path/to/diyetlenio/cron/send-appointment-reminders.php
```

Çıktı örneği:
```
[2025-10-25 14:30:00] Randevu hatırlatmaları kontrolü başlıyor...
Toplam 3 hatırlatma bulundu.
✅ Hatırlatma gönderildi: ahmet@example.com (Randevu #12, 24 saat kaldı)
✅ Hatırlatma gönderildi: ayse@example.com (Randevu #15, 1 saat kaldı)
✅ Hatırlatma gönderildi: mehmet@example.com (Randevu #18, 45 dakika kaldı)

=== ÖZET ===
Başarılı: 3
Başarısız: 0
Toplam: 3
[2025-10-25 14:30:05] İşlem tamamlandı.
```

## 📊 Log İzleme

Cron job loglarını izlemek için:
```bash
tail -f /var/log/diyetlenio-reminders.log
```

Son 50 satırı görüntülemek için:
```bash
tail -n 50 /var/log/diyetlenio-reminders.log
```

## 🔍 Sorun Giderme

### Cron çalışmıyor mu?

1. **Cron servisinin çalıştığını kontrol edin:**
```bash
sudo systemctl status cron
```

2. **Script'in executable olduğunu kontrol edin:**
```bash
ls -la /path/to/diyetlenio/cron/send-appointment-reminders.php
# -rwxr-xr-x olarak görünmeli
```

3. **PHP path'ini kontrol edin:**
```bash
which php
# /usr/bin/php çıktısını vermeli
```

4. **Manuel test yapın:**
```bash
php /path/to/diyetlenio/cron/send-appointment-reminders.php
```

### E-postalar gönderilmiyor mu?

1. **Mail sunucu ayarlarını kontrol edin:**
   - `.env` dosyasında `MAIL_*` değişkenlerini kontrol edin
   - SMTP kullanıyorsanız, bilgilerin doğru olduğundan emin olun

2. **Veritabanını kontrol edin:**
```sql
SELECT * FROM appointment_reminders WHERE status = 'failed';
```

3. **PHP mail fonksiyonunu test edin:**
```bash
echo "Test mail" | mail -s "Test" your@email.com
```

## 🔔 Ek Cron Job İpuçları

### Farklı Zaman Dilimleri

```bash
# Her gün saat 09:00'da
0 9 * * * /usr/bin/php /path/to/script.php

# Her saat başı
0 * * * * /usr/bin/php /path/to/script.php

# Her 30 dakikada bir
*/30 * * * * /usr/bin/php /path/to/script.php

# Pazartesi-Cuma, 09:00-17:00 arası her saat
0 9-17 * * 1-5 /usr/bin/php /path/to/script.php
```

### E-posta Bildirimi

Cron job başarısız olduğunda e-posta almak için:
```bash
MAILTO="admin@diyetlenio.com"
*/5 * * * * /usr/bin/php /path/to/cron/send-appointment-reminders.php
```

## 📝 Gelecek Cron Job'lar

Planlanmış/gelecek özellikler:

- [ ] **SMS hatırlatmaları** (`send-sms-reminders.php`)
- [ ] **Ödeme hatırlatmaları** (`send-payment-reminders.php`)
- [ ] **Kullanılmayan hesap temizliği** (`cleanup-inactive-accounts.php`)
- [ ] **Raporlama** (`generate-weekly-reports.php`)
- [ ] **Veritabanı yedekleme** (`backup-database.php`)

## 🛠️ Geliştirme

Yeni bir cron job eklerken:

1. Script'i bu klasöre ekleyin
2. Başına shebang ekleyin: `#!/usr/bin/env php`
3. Executable yapın: `chmod +x script.php`
4. CLI kontrolü ekleyin
5. Detaylı loglama ekleyin
6. Bu README'yi güncelleyin
7. Crontab'a ekleyin

---

**Son Güncelleme:** 2025-10-25
**Maintainer:** Diyetlenio Development Team
