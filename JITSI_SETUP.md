# JİTSİ MEET - ÜCRETSİZ VİDEO GÖRÜŞME SİSTEMİ

## ✅ KURULUM TAMAMLANDI!

**Tarih:** 25 Ekim 2025
**Durum:** Production Ready
**Maliyet:** 0₺/ay (Tamamen ücretsiz)

---

## 🎯 ÖZELLİKLER

### Video Görüşme
- ✅ **Sınırsız süre** - 45 dakika, 1 saat, 2 saat... limit yok!
- ✅ **HD kalite** - 720p video
- ✅ **Ekran paylaşımı**
- ✅ **Chat mesajlaşma**
- ✅ **Arka plan bulanıklığı** (blur background)
- ✅ **El kaldırma**
- ✅ **Cihaz seçimi** (mikrofon/kamera)
- ✅ **Mobil uyumlu** - iOS & Android

### Güvenlik
- ✅ Sadece randevu sahipleri katılabilir
- ✅ 30 dakika önce katılım kontrolü
- ✅ Benzersiz room ID'ler
- ✅ HTTPS üzerinden güvenli bağlantı

---

## 📁 OLUŞTURULAN DOSYALAR

### 1. Backend API
**Dosya:** `/api/jitsi-room.php`

**Görevi:**
- Randevu kontrolü yapar
- Benzersiz room ID oluşturur
- video_sessions tablosuna kaydeder
- Room bilgilerini döner

**Endpoint:**
```
GET /api/jitsi-room.php?appointment_id=123
```

**Response:**
```json
{
  "success": true,
  "room_name": "Diyetlenio_123_a4f2b8c1",
  "display_name": "Ahmet Yılmaz",
  "domain": "meet.jit.si",
  "subject": "Diyetlenio Randevu #123"
}
```

### 2. Video Room Sayfası
**Dosya:** `/public/video-room-jitsi.php`

**Görevi:**
- Jitsi Meet iframe'ini yükler
- Video görüşmeyi başlatır
- Katılımcı event'lerini dinler
- Görüşme bitince randevu sayfasına yönlendirir

**URL:**
```
/video-room-jitsi.php?appointment_id=123
```

### 3. Güncellenmiş Sayfalar

**Client Randevular:** `/public/client/appointments.php`
- "Görüşmeye Katıl" butonu eklendi
- 30 dakika önce aktif olur
- Geri sayım gösterir

**Dietitian Randevular:** `/public/dietitian/appointments.php`
- "Görüşmeye Katıl" butonu eklendi
- Online randevular için görünür

---

## 🚀 NASIL ÇALIŞIR?

### 1. Kullanıcı Akışı

```
1. Client/Dietitian randevular sayfasına girer
   └─→ /client/appointments.php veya /dietitian/appointments.php

2. Randevu saatinden 30 dakika önce "Görüşmeye Katıl" butonu aktif olur

3. Butona tıklar

4. Backend API randevuyu kontrol eder (/api/jitsi-room.php)
   ├─→ Randevu var mı?
   ├─→ Kullanıcı yetkili mi?
   └─→ Zaman uygun mu?

5. Room bilgileri döner

6. video-room-jitsi.php sayfası açılır

7. Jitsi Meet iframe'i yüklenir

8. Kullanıcı otomatik olarak room'a katılır

9. Karşı taraf katılınca görüşme başlar

10. "Hangup" butonuna basınca görüşme biter
    └─→ Otomatik olarak randevular sayfasına dönülür
```

### 2. Teknik Akış

```php
// 1. API çağrısı
GET /api/jitsi-room.php?appointment_id=123

// 2. Backend kontrol
- Auth check ✓
- Appointment ownership ✓
- Time validation ✓

// 3. Room oluştur
$roomName = 'Diyetlenio_123_a4f2b8c1';

// 4. Database kaydet
INSERT INTO video_sessions (appointment_id, room_id, status)
VALUES (123, 'Diyetlenio_123_a4f2b8c1', 'active')

// 5. Frontend'e gönder
{
  room_name: 'Diyetlenio_123_a4f2b8c1',
  domain: 'meet.jit.si'
}

// 6. Jitsi External API ile bağlan
const jitsiApi = new JitsiMeetExternalAPI('meet.jit.si', {
  roomName: 'Diyetlenio_123_a4f2b8c1',
  ...options
});
```

---

## 🧪 TEST ETME

### Manuel Test (Hızlı)

1. **İki tarayıcı aç** (örn: Chrome + Firefox veya Chrome Normal + Incognito)

2. **İlk tarayıcıda:**
   - Client olarak giriş yap
   - Randevular sayfasına git
   - Online randevu oluştur (is_online = 1)
   - "Görüşmeye Katıl" butonuna tıkla

3. **İkinci tarayıcıda:**
   - Dietitian olarak giriş yap
   - Randevular sayfasına git
   - Aynı randevuyu bul
   - "Görüşmeye Katıl" butonuna tıkla

4. **Video görüşme başlamalı!** ✅

### Test Randevu Oluşturma

SQL'den hızlıca test randevusu oluşturabilirsiniz:

```sql
-- Test randevusu (şu andan 10 dakika sonra, online)
INSERT INTO appointments (
    dietitian_id, client_id, appointment_date, start_time, end_time,
    duration, status, is_online, created_at
) VALUES (
    1, -- Dietitian ID
    2, -- Client ID
    CURDATE(), -- Bugün
    ADDTIME(CURTIME(), '00:10:00'), -- 10 dakika sonra
    ADDTIME(CURTIME(), '00:55:00'), -- 55 dakika sonra
    45,
    'scheduled',
    1, -- Online görüşme
    NOW()
);
```

