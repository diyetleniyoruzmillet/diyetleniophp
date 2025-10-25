# VIDEO GÖRÜŞME VE RANDEVU SİSTEMİ - KAPSAMLI TASARIM

**Tarih:** 25 Ekim 2025
**Durum:** Tasarım & İmplementasyon Planı
**Hedef:** Production-ready video consultation ve appointment sistemi

---

## MEVCUT DURUM ANALİZİ

### ✅ Video Sistemi - Mevcut Durum
**Hazır Olanlar:**
- ✅ Frontend UI tamamen tasarlanmış (`/public/video-room.php` - 372 satır)
- ✅ Modern video arayüzü (local/remote video, controls, connection status)
- ✅ Socket.IO kütüphanesi dahil edilmiş
- ✅ WebRTC Client JavaScript sınıfı referanslanmış
- ✅ Database: `video_sessions` tablosu var

**Eksikler:**
- ❌ `/assets/js/webrtc-client.js` dosyası yok veya boş
- ❌ WebRTC signaling server yok
- ❌ STUN/TURN server yapılandırması yok
- ❌ Socket.IO server implementasyonu yok

### ⚠️ Randevu Sistemi - Mevcut Durum
**Hazır Olanlar:**
- ✅ Temel randevu formu (`/public/book-appointment.php` - 228 satır)
- ✅ CSRF korumalı
- ✅ Tarih/saat seçimi
- ✅ Çakışma kontrolü
- ✅ Database kaydetme

**Eksikler:**
- ❌ Diyetisyen müsaitlik sistemi yok (saatler hardcoded)
- ❌ Dinamik saat slotları yok
- ❌ Email/SMS bildirimleri entegre değil
- ❌ Ödeme entegrasyonu yok
- ❌ Randevu onaylama workflow'u eksik
- ❌ Randevu iptal/değiştirme sistemi basit
- ❌ Randevu hatırlatıcıları yok

---

## ÇÖZÜM MİMARİSİ

### Seçenek 1: TWILIO VİDEO API (ÖNERİLEN) ⭐

**Avantajları:**
- ✅ Hazır signaling server (yönetilmeye gerek yok)
- ✅ Otomatik STUN/TURN server sağlanır
- ✅ Düşük gecikme, yüksek kalite
- ✅ Kolay entegrasyon (2-3 gün)
- ✅ Kayıt özelliği built-in
- ✅ Ekran paylaşımı desteği
- ✅ Mobile uyumlu

**Dezavantajları:**
- ❌ Ücretli ($50-200/ay kullanıma göre)
- ❌ Üçüncü parti servise bağımlılık

**Fiyatlandırma:**
- İlk 10.000 dakika/ay ücretsiz
- Sonra $0.0015/dakika/katılımcı
- Kayıt: $0.004/dakika
- Ortalama maliyet: $100-150/ay (100 görüşme/ay için)

**Kullanım Akışı:**
```
1. Client/Dietitian randevu saatinde "Görüşmeye Başla" tıklar
2. Backend Twilio API'ye room oluşturma isteği gönderir
3. Twilio access token döner
4. Frontend Twilio Video SDK ile bağlanır
5. WebRTC bağlantısı otomatik kurulur (Twilio signaling server üzerinden)
6. Video/Audio akışı başlar
```

---

### Seçenek 2: SELF-HOSTED WEBRTC + SOCKET.IO

**Avantajları:**
- ✅ Tamamen kendi kontrolünüzde
- ✅ Ek ücret yok (sunucu maliyeti hariç)
- ✅ Özelleştirilebilir

**Dezavantajları:**
- ❌ Signaling server geliştirme gerekli (Node.js - 2 hafta)
- ❌ TURN server maliyeti ($50-100/ay - Coturn)
- ❌ Bakım ve monitoring sorumluluğu
- ❌ Scaling zor

**Gerekli Bileşenler:**
1. **Node.js Signaling Server** (Socket.IO)
2. **TURN Server** (NAT traversal için)
3. **STUN Server** (genelde Google STUN kullanılabilir)

**Mimari:**
```
┌─────────────┐         WebSocket         ┌──────────────────┐
│   Browser   │◄──────────────────────────►│ Node.js Signaling│
│  (Client)   │                            │     Server       │
└─────────────┘                            └──────────────────┘
       │
       │ WebRTC P2P Connection
       │ (STUN/TURN if needed)
       ▼
┌─────────────┐
│   Browser   │
│ (Dietitian) │
└─────────────┘
```

---

## ÖNERİLEN ÇÖZÜM: TWİLİO VİDEO API

### Implementasyon Adımları

#### **PHASE 1: TWILIO SETUP (1 gün)**

**1.1 Twilio Hesabı**
```bash
# Adım 1: Twilio hesabı oluştur (twilio.com)
# Adım 2: Video ürününü aktifleştir
# Adım 3: API credentials al:
#   - Account SID
#   - Auth Token
```

**1.2 Environment Configuration**
```env
# .env dosyasına ekle:
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_API_KEY_SID=SKxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_API_KEY_SECRET=your_api_key_secret_here
```

**1.3 PHP SDK Kurulumu**
```bash
composer require twilio/sdk
```

#### **PHASE 2: BACKEND API (2 gün)**

**2.1 Twilio Service Class**

Dosya: `/classes/TwilioVideoService.php`

