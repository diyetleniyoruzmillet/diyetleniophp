# ÜCRETSIZ VİDEO GÖRÜŞME ÇÖZÜMLERİ

**Tarih:** 25 Ekim 2025
**Maliyet:** 0₺/ay
**Hedef:** Production-ready ücretsiz video consultation sistemi

---

## SEÇENEK 1: JITSI MEET (ÖNERİLEN) ⭐⭐⭐⭐⭐

### Neden Jitsi?

**✅ Avantajları:**
- 🆓 **Tamamen ücretsiz** - Sınırsız kullanım, hiç ödeme yok
- ✅ Kolay entegrasyon (5 dakikada çalışır)
- ✅ Kayıt özelliği var (self-hosted versiyonda)
- ✅ Ekran paylaşımı built-in
- ✅ Mobil uyumlu (iOS/Android)
- ✅ Açık kaynak (güvenilir)
- ✅ Yüksek kalite video/audio
- ✅ Sınırsız katılımcı (bizim için 2 yeterli)
- ✅ Chat, el kaldırma, blur background gibi özellikler
- ✅ GDPR compliant

**❌ Dezavantajları:**
- meet.jit.si üzerinden kullanırsanız limitler var (her oda max 5 dakika sonra uyarı)
- Self-host ederseniz sunucu maliyeti var (~$10-20/ay)
- Twilio kadar customize edilebilir değil

**💰 Maliyet Karşılaştırması:**
```
Jitsi (meet.jit.si):        0₺/ay ✅
Jitsi (self-hosted):        ~$10-20/ay (Hetzner/DigitalOcean)
Twilio:                     ~$100-150/ay
```

---

## IMPLEMENTAsYON 1: JİTSİ MEET (Meet.jit.si Kullanarak)

### Yaklaşım: External API (En Kolay)

Jitsi'nin public sunucularını kullanarak tamamen ücretsiz, hemen çalışır.

### Backend: Room Oluşturma

**Dosya: `/api/jitsi-room.php`**