---

## 🔧 YAPıLANDıRMA

### Video Kalite Ayarları

`video-room-jitsi.php` dosyasında:

```javascript
configOverwrite: {
    resolution: 720, // 360, 720, 1080
    constraints: {
        video: {
            height: { ideal: 720, max: 1080, min: 360 }
        }
    }
}
```

### Toolbar Butonları

Hangi butonların gösterileceğini özelleştir:

```javascript
TOOLBAR_BUTTONS: [
    'microphone',     // Mikrofon aç/kapa
    'camera',         // Kamera aç/kapa
    'desktop',        // Ekran paylaşımı
    'fullscreen',     // Tam ekran
    'hangup',         // Görüşmeyi bitir
    'chat',           // Chat
    'raisehand',      // El kaldır
    'videobackgroundblur' // Arka plan bulanıklığı
    // ...daha fazlası
]
```

### Katılım Zamanı Ayarı

`/api/jitsi-room.php` ve randevu sayfalarında:

```php
// 30 dakika önce katılabilir (değiştirilebilir)
$thirtyMinsBefore = $appointmentTime - (30 * 60);
```

Bunu `(15 * 60)` yaparsanız 15 dakika önce,
`(60 * 60)` yaparsanız 1 saat önce katılabilir.

---

## 📊 VERİTABANI

### video_sessions Tablosu

```sql
CREATE TABLE IF NOT EXISTS video_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    room_id VARCHAR(255) NOT NULL,
    room_sid VARCHAR(255) NULL,
    status ENUM('active', 'completed', 'failed') DEFAULT 'active',
    started_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_appointment (appointment_id)
);
```

Bu tablo zaten var olmalı. Kontrol etmek için:

```sql
SHOW TABLES LIKE 'video_sessions';
```

---

## 🆚 JİTSİ vs DİĞER ÇÖZÜMLER

| Özellik | Jitsi (meet.jit.si) | Zoom | Google Meet | Twilio |
|---------|---------------------|------|-------------|--------|
| **Maliyet** | 0₺/ay | 40₺/ay | 0₺ (60dk limit) | $100-150/ay |
| **Süre Limiti** | ❌ Yok | ⚠️ 40dk (ücretsiz) | ⚠️ 60dk | ❌ Yok |
| **Entegrasyon** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Özelleştirme** | ⭐⭐⭐ | ⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Kalite** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Mobil** | ✅ | ✅ | ✅ | ✅ |
| **Kayıt** | ⚠️ (self-hosted) | ✅ | ✅ (ücretli) | ✅ |

---

## 🔄 GELECEKİ YÜKSELTMErLER

### Self-Hosted Jitsi (İsteğe Bağlı)

Eğer ileride:
- ✅ Kayıt özelliği
- ✅ Tam özelleştirme
- ✅ Branding (logo, renkler)
- ✅ KVKK uyumluluk (veriler Türkiye'de)

isterseniz, kendi sunucunuza Jitsi kurabilirsiniz (~$10-20/ay).

**Gerekli değişiklikler:**
1. `domain` değiştir: `'meet.jit.si'` → `'video.diyetlenio.com'`
2. Sunucu kur (10 dakika)
3. Bitti!

---

## ❓ SIKÇA SORULAN SORULAR

### 1. 45 dakikadan uzun görüşme yapabilir miyiz?
**Evet!** Jitsi Meet'te zaman limiti yok. 1 saat, 2 saat, istediğiniz kadar.

### 2. Kaç kişi katılabilir?
meet.jit.si üzerinden **sınırsız**. Ama 1-1 görüşme için optimize edildi.

### 3. Kayıt özelliği var mı?
meet.jit.si'de **yok**. Self-hosted Jitsi'de **var**.

### 4. Mobil uygulaması var mı?
Mobil tarayıcıdan çalışır. Jitsi Meet uygulamasını da kullanabilirsiniz.

### 5. Güvenli mi?
**Evet.** HTTPS üzerinden, şifreli bağlantı. Açık kaynak, binlerce şirket kullanıyor.

### 6. Ekran paylaşımı var mı?
**Evet.** Desktop sharing butonu var.

### 7. Chat var mı?
**Evet.** Görüşme sırasında yazılı chat yapılabilir.

### 8. Arka plan bulanıklığı var mı?
**Evet.** "Blur background" özelliği var.

---

## 📞 DESTEK

Sorun olursa:

1. **Browser console'u kontrol edin:**
   - F12 → Console tab
   - Kırmızı hatalar varsa screenshotlayın

2. **Video/Audio izinleri:**
   - Tarayıcı kamera/mikrofon iznini verdi mi?
   - Site settings'te kontrol edin

3. **Network sorunları:**
   - HTTPS bağlantısı var mı?
   - Firewall Jitsi'yi engelliyor mu?

---

## ✅ ÖZET

Jitsi Meet entegrasyonu **tamamen hazır**!

**Ne kazandık:**
- 0₺ maliyet
- Sınırsız süre görüşme
- Professional özellikler
- Hemen kullanıma hazır

**Yapılması gereken:**
```bash
git add .
git commit -m "feat: add free Jitsi Meet video calls"
git push
```

**Test et ve kullanmaya başla!** 🚀