```php
<?php

use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Rest\Client;

class TwilioVideoService
{
    private $client;
    private $accountSid;
    private $authToken;
    private $apiKeySid;
    private $apiKeySecret;

    public function __construct()
    {
        $config = include __DIR__ . '/../config/config.php';

        $this->accountSid = $config['twilio']['account_sid'];
        $this->authToken = $config['twilio']['auth_token'];
        $this->apiKeySid = $config['twilio']['api_key_sid'];
        $this->apiKeySecret = $config['twilio']['api_key_secret'];

        $this->client = new Client($this->accountSid, $this->authToken);
    }

    /**
     * Randevu için video room oluştur
     *
     * @param int $appointmentId
     * @return array ['room_sid' => string, 'room_name' => string]
     */
    public function createRoom($appointmentId)
    {
        try {
            $roomName = "appointment_" . $appointmentId;

            // Twilio'da room oluştur
            $room = $this->client->video->v1->rooms->create([
                'uniqueName' => $roomName,
                'type' => 'peer-to-peer', // veya 'group' (3+ kişi için)
                'maxParticipants' => 2,
                'recordParticipantsOnConnect' => false, // İsterseniz true
                'statusCallback' => getenv('APP_URL') . '/api/twilio-webhook.php'
            ]);

            return [
                'room_sid' => $room->sid,
                'room_name' => $room->uniqueName,
                'status' => $room->status
            ];

        } catch (Exception $e) {
            error_log('Twilio room creation error: ' . $e->getMessage());
            throw new Exception('Video odası oluşturulamadı: ' . $e->getMessage());
        }
    }

    /**
     * Kullanıcı için access token oluştur
     *
     * @param string $roomName
     * @param int $userId
     * @param string $userName
     * @return string JWT token
     */
    public function generateAccessToken($roomName, $userId, $userName)
    {
        try {
            // AccessToken oluştur
            $token = new AccessToken(
                $this->accountSid,
                $this->apiKeySid,
                $this->apiKeySecret,
                3600, // 1 saat geçerli
                $userId  // identity
            );

            // Video grant ekle
            $videoGrant = new VideoGrant();
            $videoGrant->setRoom($roomName);

            $token->addGrant($videoGrant);

            return $token->toJWT();

        } catch (Exception $e) {
            error_log('Twilio token generation error: ' . $e->getMessage());
            throw new Exception('Erişim token\'ı oluşturulamadı');
        }
    }

    /**
     * Room'u bitir
     */
    public function completeRoom($roomSid)
    {
        try {
            $this->client->video->v1
                ->rooms($roomSid)
                ->update(['status' => 'completed']);

            return true;
        } catch (Exception $e) {
            error_log('Twilio room completion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Room durumunu getir
     */
    public function getRoomStatus($roomName)
    {
        try {
            $rooms = $this->client->video->v1->rooms->read([
                'uniqueName' => $roomName,
                'status' => 'in-progress'
            ]);

            return count($rooms) > 0 ? $rooms[0] : null;
        } catch (Exception $e) {
            error_log('Twilio room status error: ' . $e->getMessage());
            return null;
        }
    }
}
```

**2.2 API Endpoint: Token Generation**

Dosya: `/api/video-token.php`

```php
<?php
/**
 * API: Video görüşme için Twilio access token oluştur
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

    // Randevu bilgilerini kontrol et
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

    // Twilio service
    $twilioService = new TwilioVideoService();

    // Room oluştur veya mevcut room'u kullan
    $roomName = "appointment_" . $appointmentId;
    $roomInfo = $twilioService->getRoomStatus($roomName);

    if (!$roomInfo) {
        // Room yok, oluştur
        $roomInfo = $twilioService->createRoom($appointmentId);
        $roomSid = $roomInfo['room_sid'];
    } else {
        $roomSid = $roomInfo->sid;
    }

    // Access token oluştur
    $userId = $auth->id();
    $userName = $auth->user()->getFullName();
    $token = $twilioService->generateAccessToken($roomName, $userId, $userName);

    // Video session kaydı oluştur/güncelle
    $stmt = $conn->prepare("
        INSERT INTO video_sessions (appointment_id, room_id, room_sid, status, created_at)
        VALUES (?, ?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE
            room_sid = VALUES(room_sid),
            status = 'active',
            updated_at = NOW()
    ");
    $stmt->execute([$appointmentId, $roomName, $roomSid]);

    // Success response
    echo json_encode([
        'success' => true,
        'token' => $token,
        'room_name' => $roomName,
        'room_sid' => $roomSid,
        'identity' => $userId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

#### **PHASE 3: FRONTEND TWILIO ENTEGRASYONU (2 gün)**

**3.1 Twilio Video SDK Ekleme**

`/public/video-room.php` dosyasını güncelle:

```html
<!-- Twilio Video SDK -->
<script src="https://sdk.twilio.com/js/video/releases/2.27.0/twilio-video.min.js"></script>
```

**3.2 JavaScript Client Kodu**

Dosya: `/assets/js/twilio-video-client.js`

```javascript
/**
 * Twilio Video Client
 * Twilio Video SDK ile video görüşme yönetimi
 */

class TwilioVideoClient {
    constructor(options) {
        this.appointmentId = options.appointmentId;
        this.localVideoEl = options.localVideoEl;
        this.remoteVideoEl = options.remoteVideoEl;
        this.onConnected = options.onConnected || (() => {});
        this.onDisconnected = options.onDisconnected || (() => {});
        this.onParticipantConnected = options.onParticipantConnected || (() => {});
        this.onParticipantDisconnected = options.onParticipantDisconnected || (() => {});
        this.onError = options.onError || (() => {});

        this.room = null;
        this.localTracks = null;
        this.localAudioTrack = null;
        this.localVideoTrack = null;
    }