```php
<?php
/**
 * API: Jitsi Meet room bilgilerini oluştur
 */

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

// Auth kontrolü
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $appointmentId = (int) ($_GET['appointment_id'] ?? 0);

    if (!$appointmentId) {
        throw new Exception('Appointment ID gerekli');
    }

    // Randevu kontrolü
    $conn = $db->getConnection();
    $stmt = $conn->prepare("
        SELECT a.*,
               u1.full_name as client_name,
               u2.full_name as dietitian_name
        FROM appointments a
        LEFT JOIN users u1 ON a.client_id = u1.id
        LEFT JOIN users u2 ON a.dietitian_id = u2.id
        WHERE a.id = ? AND (a.client_id = ? OR a.dietitian_id = ?)
    ");
    $stmt->execute([$appointmentId, $auth->id(), $auth->id()]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        throw new Exception('Randevu bulunamadı veya erişim yetkiniz yok');
    }

    // Randevu saati kontrolü (30 dakika önce başlatılabilir)
    $appointmentDateTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
    $now = time();
    $thirtyMinsBefore = $appointmentDateTime - (30 * 60);

    if ($now < $thirtyMinsBefore) {
        throw new Exception('Randevu henüz başlamadı. Randevu saatinden 30 dakika önce katılabilirsiniz.');
    }

    // Benzersiz room adı oluştur
    $roomName = 'Diyetlenio_Appointment_' . $appointmentId . '_' . md5($appointmentId . time());

    // Kullanıcı bilgileri
    $userType = $auth->user()->getUserType();
    $displayName = $auth->user()->getFullName();
    if ($userType === 'dietitian') {
        $displayName = 'Dyt. ' . $displayName;
    }

    // Video session kaydı
    $stmt = $conn->prepare("
        INSERT INTO video_sessions (appointment_id, room_id, status, created_at)
        VALUES (?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE
            room_id = VALUES(room_id),
            status = 'active',
            updated_at = NOW()
    ");
    $stmt->execute([$appointmentId, $roomName]);

    // Success response
    echo json_encode([
        'success' => true,
        'room_name' => $roomName,
        'display_name' => $displayName,
        'domain' => 'meet.jit.si',
        'subject' => 'Diyetlenio Randevu #' . $appointmentId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

### Frontend: Jitsi Meet Entegrasyonu

**Dosya: `/public/video-room-jitsi.php`**

```php
<?php
/**
 * Video Call Room - Jitsi Meet
 * Ücretsiz video conferencing
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check()) {
    redirect('/login.php');
}

$user = $auth->user();
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if (!$appointmentId) {
    setFlash('error', 'Geçersiz randevu bilgisi.');
    redirect('/' . $user->getUserType() . '/appointments.php');
}

// Randevu bilgisini çek
$conn = $db->getConnection();
$stmt = $conn->prepare("
    SELECT a.*,
           u1.full_name as client_name,
           u2.full_name as dietitian_name
    FROM appointments a
    LEFT JOIN users u1 ON a.client_id = u1.id
    LEFT JOIN users u2 ON a.dietitian_id = u2.id
    WHERE a.id = ? AND (a.client_id = ? OR a.dietitian_id = ?)
");
$stmt->execute([$appointmentId, $user->getId(), $user->getId()]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Randevu bulunamadı.');
    redirect('/' . $user->getUserType() . '/appointments.php');
}

$isDietitian = $user->getUserType() === 'dietitian';
$participantName = $isDietitian
    ? $appointment['client_name']
    : 'Dyt. ' . $appointment['dietitian_name'];

$pageTitle = 'Video Görüşme';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> - Diyetlenio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #1e293b;
            overflow: hidden;
        }

        #jitsi-container {
            width: 100vw;
            height: 100vh;
        }

        .loading-screen {
            position: fixed;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            z-index: 9999;
        }

        .loading-screen.hidden {
            display: none;
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255,255,255,0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 30px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .info-banner {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .info-banner.hidden {
            display: none;
        }

        .info-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .info-text h5 {
            margin: 0;
            font-weight: 700;
            color: #1e293b;
        }

        .info-text p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .close-banner {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            padding: 5px 10px;
        }

        .close-banner:hover {
            color: #1e293b;
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="spinner"></div>
        <h2>Video görüşme hazırlanıyor...</h2>
        <p>Lütfen bekleyin</p>
    </div>

    <!-- Info Banner -->
    <div class="info-banner" id="infoBanner">
        <div class="info-content">
            <div class="info-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="info-text">
                <h5>Randevu #<?= $appointmentId ?></h5>
                <p><strong><?= clean($participantName) ?></strong> ile görüşme | <?= date('d.m.Y H:i', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) ?></p>
            </div>
        </div>
        <button class="close-banner" onclick="document.getElementById('infoBanner').classList.add('hidden')">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Jitsi Meet Container -->
    <div id="jitsi-container"></div>

    <!-- Jitsi Meet External API -->
    <script src="https://meet.jit.si/external_api.js"></script>
    <script>
        const appointmentId = <?= $appointmentId ?>;
        const backUrl = '/<?= $user->getUserType() ?>/appointments.php';
        let jitsiApi = null;

        // Jitsi room bilgilerini al
        async function initializeJitsi() {
            try {
                const response = await fetch(`/api/jitsi-room.php?appointment_id=${appointmentId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Room oluşturulamadı');
                }

                const { room_name, display_name, domain, subject } = data;

                // Jitsi Meet API options
                const options = {
                    roomName: room_name,
                    width: '100%',
                    height: '100%',
                    parentNode: document.getElementById('jitsi-container'),
                    configOverwrite: {
                        startWithAudioMuted: false,
                        startWithVideoMuted: false,
                        enableWelcomePage: false,
                        prejoinPageEnabled: false, // Bekleme sayfasını atla
                        disableDeepLinking: true,
                        defaultLanguage: 'tr',
                        enableNoisyMicDetection: true,
                        enableNoAudioDetection: true,
                        enableClosePage: false,
                        hideConferenceSubject: false,
                        subject: subject,
                        // Recording (self-hosted'da çalışır)
                        // fileRecordingsEnabled: true,
                        // liveStreamingEnabled: false
                    },
                    interfaceConfigOverwrite: {
                        TOOLBAR_BUTTONS: [
                            'microphone',
                            'camera',
                            'closedcaptions',
                            'desktop',
                            'fullscreen',
                            'fodeviceselection',
                            'hangup',
                            'chat',
                            'raisehand',
                            'videoquality',
                            'filmstrip',
                            'settings',
                            'tileview',
                            'videobackgroundblur',
                            'help'
                        ],
                        SHOW_JITSI_WATERMARK: false,
                        SHOW_WATERMARK_FOR_GUESTS: false,
                        SHOW_BRAND_WATERMARK: false,
                        BRAND_WATERMARK_LINK: '',
                        DEFAULT_BACKGROUND: '#1e293b',
                        DISABLE_VIDEO_BACKGROUND: false,
                        HIDE_INVITE_MORE_HEADER: true,
                        MOBILE_APP_PROMO: false,
                        DISPLAY_WELCOME_PAGE_CONTENT: false,
                        DISPLAY_WELCOME_PAGE_TOOLBAR_ADDITIONAL_CONTENT: false
                    },
                    userInfo: {
                        displayName: display_name
                    }
                };

                // Jitsi API başlat
                jitsiApi = new JitsiMeetExternalAPI(domain, options);

                // Event listeners
                jitsiApi.addEventListener('videoConferenceJoined', (event) => {
                    console.log('Joined conference:', event);
                    document.getElementById('loadingScreen').classList.add('hidden');
                });

                jitsiApi.addEventListener('participantJoined', (event) => {
                    console.log('Participant joined:', event);
                });

                jitsiApi.addEventListener('participantLeft', (event) => {
                    console.log('Participant left:', event);
                });

                jitsiApi.addEventListener('videoConferenceLeft', (event) => {
                    console.log('Left conference:', event);
                    // Randevu sayfasına dön
                    window.location.href = backUrl;
                });

                jitsiApi.addEventListener('readyToClose', () => {
                    console.log('Ready to close');
                    jitsiApi.dispose();
                    window.location.href = backUrl;
                });

                jitsiApi.addEventListener('errorOccurred', (event) => {
                    console.error('Jitsi error:', event);
                    alert('Video görüşme hatası oluştu. Lütfen sayfayı yenileyip tekrar deneyin.');
                });

            } catch (error) {
                console.error('Failed to initialize Jitsi:', error);
                document.getElementById('loadingScreen').innerHTML = `
                    <i class="fas fa-exclamation-triangle" style="font-size: 4rem; margin-bottom: 20px;"></i>
                    <h2>Bağlantı Hatası</h2>
                    <p>${error.message}</p>
                    <button onclick="window.location.href='${backUrl}'" style="margin-top: 20px; padding: 12px 30px; background: white; color: #667eea; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Geri Dön
                    </button>
                `;
            }
        }

        // Sayfayı kapatırken Jitsi'yi temizle
        window.addEventListener('beforeunload', () => {
            if (jitsiApi) {
                jitsiApi.dispose();
            }
        });

        // Banner'ı 5 saniye sonra otomatik gizle
        setTimeout(() => {
            const banner = document.getElementById('infoBanner');
            if (banner) {
                banner.classList.add('hidden');
            }
        }, 5000);

        // Initialize
        initializeJitsi();
    </script>
</body>
</html>
```

---

## SEÇENEK 2: DAILY.CO (Ücretsiz Plan)

### Özellikler

**✅ Avantajları:**
- 🆓 İlk 10,000 dakika/ay ücretsiz (Twilio ile aynı)
- ✅ Kayıt özelliği (ücretli planda)
- ✅ Modern API
- ✅ React/Vue komponenti var
- ✅ Güzel UI

**❌ Dezavantajları:**
- 10,000 dakika sonrası ücretli
- Hesap oluşturma gerekli

### Kısa Implementation

```javascript
// Daily.co embed (çok basit)
<script src="https://unpkg.com/@daily-co/daily-js"></script>

<script>
const room = DailyIframe.createFrame(document.getElementById('container'), {
  iframeStyle: {
    width: '100%',
    height: '100vh',
  },
  showLeaveButton: true,
  showFullscreenButton: true
});

// Room'a katıl
room.join({
  url: 'https://your-domain.daily.co/appointment-123',
  userName: 'Ahmet Yılmaz'
});
</script>
```

**Maliyet:**
- İlk 10,000 dakika: Ücretsiz
- Sonrası: $0.0015/dakika (Twilio ile aynı)

---

## SEÇENEK 3: AGORA.IO (Ücretsiz Plan)

### Özellikler

**✅ Avantajları:**
- 🆓 İlk 10,000 dakika/ay ücretsiz
- ✅ Düşük gecikme (özellikle Asya-Pasifik)
- ✅ Recording API
- ✅ SDK çeşitliliği

**❌ Dezavantajları:**
- Biraz daha karmaşık entegrasyon
- Dokümantasyon Twilio kadar iyi değil

---

## SEÇENEK 4: SELF-HOSTED JITSI (Tam Kontrol)

### Ne Zaman Kullanılmalı?

Eğer:
- ✅ Sınırsız kullanım istiyorsanız
- ✅ Kayıt özelliği istiyorsanız
- ✅ Tam özelleştirme istiyorsanız
- ✅ KVKK/GDPR için kendi sunucunuzda tutmak istiyorsanız

### Gereksinimler

**Sunucu:**
- 4GB RAM
- 2 CPU core
- 50GB SSD
- Ubuntu 20.04/22.04

**Maliyet:**
- Hetzner: €4.51/ay (CX21)
- DigitalOcean: $12/ay (Basic Droplet)
- AWS Lightsail: $10/ay

### Kurulum (10 dakika)

```bash
# 1. Sunucuya bağlan
ssh root@your-server-ip

# 2. Hostname ayarla
hostnamectl set-hostname video.diyetlenio.com

# 3. Jitsi kurulum scriptini çalıştır
wget https://github.com/jitsi/jitsi-meet/releases/latest/download/jitsi-meet_*_all.deb
apt-get update
apt-get install -y gnupg2 nginx-full
wget -qO - https://download.jitsi.org/jitsi-key.gpg.key | apt-key add -
sh -c "echo 'deb https://download.jitsi.org stable/' > /etc/apt/sources.list.d/jitsi-stable.list"
apt-get update
apt-get -y install jitsi-meet

# 4. SSL sertifikası
/usr/share/jitsi-meet/scripts/install-letsencrypt-cert.sh

# 5. JWT authentication (opsiyonel - sadece randevu sahipleri bağlanabilir)
apt-get install -y jitsi-meet-tokens
```

**Avantajları:**
- ✅ Sınırsız kullanım
- ✅ Tam kontrol
- ✅ Kayıt özelliği
- ✅ Branding özelleştirmesi
- ✅ KVKK uyumlu (veri Türkiye'de)

---

## KARŞILAŞTIRMA TABLOSU

| Özellik | Jitsi (meet.jit.si) | Jitsi (Self-hosted) | Daily.co | Agora.io | Twilio |
|---------|---------------------|---------------------|----------|----------|--------|
| **Maliyet** | 0₺/ay | ~$10-20/ay | 0₺ (10k dk) | 0₺ (10k dk) | $100-150/ay |
| **Entegrasyon** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Kayıt** | ❌ | ✅ | ✅ (ücretli) | ✅ | ✅ |
| **Özelleştirme** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Kalite** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Limit** | Yok | Yok | 10k dk/ay | 10k dk/ay | 10k dk/ay |
| **Setup Süresi** | 10 dk | 2 saat | 30 dk | 1 gün | 1 gün |

---

## ÖNERİM: AŞAMALI YAKLAŞIM 🎯

### **Faz 1: Jitsi Meet (meet.jit.si) ile Başla** - HEMEN
- ✅ 0₺ maliyet
- ✅ 10 dakikada implement edilir
- ✅ Hemen test edebilirsiniz
- ✅ Production'a çıkabilir

**Kod:** Yukarıdaki `video-room-jitsi.php` kodunu kullan

### **Faz 2: Kullanıcı Geri Bildirimine Göre Karar** - 1-2 ay sonra

Eğer:
- Kullanıcılar memnun → Devam et
- Kayıt özelliği isteniyor → Self-hosted Jitsi'ye geç ($10/ay)
- Daha profesyonel UI isteniyor → Daily.co veya Twilio'ya geç
- Çok fazla kullanım var (>10k dk/ay) → Self-hosted Jitsi

---

## HEMEN ŞİMDİ YAPILACAKLAR

1. **Backend API oluştur** (5 dakika)
   - `/api/jitsi-room.php` dosyasını ekle

2. **Frontend sayfayı değiştir** (5 dakika)
   - `/public/video-room.php`'yi → `video-room-jitsi.php` ile değiştir

3. **Test et** (2 dakika)
   - 2 farklı tarayıcıda aç
   - Video/audio çalışıyor mu kontrol et

4. **Production'a çıkar** (1 dakika)
   - Git commit + push

**TOPLAM SÜRE:** ~15 dakika

---

## SONUÇ

### En İyi Çözüm: JITSI MEET ⭐

**Şu anki ihtiyacınız için:**
1. ✅ Tamamen ücretsiz
2. ✅ Kolay implementasyon
3. ✅ Yeterli kalite
4. ✅ Hemen kullanılabilir

**İleriye dönük:**
- Eğer kayıt özelliği gerekirse → Self-hosted Jitsi ($10/ay)
- Eğer premium özellikler gerekirse → Twilio/Daily.co ($100+/ay)

---

## HEMEN İMPLEMENT EDELİM Mİ?

Jitsi Meet çözümünü şimdi implement etmemi ister misiniz?

**Ya da:**
- Daily.co'yu mu deneyelim?
- Self-hosted Jitsi kurulumu mu yapalım?
- Diğer seçenekleri mi detaylandırayım?

Söyleyin, hemen başlayalım! 🚀
