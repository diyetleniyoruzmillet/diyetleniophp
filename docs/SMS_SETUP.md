# SMS Bildirimleri Kurulum Rehberi

Diyetlenio platformu Türkiye'deki popüler SMS sağlayıcıları ile entegre olarak çalışabilir.

## 🇹🇷 Desteklenen SMS Sağlayıcıları

### 1. Netgsm
- **Web:** https://www.netgsm.com.tr
- **Fiyat:** ~6-8 kuruş/SMS (paket seçimine göre)
- **Özellikler:**
  - Türkiye'nin en yaygın SMS sağlayıcısı
  - Basit HTTP API
  - Kredi kontrolü desteği
  - Hızlı teslimat

### 2. İletimerkezi
- **Web:** https://www.iletimerkezi.com
- **Fiyat:** ~7-10 kuruş/SMS
- **Özellikler:**
  - XML tabanlı API
  - Detaylı raporlama
  - Toplu gönderim desteği

### 3. Vatansms
- **Web:** https://www.vatansms.net
- **Fiyat:** ~5-8 kuruş/SMS
- **Özellikler:**
  - JSON API
  - Uygun fiyat
  - Kolay entegrasyon

## 🚀 Kurulum Adımları

### Adım 1: SMS Sağlayıcısı Seçin ve Hesap Oluşturun

#### Netgsm için:
1. https://www.netgsm.com.tr adresinden kayıt olun
2. SMS paketi satın alın (başlangıç için 1000 SMS yeterli)
3. API kullanıcı adı ve şifrenizi alın
4. SMS başlığınızı (header) belirleyin (örn: "DIYETLENIO")

#### İletimerkezi için:
1. https://www.iletimerkezi.com adresinden kayıt olun
2. API Key alın
3. SMS başlığınızı onaylattırın

#### Vatansms için:
1. https://www.vatansms.net adresinden kayıt olun
2. API credentials alın
3. SMS başlığınızı kaydedin

### Adım 2: .env Dosyasını Yapılandırın

`.env` dosyanıza aşağıdaki satırları ekleyin:

```bash
# SMS (Türkiye Sağlayıcılar)
SMS_PROVIDER=netgsm
SMS_USERNAME=your_username
SMS_PASSWORD=your_password
SMS_API_KEY=your_api_key_if_needed
SMS_HEADER=DIYETLENIO
```

**Netgsm için örnek:**
```bash
SMS_PROVIDER=netgsm
SMS_USERNAME=8503xxxxxx
SMS_PASSWORD=your_password
SMS_HEADER=DIYETLENIO
```

**İletimerkezi için örnek:**
```bash
SMS_PROVIDER=iletimerkezi
SMS_API_KEY=your_api_key
SMS_PASSWORD=your_secret
SMS_HEADER=DIYETLENIO
```

**Vatansms için örnek:**
```bash
SMS_PROVIDER=vatansms
SMS_API_KEY=your_api_id
SMS_PASSWORD=your_api_key
SMS_HEADER=DIYETLENIO
```

### Adım 3: SMS Başlığını Onaylattırın

Türkiye'de ticari SMS göndermek için başlık (header) onayı gereklidir:

1. Sağlayıcınızın paneline giriş yapın
2. "Başlık Başvurusu" bölümüne gidin
3. "DIYETLENIO" başlığı için başvuru yapın
4. Gerekli belgeleri yükleyin (vergi levhası, imza sirküsü)
5. Onay 1-3 iş günü sürebilir

**Önemli:** Başlık onaylanana kadar SMS gönderemezsiniz.

### Adım 4: Test Edin

PHP ile basit test:

```php
<?php
require_once __DIR__ . '/includes/bootstrap.php';

$sms = new SmsService();

// Tek SMS gönder
$result = $sms->send('905XXXXXXXXX', 'Test mesajı - Diyetlenio');

if ($result) {
    echo "✅ SMS başarıyla gönderildi!\n";
} else {
    echo "❌ SMS gönderilemedi. Loglara bakın.\n";
}

// Kredi kontrolü (sadece Netgsm)
$balance = $sms->checkBalance();
echo "Kalan kredi: {$balance} SMS\n";
?>
```

## 📱 SMS Kullanım Senaryoları

### 1. Randevu Onayı (Opsiyonel)

Randevu oluşturulduğunda hem e-posta hem SMS gönderilebilir.

```php
// book-appointment-v2.php içinde
$smsService = new SmsService();
$client = $auth->user();

if (!empty($client->getPhone())) {
    $smsService->sendAppointmentConfirmation($client->getPhone(), [
        'client_name' => $client->getFullName(),
        'dietitian_name' => $dietitian['full_name'],
        'appointment_date' => date('d.m.Y', strtotime($date)),
        'start_time' => date('H:i', strtotime($time))
    ]);
}
```

### 2. Randevu Hatırlatmaları (Cron Job)

`appointment_reminders` tablosunda `reminder_type = 'sms'` olan kayıtlar için:

```php
// Cron job otomatik olarak gönderir
// Kurulum: cron/README.md'ye bakın
```

### 3. Randevu İptalleri

```php
$smsService->sendAppointmentCancellation($client->getPhone(), [
    'appointment_date' => date('d.m.Y', strtotime($appointment['date'])),
    'start_time' => date('H:i', strtotime($appointment['time'])),
    'reason' => 'Diyetisyen tarafından iptal edildi'
]);
```