    /**
     * Video görüşmeyi başlat
     */
    async connect() {
        try {
            // Backend'den Twilio token al
            const response = await fetch(`/api/video-token.php?appointment_id=${this.appointmentId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Token alınamadı');
            }

            const { token, room_name } = data;

            // Local video/audio tracks oluştur
            this.localTracks = await Twilio.Video.createLocalTracks({
                audio: true,
                video: { width: 1280, height: 720 }
            });

            this.localAudioTrack = this.localTracks.find(track => track.kind === 'audio');
            this.localVideoTrack = this.localTracks.find(track => track.kind === 'video');

            // Local video'yu göster
            this.localVideoEl.srcObject = new MediaStream([this.localVideoTrack.mediaStreamTrack]);

            // Twilio room'a bağlan
            this.room = await Twilio.Video.connect(token, {
                name: room_name,
                tracks: this.localTracks,
                audio: true,
                video: true,
                networkQuality: {
                    local: 1, // 0-3 (0: disabled, 3: most detailed)
                    remote: 1
                },
                bandwidthProfile: {
                    video: {
                        mode: 'collaboration',
                        maxSubscriptionBitrate: 2500000
                    }
                },
                preferredVideoCodecs: [{ codec: 'VP8', simulcast: true }],
                maxAudioBitrate: 16000
            });

            console.log('Connected to room:', this.room.name);
            this.onConnected(this.room);

            // Event listeners
            this.room.on('participantConnected', participant => {
                console.log('Participant connected:', participant.identity);
                this.handleParticipantConnected(participant);
            });

            this.room.on('participantDisconnected', participant => {
                console.log('Participant disconnected:', participant.identity);
                this.handleParticipantDisconnected(participant);
            });

            this.room.once('disconnected', room => {
                console.log('Disconnected from room');
                this.cleanup();
                this.onDisconnected();
            });

            // Zaten room'da olan katılımcıları göster
            this.room.participants.forEach(participant => {
                this.handleParticipantConnected(participant);
            });

            return this.room;

        } catch (error) {
            console.error('Connection error:', error);
            this.onError(error);
            throw error;
        }
    }

    /**
     * Katılımcı bağlandığında
     */
    handleParticipantConnected(participant) {
        this.onParticipantConnected(participant);

        // Mevcut tracks'leri göster
        participant.tracks.forEach(publication => {
            if (publication.isSubscribed) {
                this.attachTrack(publication.track);
            }
        });

        // Yeni track eklendiğinde
        participant.on('trackSubscribed', track => {
            console.log('Track subscribed:', track.kind);
            this.attachTrack(track);
        });

        // Track kaldırıldığında
        participant.on('trackUnsubscribed', track => {
            console.log('Track unsubscribed:', track.kind);
            this.detachTrack(track);
        });
    }

    /**
     * Katılımcı ayrıldığında
     */
    handleParticipantDisconnected(participant) {
        this.onParticipantDisconnected(participant);

        // Tracks'leri temizle
        participant.tracks.forEach(publication => {
            if (publication.track) {
                this.detachTrack(publication.track);
            }
        });
    }

    /**
     * Track'i video elementine ekle
     */
    attachTrack(track) {
        if (track.kind === 'video') {
            this.remoteVideoEl.srcObject = new MediaStream([track.mediaStreamTrack]);
        } else if (track.kind === 'audio') {
            const audioEl = track.attach();
            document.body.appendChild(audioEl);
        }
    }

    /**
     * Track'i kaldır
     */
    detachTrack(track) {
        track.detach().forEach(element => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        });
    }

    /**
     * Mikrofonu aç/kapat
     */
    toggleMute() {
        if (this.localAudioTrack) {
            if (this.localAudioTrack.isEnabled) {
                this.localAudioTrack.disable();
            } else {
                this.localAudioTrack.enable();
            }
            return !this.localAudioTrack.isEnabled;
        }
        return false;
    }

    /**
     * Kamerayı aç/kapat
     */
    toggleVideo() {
        if (this.localVideoTrack) {
            if (this.localVideoTrack.isEnabled) {
                this.localVideoTrack.disable();
            } else {
                this.localVideoTrack.enable();
            }
            return !this.localVideoTrack.isEnabled;
        }
        return false;
    }

    /**
     * Bağlantıyı kes
     */
    disconnect() {
        if (this.room) {
            this.room.disconnect();
        }
        this.cleanup();
    }

    /**
     * Temizlik
     */
    cleanup() {
        if (this.localTracks) {
            this.localTracks.forEach(track => {
                track.stop();
            });
        }
    }
}

// Export
window.TwilioVideoClient = TwilioVideoClient;
```

**3.3 video-room.php Güncellemesi**

Eski Socket.IO kodlarını kaldır, Twilio client'ı kullan:

```javascript
<script src="https://sdk.twilio.com/js/video/releases/2.27.0/twilio-video.min.js"></script>
<script src="/assets/js/twilio-video-client.js"></script>
<script>
    const appointmentId = <?= $appointmentId ?>;

    const twilioClient = new TwilioVideoClient({
        appointmentId: appointmentId,
        localVideoEl: document.getElementById('localVideo'),
        remoteVideoEl: document.getElementById('remoteVideo'),

        onConnected: (room) => {
            console.log('Connected to room:', room.name);
            document.getElementById('waitingScreen').classList.add('hidden');
            updateConnectionStatus('connected');
        },

        onDisconnected: () => {
            console.log('Disconnected');
            updateConnectionStatus('disconnected');
        },

        onParticipantConnected: (participant) => {
            console.log('Participant joined:', participant.identity);
            document.getElementById('waitingScreen').classList.add('hidden');
        },

        onParticipantDisconnected: (participant) => {
            console.log('Participant left:', participant.identity);
            document.getElementById('waitingScreen').classList.remove('hidden');
            updateConnectionStatus('waiting');
        },

        onError: (error) => {
            console.error('Error:', error);
            alert('Video görüşme hatası: ' + error.message);
        }
    });

    // Görüşmeyi başlat
    twilioClient.connect().catch(error => {
        console.error('Failed to connect:', error);
        alert('Bağlantı kurulamadı. Lütfen sayfayı yenileyip tekrar deneyin.');
    });

    // Mute button
    document.getElementById('muteBtn').addEventListener('click', () => {
        const isMuted = twilioClient.toggleMute();
        const btn = document.getElementById('muteBtn');
        btn.classList.toggle('active', isMuted);
        btn.querySelector('i').className = isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
    });

    // Video button
    document.getElementById('videoBtn').addEventListener('click', () => {
        const isOff = twilioClient.toggleVideo();
        const btn = document.getElementById('videoBtn');
        btn.classList.toggle('active', isOff);
        btn.querySelector('i').className = isOff ? 'fas fa-video-slash' : 'fas fa-video';
    });

    // End button
    document.getElementById('endBtn').addEventListener('click', () => {
        if (confirm('Görüşmeyi sonlandırmak istediğinizden emin misiniz?')) {
            twilioClient.disconnect();
            window.location.href = '/<?= $user->getUserType() ?>/appointments.php';
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        twilioClient.disconnect();
    });

    function updateConnectionStatus(state) {
        const statusText = document.getElementById('statusText');
        const statusDot = document.querySelector('.status-dot');

        switch(state) {
            case 'connected':
                statusText.textContent = 'Bağlı';
                statusDot.style.background = '#10b981';
                break;
            case 'waiting':
                statusText.textContent = 'Karşı taraf bekleniyor...';
                statusDot.style.background = '#f59e0b';
                break;
            case 'disconnected':
                statusText.textContent = 'Bağlantı Kesildi';
                statusDot.style.background = '#ef4444';
                break;
        }
    }
</script>
```

---

## RANDEVU SİSTEMİ TASARIMI

### Database Schema Güncellemeleri

**1. Availability (Müsaitlik) Tablosu**

Dosya: `/database/migrations/020_create_dietitian_availability.sql`

```sql
CREATE TABLE IF NOT EXISTS dietitian_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dietitian_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Pazar, 1=Pazartesi, ..., 6=Cumartesi',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 45 COMMENT 'Dakika cinsinden',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dietitian_day (dietitian_id, day_of_week),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek veri
INSERT INTO dietitian_availability (dietitian_id, day_of_week, start_time, end_time, slot_duration) VALUES
-- Pazartesi
(1, 1, '09:00:00', '12:00:00', 45),
(1, 1, '13:00:00', '17:00:00', 45),
-- Salı
(1, 2, '09:00:00', '12:00:00', 45),
(1, 2, '13:00:00', '17:00:00', 45),
-- Çarşamba
(1, 3, '09:00:00', '12:00:00', 45),
(1, 3, '13:00:00', '17:00:00', 45);
```

**2. Availability Exceptions (Özel Günler/Tatiller)**

```sql
CREATE TABLE IF NOT EXISTS dietitian_availability_exceptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dietitian_id INT NOT NULL,
    exception_date DATE NOT NULL,
    is_available BOOLEAN DEFAULT FALSE COMMENT 'FALSE=İzin, TRUE=Özel çalışma günü',
    start_time TIME NULL,
    end_time TIME NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (dietitian_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dietitian_date (dietitian_id, exception_date),
    INDEX idx_date (exception_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**3. Appointment Reminders**

```sql
CREATE TABLE IF NOT EXISTS appointment_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    reminder_type ENUM('email', 'sms') NOT NULL,
    scheduled_for DATETIME NOT NULL,
    sent_at DATETIME NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    INDEX idx_scheduled (scheduled_for, status),
    INDEX idx_appointment (appointment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Backend PHP Classes

**1. Availability Service**

Dosya: `/classes/AvailabilityService.php`

```php
<?php

class AvailabilityService
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Diyetisyenin belirli bir tarih için müsait saatlerini getir
     *
     * @param int $dietitianId
     * @param string $date Format: Y-m-d
     * @return array Müsait saat slotları
     */
    public function getAvailableSlots($dietitianId, $date)
    {
        $conn = $this->db->getConnection();
        $dayOfWeek = date('w', strtotime($date)); // 0=Pazar, 6=Cumartesi

        // 1. Exception kontrolü (izinli veya özel çalışma günü)
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability_exceptions
            WHERE dietitian_id = ? AND exception_date = ?
        ");
        $stmt->execute([$dietitianId, $date]);
        $exception = $stmt->fetch();

        if ($exception && !$exception['is_available']) {
            // İzinli gün, boş array dön
            return [];
        }

        // 2. Normal müsaitlik bilgisini getir
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability
            WHERE dietitian_id = ? AND day_of_week = ? AND is_active = 1
            ORDER BY start_time
        ");
        $stmt->execute([$dietitianId, $dayOfWeek]);
        $availabilities = $stmt->fetchAll();

        if (empty($availabilities) && !$exception) {
            // Bu gün çalışmıyor
            return [];
        }

        // Exception varsa ve available ise, o saatleri kullan
        if ($exception && $exception['is_available']) {
            $availabilities = [[
                'start_time' => $exception['start_time'],
                'end_time' => $exception['end_time'],
                'slot_duration' => 45
            ]];
        }

        // 3. Tüm olası time slotları oluştur
        $allSlots = [];
        foreach ($availabilities as $avail) {
            $startTime = strtotime($avail['start_time']);
            $endTime = strtotime($avail['end_time']);
            $duration = ($avail['slot_duration'] ?? 45) * 60; // saniye

            $currentTime = $startTime;
            while ($currentTime + $duration <= $endTime) {
                $allSlots[] = date('H:i:s', $currentTime);
                $currentTime += $duration;
            }
        }

        // 4. Mevcut randevuları getir
        $stmt = $conn->prepare("
            SELECT start_time, end_time FROM appointments
            WHERE dietitian_id = ?
            AND appointment_date = ?
            AND status NOT IN ('cancelled', 'no-show')
        ");
        $stmt->execute([$dietitianId, $date]);
        $bookedAppointments = $stmt->fetchAll();

        // 5. Dolu slotları çıkar
        $availableSlots = array_filter($allSlots, function($slot) use ($bookedAppointments) {
            $slotTime = strtotime($slot);

            foreach ($bookedAppointments as $booked) {
                $bookedStart = strtotime($booked['start_time']);
                $bookedEnd = strtotime($booked['end_time']);

                // Slot çakışıyor mu?
                if ($slotTime >= $bookedStart && $slotTime < $bookedEnd) {
                    return false;
                }
            }

            return true;
        });

        // 6. Geçmiş saatleri filtrele (bugün ise)
        if ($date === date('Y-m-d')) {
            $now = time();
            $availableSlots = array_filter($availableSlots, function($slot) use ($date, $now) {
                $slotTimestamp = strtotime($date . ' ' . $slot);
                return $slotTimestamp > $now;
            });
        }

        return array_values($availableSlots);
    }

    /**
     * Diyetisyenin haftalık müsaitlik ayarlarını getir
     */
    public function getWeeklyAvailability($dietitianId)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            SELECT * FROM dietitian_availability
            WHERE dietitian_id = ?
            ORDER BY day_of_week, start_time
        ");
        $stmt->execute([$dietitianId]);
        return $stmt->fetchAll();
    }

    /**
     * Haftalık müsaitlik güncelle
     */
    public function updateWeeklyAvailability($dietitianId, array $schedule)
    {
        $conn = $this->db->getConnection();

        try {
            $conn->beginTransaction();

            // Mevcut tüm müsaitlikleri sil
            $stmt = $conn->prepare("DELETE FROM dietitian_availability WHERE dietitian_id = ?");
            $stmt->execute([$dietitianId]);

            // Yeni müsaitleri ekle
            $stmt = $conn->prepare("
                INSERT INTO dietitian_availability
                (dietitian_id, day_of_week, start_time, end_time, slot_duration, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($schedule as $item) {
                $stmt->execute([
                    $dietitianId,
                    $item['day_of_week'],
                    $item['start_time'],
                    $item['end_time'],
                    $item['slot_duration'] ?? 45,
                    $item['is_active'] ?? 1
                ]);
            }

            $conn->commit();
            return true;

        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Availability update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * İzin/tatil günü ekle
     */
    public function addException($dietitianId, $date, $reason = null)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO dietitian_availability_exceptions
            (dietitian_id, exception_date, is_available, reason)
            VALUES (?, ?, 0, ?)
            ON DUPLICATE KEY UPDATE
                is_available = 0,
                reason = VALUES(reason),
                start_time = NULL,
                end_time = NULL
        ");

        return $stmt->execute([$dietitianId, $date, $reason]);
    }

    /**
     * Özel çalışma günü ekle
     */
    public function addSpecialWorkingDay($dietitianId, $date, $startTime, $endTime)
    {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO dietitian_availability_exceptions
            (dietitian_id, exception_date, is_available, start_time, end_time)
            VALUES (?, ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE
                is_available = 1,
                start_time = VALUES(start_time),
                end_time = VALUES(end_time)
        ");

        return $stmt->execute([$dietitianId, $date, $startTime, $endTime]);
    }
}
```

**2. Appointment Service (Gelişmiş)**

Dosya: `/classes/AppointmentService.php`

```php
<?php

class AppointmentService
{
    private $db;
    private $availabilityService;
    private $notification;
    private $mail;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->availabilityService = new AvailabilityService($db);
        $this->notification = new Notification();
        $this->mail = new Mail();
    }

    /**
     * Randevu oluştur (gelişmiş versiyon)
     */
    public function createAppointment($dietitianId, $clientId, $date, $startTime, $notes = '')
    {
        $conn = $this->db->getConnection();

        // 1. Slot müsait mi kontrol et
        $availableSlots = $this->availabilityService->getAvailableSlots($dietitianId, $date);

        if (!in_array($startTime, $availableSlots)) {
            throw new Exception('Seçilen saat müsait değil veya geçersiz');
        }

        try {
            $conn->beginTransaction();

            // 2. Randevu oluştur
            $endTime = date('H:i:s', strtotime($startTime) + (45 * 60));

            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    dietitian_id, client_id, appointment_date, start_time, end_time,
                    duration, status, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, 45, 'scheduled', ?, NOW())
            ");

            $stmt->execute([
                $dietitianId,
                $clientId,
                $date,
                $startTime,
                $endTime,
                $notes
            ]);

            $appointmentId = $conn->lastInsertId();

            // 3. Bildirim gönder
            $this->notification->notifyAppointmentCreated($appointmentId);

            // 4. Email gönder
            $this->sendAppointmentEmails($appointmentId);

            // 5. Randevu hatırlatıcılarını planla
            $this->scheduleReminders($appointmentId, $date, $startTime);

            $conn->commit();

            return $appointmentId;

        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Appointment creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Randevu email'lerini gönder
     */
    private function sendAppointmentEmails($appointmentId)
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT a.*,
                       c.email as client_email, c.full_name as client_name,
                       d.email as dietitian_email, d.full_name as dietitian_name
                FROM appointments a
                JOIN users c ON a.client_id = c.id
                JOIN users d ON a.dietitian_id = d.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointmentId]);
            $appt = $stmt->fetch();

            if (!$appt) return;

            $dateTime = date('d.m.Y H:i', strtotime($appt['appointment_date'] . ' ' . $appt['start_time']));

            // Danışana email
            $clientSubject = "Randevunuz Oluşturuldu";
            $clientBody = "
                <h2>Merhaba {$appt['client_name']},</h2>
                <p>Randevunuz başarıyla oluşturuldu.</p>
                <p><strong>Diyetisyen:</strong> {$appt['dietitian_name']}</p>
                <p><strong>Tarih & Saat:</strong> {$dateTime}</p>
                <p><strong>Süre:</strong> 45 dakika</p>
                <p>Randevu saatinizde <a href='" . getenv('APP_URL') . "/client/appointments.php'>Randevularım</a> sayfasından görüşmeye katılabilirsiniz.</p>
                <p>İyi günler dileriz!</p>
            ";
            $this->mail->send($appt['client_email'], $clientSubject, $clientBody);

            // Diyetisyene email
            $dietitianSubject = "Yeni Randevu";
            $dietitianBody = "
                <h2>Merhaba Dyt. {$appt['dietitian_name']},</h2>
                <p>Yeni bir randevunuz oluşturuldu.</p>
                <p><strong>Danışan:</strong> {$appt['client_name']}</p>
                <p><strong>Tarih & Saat:</strong> {$dateTime}</p>
                <p><strong>Süre:</strong> 45 dakika</p>
                <p>Randevuyu <a href='" . getenv('APP_URL') . "/dietitian/appointments.php'>Randevularım</a> sayfasından görüntüleyebilirsiniz.</p>
            ";
            $this->mail->send($appt['dietitian_email'], $dietitianSubject, $dietitianBody);

        } catch (Exception $e) {
            error_log('Appointment email error: ' . $e->getMessage());
            // Email hatasıdır, randevu oluşturulmuştur, hata fırlatma
        }
    }

    /**
     * Randevu hatırlatıcılarını planla
     */
    private function scheduleReminders($appointmentId, $date, $startTime)
    {
        $conn = $this->db->getConnection();
        $appointmentDateTime = strtotime($date . ' ' . $startTime);

        // 24 saat önce email
        $reminder24h = date('Y-m-d H:i:s', $appointmentDateTime - (24 * 3600));

        // 1 saat önce email & SMS
        $reminder1h = date('Y-m-d H:i:s', $appointmentDateTime - (1 * 3600));

        $stmt = $conn->prepare("
            INSERT INTO appointment_reminders (appointment_id, reminder_type, scheduled_for)
            VALUES (?, ?, ?)
        ");

        // 24 saat önce email
        if (strtotime($reminder24h) > time()) {
            $stmt->execute([$appointmentId, 'email', $reminder24h]);
        }

        // 1 saat önce email
        if (strtotime($reminder1h) > time()) {
            $stmt->execute([$appointmentId, 'email', $reminder1h]);
        }

        // 1 saat önce SMS
        if (strtotime($reminder1h) > time()) {
            $stmt->execute([$appointmentId, 'sms', $reminder1h]);
        }
    }

    /**
     * Randevu iptal et
     */
    public function cancelAppointment($appointmentId, $userId, $reason = '')
    {
        $conn = $this->db->getConnection();

        // Randevu sahibi mi kontrol et
        $stmt = $conn->prepare("
            SELECT * FROM appointments
            WHERE id = ? AND (client_id = ? OR dietitian_id = ?)
        ");
        $stmt->execute([$appointmentId, $userId, $userId]);
        $appointment = $stmt->fetch();

        if (!$appointment) {
            throw new Exception('Randevu bulunamadı veya yetkiniz yok');
        }

        // Randevu geçmişte mi?
        $appointmentTime = strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
        if ($appointmentTime < time()) {
            throw new Exception('Geçmiş randevuları iptal edemezsiniz');
        }

        try {
            $conn->beginTransaction();

            // İptal et
            $stmt = $conn->prepare("
                UPDATE appointments
                SET status = 'cancelled', cancellation_reason = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $appointmentId]);

            // Bildirimleri iptal et
            $stmt = $conn->prepare("
                UPDATE appointment_reminders
                SET status = 'cancelled'
                WHERE appointment_id = ? AND status = 'pending'
            ");
            $stmt->execute([$appointmentId]);

            // Bildirim gönder
            $this->notification->notifyAppointmentCancelled($appointmentId);

            // Email gönder
            $this->sendCancellationEmails($appointmentId, $reason);

            $conn->commit();
            return true;

        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Appointment cancellation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * İptal email'lerini gönder
     */
    private function sendCancellationEmails($appointmentId, $reason)
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT a.*,
                       c.email as client_email, c.full_name as client_name,
                       d.email as dietitian_email, d.full_name as dietitian_name
                FROM appointments a
                JOIN users c ON a.client_id = c.id
                JOIN users d ON a.dietitian_id = d.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointmentId]);
            $appt = $stmt->fetch();

            if (!$appt) return;

            $dateTime = date('d.m.Y H:i', strtotime($appt['appointment_date'] . ' ' . $appt['start_time']));

            // Her iki tarafa da email gönder
            $subject = "Randevu İptal Edildi";
            $body = "
                <h2>Randevu İptal Edildi</h2>
                <p><strong>Danışan:</strong> {$appt['client_name']}</p>
                <p><strong>Diyetisyen:</strong> {$appt['dietitian_name']}</p>
                <p><strong>Tarih & Saat:</strong> {$dateTime}</p>
                <p><strong>İptal Nedeni:</strong> {$reason}</p>
            ";

            $this->mail->send($appt['client_email'], $subject, $body);
            $this->mail->send($appt['dietitian_email'], $subject, $body);

        } catch (Exception $e) {
            error_log('Cancellation email error: ' . $e->getMessage());
        }
    }
}
```

---

## RANDEVU SİSTEMİ FRONTEND

### Gelişmiş Randevu Formu

Dosya: `/public/book-appointment-v2.php`

```php
<?php
/**
 * Diyetlenio - Gelişmiş Randevu Alma Sayfası
 * Müsaitlik sistemi entegre
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Auth kontrolü
if (!$auth->check() || $auth->user()->getUserType() !== 'client') {
    setFlash('error', 'Randevu almak için danışan olarak giriş yapmalısınız.');
    redirect('/login.php');
}

$dietitianId = (int) ($_GET['dietitian_id'] ?? 0);
$conn = $db->getConnection();

// Diyetisyen bilgilerini getir
$stmt = $conn->prepare("
    SELECT u.id, u.full_name, u.profile_photo, u.email,
           dp.title, dp.consultation_fee, dp.about_me
    FROM users u
    INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
    WHERE u.id = ? AND u.user_type = 'dietitian' AND u.is_active = 1 AND dp.is_approved = 1
");
$stmt->execute([$dietitianId]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    setFlash('error', 'Diyetisyen bulunamadı.');
    redirect('/dietitians.php');
}

$errors = [];
$success = false;

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Geçersiz form gönderimi.';
    } else {
        try {
            $appointmentService = new AppointmentService($db);

            $date = $_POST['appointment_date'] ?? '';
            $time = $_POST['start_time'] ?? '';
            $notes = $_POST['notes'] ?? '';

            // Validasyon
            if (empty($date) || empty($time)) {
                throw new Exception('Tarih ve saat seçmelisiniz');
            }

            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                throw new Exception('Geçmiş tarihli randevu oluşturamazsınız');
            }

            // Randevu oluştur
            $appointmentId = $appointmentService->createAppointment(
                $dietitianId,
                $auth->id(),
                $date,
                $time,
                $notes
            );

            setFlash('success', 'Randevunuz başarıyla oluşturuldu!');
            redirect('/payment-info.php?appointment=' . $appointmentId);

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// AJAX: Seçilen tarihe göre müsait saatleri getir
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_slots') {
    header('Content-Type: application/json');

    $date = $_GET['date'] ?? '';
    if (empty($date)) {
        echo json_encode(['error' => 'Tarih gerekli']);
        exit;
    }

    $availabilityService = new AvailabilityService($db);
    $slots = $availabilityService->getAvailableSlots($dietitianId, $date);

    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'date' => $date
    ]);
    exit;
}

$pageTitle = 'Randevu Al';
include __DIR__ . '/../includes/partials/header.php';
?>
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .container { max-width: 1000px; margin-top: 50px; margin-bottom: 50px; }
        .card-custom { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

        .dietitian-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .time-slot {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            position: relative;
        }

        .time-slot:hover:not(.disabled) {
            border-color: #667eea;
            background: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .time-slot.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .time-slot.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f9fafb;
        }

        .time-slot input[type="radio"] {
            display: none;
        }

        .calendar-wrapper {
            margin-bottom: 30px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading-slots {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-slots {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-slots i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dietitian-profile.php?id=<?= $dietitian['id'] ?>" class="btn btn-outline-secondary mb-4">
            <i class="fas fa-arrow-left me-2"></i>Geri Dön
        </a>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php foreach ($errors as $error): ?>
                    <div><i class="fas fa-exclamation-circle me-2"></i><?= clean($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Dietitian Header -->
        <div class="dietitian-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <?php if ($dietitian['profile_photo']): ?>
                            <?php $p=$dietitian['profile_photo']; $photoUrl='/assets/uploads/' . ltrim($p,'/'); ?>
                            <img src="<?= clean($photoUrl) ?>" alt="" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-md fa-3x text-purple"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col">
                    <h1 class="mb-2"><?= clean($dietitian['full_name']) ?></h1>
                    <p class="mb-2 opacity-90"><?= clean($dietitian['title'] ?? 'Diyetisyen') ?></p>
                    <p class="mb-0"><strong>İlk Görüşme:</strong> Ücretsiz | <strong>Takip Görüşmeleri:</strong> <?= number_format($dietitian['consultation_fee'], 0) ?> ₺</p>
                </div>
            </div>
        </div>

        <!-- Appointment Form -->
        <div class="card-custom">
            <h2 class="mb-4"><i class="fas fa-calendar-alt me-2 text-primary"></i>Randevu Bilgileri</h2>

            <form method="POST" id="appointmentForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <!-- Tarih Seçimi -->
                <div class="calendar-wrapper">
                    <label class="form-label fw-bold fs-5 mb-3">
                        <i class="fas fa-calendar me-2 text-primary"></i>Randevu Tarihi Seçin
                    </label>
                    <input type="date"
                           name="appointment_date"
                           id="appointmentDate"
                           class="form-control form-control-lg"
                           min="<?= date('Y-m-d') ?>"
                           max="<?= date('Y-m-d', strtotime('+90 days')) ?>"
                           required>
                    <small class="text-muted">En fazla 90 gün sonrasına randevu alabilirsiniz</small>
                </div>

                <!-- Saat Seçimi -->
                <div class="mb-4">
                    <label class="form-label fw-bold fs-5 mb-3">
                        <i class="fas fa-clock me-2 text-primary"></i>Müsait Saatler
                    </label>

                    <div id="slotsContainer">
                        <div class="empty-slots">
                            <i class="fas fa-calendar-day"></i>
                            <h4>Lütfen önce tarih seçin</h4>
                            <p>Seçtiğiniz tarihe göre müsait saatler burada görünecektir</p>
                        </div>
                    </div>
                </div>

                <!-- Notlar -->
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-sticky-note me-2 text-primary"></i>Görüşmek İstediğiniz Konular (Opsiyonel)
                    </label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Özel durumlarınız, hedefleriniz, sorularınız varsa yazabilirsiniz..."><?= clean($_POST['notes'] ?? '') ?></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-calendar-check me-2"></i>Randevuyu Onayla
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dietitianId = <?= $dietitianId ?>;
        const dateInput = document.getElementById('appointmentDate');
        const slotsContainer = document.getElementById('slotsContainer');
        const submitBtn = document.getElementById('submitBtn');
        let selectedSlot = null;

        // Tarih değiştiğinde saatleri getir
        dateInput.addEventListener('change', function() {
            const date = this.value;
            if (!date) return;

            loadAvailableSlots(date);
        });

        // Müsait saatleri getir
        async function loadAvailableSlots(date) {
            slotsContainer.innerHTML = `
                <div class="loading-slots">
                    <div class="spinner"></div>
                    <p>Müsait saatler yükleniyor...</p>
                </div>
            `;

            try {
                const response = await fetch(`?ajax=get_slots&dietitian_id=${dietitianId}&date=${date}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Saatler yüklenemedi');
                }

                const slots = data.slots;

                if (slots.length === 0) {
                    slotsContainer.innerHTML = `
                        <div class="empty-slots">
                            <i class="fas fa-calendar-times"></i>
                            <h4>Bu tarihte müsait saat yok</h4>
                            <p>Lütfen başka bir tarih seçin</p>
                        </div>
                    `;
                    submitBtn.disabled = true;
                    return;
                }

                // Slot HTML'lerini oluştur
                let slotsHTML = '<div class="row g-3">';
                slots.forEach(slot => {
                    const slotTime = slot.substring(0, 5); // HH:MM formatına çevir
                    slotsHTML += `
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="time-slot" data-time="${slot}">
                                <input type="radio" name="start_time" value="${slot}" id="slot_${slot}" required>
                                <label for="slot_${slot}" class="d-block">
                                    <i class="far fa-clock me-1"></i><strong>${slotTime}</strong>
                                </label>
                            </div>
                        </div>
                    `;
                });
                slotsHTML += '</div>';

                slotsContainer.innerHTML = slotsHTML;

                // Slot click eventlerini ekle
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.addEventListener('click', function() {
                        // Önceki seçimi kaldır
                        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));

                        // Yeni seçim
                        this.classList.add('selected');
                        const radio = this.querySelector('input[type="radio"]');
                        radio.checked = true;
                        selectedSlot = radio.value;

                        // Submit butonunu aktif et
                        submitBtn.disabled = false;
                    });
                });

            } catch (error) {
                console.error('Error loading slots:', error);
                slotsContainer.innerHTML = `
                    <div class="empty-slots">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <h4>Hata</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        // Form submit
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            if (!selectedSlot) {
                e.preventDefault();
                alert('Lütfen bir saat seçin');
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Randevu oluşturuluyor...';
        });
    </script>
<?php include __DIR__ . '/../includes/partials/footer.php'; ?>
```

---

## CRON JOBS - RANDEVU HATIRLATICI SİSTEMİ

Dosya: `/cron/send-appointment-reminders.php`

```php
<?php
/**
 * CRON JOB: Randevu hatırlatıcılarını gönder
 * Her 5 dakikada bir çalışmalı
 *
 * Crontab entry:
 * */5 * * * * php /path/to/diyetlenio/cron/send-appointment-reminders.php >> /var/log/diyetlenio-reminders.log 2>&1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();
$mail = new Mail();
$now = date('Y-m-d H:i:s');

// Gönderilmemiş hatırlatıcıları getir (zamanı gelmiş olanlar)
$stmt = $conn->prepare("
    SELECT r.*, a.appointment_date, a.start_time, a.status as appointment_status,
           c.email as client_email, c.full_name as client_name, c.phone as client_phone,
           d.full_name as dietitian_name
    FROM appointment_reminders r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN users c ON a.client_id = c.id
    JOIN users d ON a.dietitian_id = d.id
    WHERE r.status = 'pending'
    AND r.scheduled_for <= ?
    AND a.status = 'scheduled'
    LIMIT 100
");
$stmt->execute([$now]);
$reminders = $stmt->fetchAll();

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($reminders) . " reminders to send\n";

foreach ($reminders as $reminder) {
    $appointmentId = $reminder['appointment_id'];
    $reminderId = $reminder['id'];
    $reminderType = $reminder['reminder_type'];

    $dateTime = date('d.m.Y H:i', strtotime($reminder['appointment_date'] . ' ' . $reminder['start_time']));
    $appointmentUrl = getenv('APP_URL') . '/client/appointments.php';

    try {
        if ($reminderType === 'email') {
            // Email gönder
            $subject = "Randevu Hatırlatma";
            $body = "
                <h2>Merhaba {$reminder['client_name']},</h2>
                <p>Yaklaşan randevunuzu hatırlatmak isteriz:</p>
                <div style='background: #f3f4f6; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                    <p><strong>Diyetisyen:</strong> {$reminder['dietitian_name']}</p>
                    <p><strong>Tarih & Saat:</strong> {$dateTime}</p>
                    <p><strong>Süre:</strong> 45 dakika</p>
                </div>
                <p>Randevu saatinizde <a href='{$appointmentUrl}' style='color: #667eea; font-weight: bold;'>buraya tıklayarak</a> görüşmeye katılabilirsiniz.</p>
                <p>İyi günler dileriz!</p>
            ";

            $mail->send($reminder['client_email'], $subject, $body);

            // Güncelle
            $updateStmt = $conn->prepare("
                UPDATE appointment_reminders
                SET status = 'sent', sent_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$reminderId]);

            echo "[" . date('Y-m-d H:i:s') . "] Email sent for appointment #{$appointmentId}\n";

        } elseif ($reminderType === 'sms' && !empty($reminder['client_phone'])) {
            // SMS gönder (Netgsm veya İletimerkezi entegrasyonu gerekli)
            // Şimdilik sadece log

            $smsText = "Diyetlenio: {$dateTime} tarihinde Dyt. {$reminder['dietitian_name']} ile randevunuz var. Katılmak için: {$appointmentUrl}";

            // TODO: SMS API entegrasyonu
            // $smsService = new SmsService();
            // $smsService->send($reminder['client_phone'], $smsText);

            echo "[" . date('Y-m-d H:i:s') . "] SMS would be sent for appointment #{$appointmentId} (not implemented)\n";

            // Şimdilik sent olarak işaretle
            $updateStmt = $conn->prepare("
                UPDATE appointment_reminders
                SET status = 'sent', sent_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$reminderId]);
        }

    } catch (Exception $e) {
        error_log("Reminder send error: " . $e->getMessage());

        // Hata olarak işaretle
        $updateStmt = $conn->prepare("
            UPDATE appointment_reminders
            SET status = 'failed', error_message = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$e->getMessage(), $reminderId]);

        echo "[" . date('Y-m-d H:i:s') . "] ERROR sending reminder #{$reminderId}: {$e->getMessage()}\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Reminder job completed\n";
```

---

## DEPLOYMENT CHECKLIST

### 1. Twilio Video Setup
- [ ] Twilio hesabı oluştur (twilio.com)
- [ ] Video ürününü aktifleştir
- [ ] API credentials'ı .env'ye ekle
- [ ] `composer require twilio/sdk` çalıştır
- [ ] TwilioVideoService class'ını ekle
- [ ] /api/video-token.php endpoint'ini oluştur
- [ ] Twilio webhook endpoint'ini yapılandır
- [ ] Frontend'i Twilio SDK ile güncelle

### 2. Database Migrations
- [ ] `020_create_dietitian_availability.sql` çalıştır
- [ ] `dietitian_availability_exceptions` tablosunu oluştur
- [ ] `appointment_reminders` tablosunu oluştur
- [ ] Mevcut diyetisyenler için default availability ekle

### 3. Backend Classes
- [ ] AvailabilityService class'ını ekle
- [ ] AppointmentService'i güncelle
- [ ] TwilioVideoService class'ını ekle

### 4. Frontend Updates
- [ ] book-appointment-v2.php'yi deploy et
- [ ] video-room.php'yi Twilio ile güncelle
- [ ] /dietitian/availability.php sayfası oluştur (diyetisyen müsaitlik ayarları için)

### 5. Cron Jobs
- [ ] send-appointment-reminders.php cron job'ı ekle
- [ ] Crontab'a ekle (her 5 dakika)
- [ ] Log dosyasını oluştur ve izinleri ayarla

### 6. Testing
- [ ] Video görüşme test et (2 farklı tarayıcıda)
- [ ] Randevu oluşturma test et
- [ ] Müsaitlik sistemi test et
- [ ] Email bildirimleri test et
- [ ] Hatırlatıcı sistemi test et

### 7. Monitoring
- [ ] Twilio usage dashboard kontrol et
- [ ] Error log monitoring ekle
- [ ] Cron job başarı/başarısızlık monitoring

---

## ESTIMATED COSTS

### Twilio Video
- İlk 10,000 dakika/ay: **Ücretsiz**
- Sonrası: $0.0015/dakika/katılımcı
- Ortalama 100 görüşme/ay (45'er dakika): **~$100-150/ay**

### Development Time
- **Phase 1 (Twilio Setup):** 1 gün
- **Phase 2 (Backend API):** 2 gün
- **Phase 3 (Frontend):** 2 gün
- **Phase 4 (Randevu sistemi):** 3 gün
- **Phase 5 (Testing & Bug Fixes):** 2 gün
- **TOPLAM:** ~10 iş günü (2 hafta)

---

## ALTERNATİF: SELF-HOSTED WEBRTC

Eğer maliyeti minimize etmek isterseniz, kendi WebRTC signaling server'ınızı kurabilirsiniz. Ancak bu yaklaşık 2 hafta ek geliştirme + sürekli bakım gerektirir.

**Gerekli Bileşenler:**
1. Node.js signaling server (Socket.IO)
2. Coturn TURN server (~$50-100/ay sunucu maliyeti)
3. Redis (state management)

**Trade-off:** Daha ucuz ama daha karmaşık, daha fazla bakım, daha az güvenilir.

---

## SONUÇve ÖNERİ

**Twilio Video API'yi kullanmanızı öneririm çünkü:**
1. ✅ Hızlı implementation (2 hafta vs 4+ hafta)
2. ✅ Yüksek kalite ve güvenilirlik
3. ✅ Düşük bakım maliyeti
4. ✅ Otomatik scaling
5. ✅ Built-in recording ve analytics
6. ✅ Mobile uyumlu

**Randevu sistemi gelişmeleri:**
1. ✅ Diyetisyen müsaitlik yönetimi
2. ✅ Dinamik saat slotları
3. ✅ Otomatik email/SMS bildirimleri
4. ✅ Randevu hatırlatıcıları
5. ✅ İptal/değiştirme sistemi

**Bu tasarım production-ready ve scalable bir çözümdür.**