## 💡 En İyi Pratikler

### 1. SMS Zamanlaması
- **Sabah:** 09:00 - 12:00 arası
- **Öğleden sonra:** 14:00 - 20:00 arası
- **Kaçının:** 22:00 - 09:00 arası (rahatsız edici)

### 2. Mesaj Uzunluğu
- **1 SMS:** 160 karakter (Türkçe: 70 karakter)
- **2 SMS:** 161-320 karakter
- Her fazla SMS ekstra ücretlendirilir

**İyi örnek (67 karakter):**
```
Merhaba,
1 saat sonra Dr. Ayşe ile randevunuz var.
Diyetlenio
```

**Kötü örnek (çok uzun):**
```
Merhaba Değerli Danışanımız,
Bugün saat 14:00'da Uzman Diyetisyen Ayşe Yılmaz ile online randevunuz bulunmaktadır. Lütfen zamanında katılmayı unutmayınız.
Diyetlenio - Sağlıklı Yaşam Platformu
www.diyetlenio.com
```

### 3. Maliyet Optimizasyonu

**Senaryo 1: Sadece E-posta (0₺)**
- Randevu onayı: E-posta
- 24 saat hatırlatma: E-posta
- 1 saat hatırlatma: E-posta
- **Aylık maliyet:** 0₺

**Senaryo 2: Kritik SMS (Önerilen)**
- Randevu onayı: E-posta
- 24 saat hatırlatma: E-posta
- 1 saat hatırlatma: **SMS** ← En önemli
- **Aylık maliyet:** ~50-100₺ (100-200 randevu için)

**Senaryo 3: Tam SMS**
- Randevu onayı: SMS
- 24 saat hatırlatma: SMS
- 1 saat hatırlatma: SMS
- **Aylık maliyet:** ~150-300₺

### 4. Telefon Numarası Toplama

Kullanıcı kayıt formuna telefon alanı ekleyin:

```php
// Kayıt formunda
<input type="tel"
       name="phone"
       placeholder="5XX XXX XX XX"
       pattern="[0-9]{10}"
       required>
```

Database migration:
```sql
ALTER TABLE users ADD COLUMN phone VARCHAR(15) NULL AFTER email;
```

## 🔒 Güvenlik

### KVKK Uyumu

SMS göndermeden önce kullanıcıdan izin almalısınız:

```php
// Kayıt formunda
<label>
    <input type="checkbox" name="accept_sms" required>
    Randevu hatırlatmalarını SMS ile almak istiyorum
</label>
```

Database:
```sql
ALTER TABLE users ADD COLUMN sms_consent BOOLEAN DEFAULT FALSE;
```

Kod:
```php
if ($user->smsConsent()) {
    $smsService->send($user->getPhone(), $message);
}
```

## 📊 İstatistikler ve Raporlama

### SMS Gönderim Raporu

```sql
SELECT
    DATE(sent_at) as date,
    COUNT(*) as total_sent,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM appointment_reminders
WHERE reminder_type = 'sms'
AND sent_at IS NOT NULL
GROUP BY DATE(sent_at)
ORDER BY date DESC;
```

### Aylık Maliyet Hesaplama

```sql
-- Geçen ay gönderilen SMS sayısı
SELECT COUNT(*) * 0.08 as estimated_cost_tl
FROM appointment_reminders
WHERE reminder_type = 'sms'
AND status = 'sent'
AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH);
```

## 🐛 Sorun Giderme

### Hata: "Başlık onaylanmamış"
**Çözüm:** SMS başlığınızı sağlayıcınızdan onaylattırın (1-3 gün sürer)

### Hata: "Kredi yetersiz"
**Çözüm:** SMS paketinizi yenileyin

### Hata: "Geçersiz telefon numarası"
**Çözüm:** Telefon numarasının `905XXXXXXXXX` formatında olduğundan emin olun

### SMS gönderilmiyor
**Kontrol listesi:**
1. `.env` dosyasında credentials doğru mu?
2. SMS başlığı onaylı mı?
3. Kredi var mı? (`$sms->checkBalance()`)
4. Telefon numarası doğru formatta mı?
5. Firewall/IP kısıtlaması var mı?

### Test Modu

Geliştirme ortamında SMS göndermek yerine loglama:

```php
// .env
APP_ENV=development

// SmsService.php içinde
if ($_ENV['APP_ENV'] === 'development') {
    error_log("SMS would be sent to {$phone}: {$message}");
    return true; // Simulate success
}
```

## 💰 Maliyet Tahminleri

**Aylık 200 randevu için:**

| Senaryo | SMS/Randevu | Toplam SMS | Maliyet (@0.08₺) |
|---------|-------------|------------|------------------|
| Sadece e-posta | 0 | 0 | 0₺ |
| 1 saat öncesi | 1 | 200 | 16₺ |
| 24h + 1h | 2 | 400 | 32₺ |
| Onay + 24h + 1h | 3 | 600 | 48₺ |

**Önerilen başlangıç:** 1 saat öncesi SMS hatırlatma (~16₺/ay)

---

**Yardıma ihtiyacınız mı var?**

SMS entegrasyonu ile ilgili sorunlar için:
1. Provider'ınızın dökümanlarını kontrol edin
2. Logları inceleyin: `/var/log/diyetlenio-reminders.log`
3. Test scripti çalıştırın
4. Destek ekibiyle iletişime geçin
