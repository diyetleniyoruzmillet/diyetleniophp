# 🎥 WebRTC Video Call Deployment Guide

Bu döküman, Diyetlenio WebRTC video call sistemini production'a deploy etme adımlarını içerir.

## 📋 İçindekiler

1. [Database Migration](#1-database-migration)
2. [Signaling Server Deployment](#2-signaling-server-deployment)
3. [Frontend Integration](#3-frontend-integration)
4. [Testing](#4-testing)
5. [Monitoring](#5-monitoring)

---

## 1. Database Migration

### Railway'de Migration Çalıştırma

#### Yöntem 1: Railway Query Tab
1. Railway Dashboard → MySQL Database → Query
2. `database/migrations/019_create_video_sessions.sql` dosyasının içeriğini kopyala
3. Query tab'a yapıştır ve çalıştır

#### Yöntem 2: MySQL Client
```bash
mysql -h nozomi.proxy.rlwy.net -P 12434 \
  -u root -pHrpWATAjzmJhHeUuUWuItKmmwvtVXGZf \
  railway < database/migrations/019_create_video_sessions.sql
```

### Migration'ı Doğrula
```sql
SHOW TABLES LIKE 'video%';
-- Beklenen: video_sessions, video_session_events

DESCRIBE video_sessions;
DESCRIBE video_session_events;

-- appointments tablosuna eklenen kolonları kontrol et
SHOW COLUMNS FROM appointments LIKE 'video%';
```

---

## 2. Signaling Server Deployment

### Seçenek A: Railway'de Deploy (ÖNERİLEN)

#### 1. Railway Projesi Oluştur
```bash
cd signaling-server
railway init
```

#### 2. Environment Variables Ayarla
Railway Dashboard → Settings → Variables:

```env
PORT=3000
NODE_ENV=production
ALLOWED_ORIGINS=https://www.diyetlenio.com,https://diyetlenio.com
```

#### 3. Deploy
```bash
railway up
```

#### 4. Domain Ayarla
1. Railway Dashboard → Settings → Networking
2. "Generate Domain" butonuna tıkla
3. Domain'i al (örn: `diyetlenio-signaling.up.railway.app`)
4. Bu domain'i not et - frontend'de kullanacaksın

#### 5. Doğrula
```bash
curl https://diyetlenio-signaling.up.railway.app/health
```

Beklenen response:
```json
{
  "status": "ok",
  "activeRooms": 0,
  "timestamp": "2025-10-23T12:00:00.000Z"
}
```

---

### Seçenek B: VPS'de Deploy (PM2 ile)

#### 1. Server'a Bağlan
```bash
ssh user@your-server.com
```

#### 2. Node.js Kur (Ubuntu/Debian)
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
node --version  # v18.x.x olmalı
```

#### 3. PM2 Kur
```bash
sudo npm install -g pm2
```

#### 4. Projeyi Yükle
```bash
cd /var/www
git clone https://github.com/diyetleniyoruzmillet/diyetleniophp.git
cd diyetleniophp/signaling-server
npm install
```

#### 5. Environment Ayarla
```bash
cp .env.example .env
nano .env
```

Düzenle:
```env
PORT=3000
NODE_ENV=production
ALLOWED_ORIGINS=https://www.diyetlenio.com
```

#### 6. PM2 ile Başlat
```bash
pm2 start server.js --name diyetlenio-signaling
pm2 save
pm2 startup
```

#### 7. Nginx Reverse Proxy (Opsiyonel ama önerilen)
```nginx
# /etc/nginx/sites-available/signaling.diyetlenio.com
server {
    listen 80;
    server_name signaling.diyetlenio.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/signaling.diyetlenio.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# SSL ile Certbot
sudo certbot --nginx -d signaling.diyetlenio.com
```

#### 8. Firewall Ayarları
```bash
sudo ufw allow 3000/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload
```

---

## 3. Frontend Integration

### 1. Signaling Server URL'ini Ayarla

`config/config.php` dosyasına ekle:
```php
// WebRTC Configuration
define('SIGNALING_SERVER_URL', getenv('SIGNALING_SERVER_URL') ?: 'https://diyetlenio-signaling.up.railway.app');
```

Railway'de environment variable ekle:
```env
SIGNALING_SERVER_URL=https://diyetlenio-signaling.up.railway.app
```

### 2. video-room.php'yi Güncelle

Script tag'lerini ekle:
```php
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="/assets/js/webrtc-client.js"></script>
<script>
    const signalingServerUrl = '<?= SIGNALING_SERVER_URL ?>';
    const webrtcClient = new WebRTCClient({
        roomId: '<?= $roomId ?>',
        userId: '<?= $user->getId() ?>',
        userName: '<?= clean($user->getFullName()) ?>',
        signalingServerUrl: signalingServerUrl
    });

    // Callbacks ayarla
    webrtcClient.onLocalStream = (stream) => {
        document.getElementById('localVideo').srcObject = stream;
    };

    webrtcClient.onRemoteStream = (stream, socketId) => {
        document.getElementById('remoteVideo').srcObject = stream;
        document.getElementById('waitingScreen').classList.add('hidden');
    };

    webrtcClient.onConnectionStateChange = (state, socketId) => {
        updateConnectionStatus(state);
    };

    webrtcClient.onError = (error) => {
        console.error('WebRTC Error:', error);
        alert(error.message || 'Video call hatası oluştu');
    };

    // Initialize
    webrtcClient.init().catch(error => {
        console.error('Failed to initialize WebRTC:', error);
        alert('Video görüşme başlatılamadı. Lütfen kamera ve mikrofon izinlerini kontrol edin.');
    });

    // Mute button
    document.getElementById('muteBtn').addEventListener('click', () => {
        const isMuted = webrtcClient.toggleMute();
        const btn = document.getElementById('muteBtn');
        btn.classList.toggle('active', isMuted);
        btn.querySelector('i').className = isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
    });

    // Video button
    document.getElementById('videoBtn').addEventListener('click', () => {
        const isOff = webrtcClient.toggleVideo();
        const btn = document.getElementById('videoBtn');
        btn.classList.toggle('active', isOff);
        btn.querySelector('i').className = isOff ? 'fas fa-video-slash' : 'fas fa-video';
    });

    // End call
    document.getElementById('endBtn').addEventListener('click', () => {
        if (confirm('Görüşmeyi sonlandırmak istediğinizden emin misiniz?')) {
            webrtcClient.leave();
            window.location.href = '/<?= $user->getUserType() ?>/appointments.php';
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        webrtcClient.leave();
    });
</script>
```

### 3. Appointment Sistemine Entegrasyon

Randevu detay sayfasına "Video Görüşmeye Başla" butonu ekle:

```php
<?php
// Randevu tipi online ise ve zamanı geldiyse
if ($appointment['type'] === 'online' &&
    strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']) <= time()) {

    // Room ID oluştur veya çek
    $roomId = $appointment['video_room_url'] ?? generateRoomId($appointment['id']);

    // Database'e kaydet
    if (!$appointment['video_room_url']) {
        $stmt = $conn->prepare("UPDATE appointments SET video_room_url = ? WHERE id = ?");
        $stmt->execute([$roomId, $appointment['id']]);
    }
?>
    <a href="/video-room.php?appointment_id=<?= $appointment['id'] ?>&room_id=<?= $roomId ?>"
       class="btn btn-primary">
        <i class="fas fa-video me-2"></i>Video Görüşmeye Başla
    </a>
<?php } ?>
```

Helper function:
```php
function generateRoomId($appointmentId) {
    return 'room_' . $appointmentId . '_' . bin2hex(random_bytes(8));
}
```

---

## 4. Testing

### Local Test (Development)

#### 1. Signaling Server'ı Başlat
```bash
cd signaling-server
npm install
npm run dev
```

#### 2. PHP Server'ı Başlat
```bash
php -S localhost:8000 -t public
```

#### 3. İki Tarayıcı Penceresi Aç
- Pencere 1: Diyetisyen olarak giriş yap
- Pencere 2: Danışan olarak giriş yap

#### 4. Video Call Başlat
- Bir randevu oluştur (online type)
- Her iki kullanıcıda da "Video Görüşmeye Başla" butonuna tıkla
- Video akışının başladığını doğrula

### Production Test

#### 1. Signaling Server Health Check
```bash
curl https://diyetlenio-signaling.up.railway.app/health
```

#### 2. WebSocket Connection Test
Chrome DevTools → Network → WS (WebSocket)
- Bağlantının kurulduğunu görmelisin
- `join-room`, `offer`, `answer` event'lerini göreceksin

#### 3. End-to-End Test
1. Gerçek bir randevu oluştur
2. İki farklı device'dan giriş yap (telefon + bilgisayar)
3. Video call başlat
4. Ses ve video kalitesini test et
5. Mute/unmute, video on/off test et

---

## 5. Monitoring

### PM2 Monitoring (VPS)
```bash
pm2 monit
pm2 logs diyetlenio-signaling
pm2 status
```

### Railway Monitoring
1. Railway Dashboard → Deployments → View Logs
2. Health check endpoint'i periyodik kontrol et:
   ```bash
   watch -n 30 'curl -s https://diyetlenio-signaling.up.railway.app/health | jq'
   ```

### Database Monitoring
```sql
-- Aktif session'ları gör
SELECT * FROM video_sessions
WHERE session_status = 'active'
ORDER BY started_at DESC;

-- Bugünkü toplam görüşme sayısı
SELECT COUNT(*) as total_calls_today
FROM video_sessions
WHERE DATE(created_at) = CURDATE();

-- Ortalama görüşme süresi
SELECT AVG(duration_minutes) as avg_duration
FROM video_sessions
WHERE session_status = 'ended';
```

---

## 🐛 Troubleshooting

### Problem: "Failed to connect to signaling server"

**Çözüm:**
1. Signaling server çalışıyor mu kontrol et:
   ```bash
   curl https://diyetlenio-signaling.up.railway.app/health
   ```
2. CORS ayarlarını kontrol et (`.env` → `ALLOWED_ORIGINS`)
3. Railway logs'u incele: `railway logs`

### Problem: "Connection failed" / "ICE connection failed"

**Çözüm:**
1. STUN server'lar erişilebilir mi kontrol et
2. Firewall/corporate network kısıtlaması olabilir
3. TURN server ekle (NAT traversal için)

### Problem: "Media devices not found"

**Çözüm:**
1. HTTPS kullan (HTTP'de camera/mic izni verilmez)
2. Tarayıcı izinlerini kontrol et
3. Device'ın kamera/mikrofonu var mı kontrol et

### Problem: Ses duyuluyor ama video gözükmüyor

**Çözüm:**
1. Video track enabled mi kontrol et: `stream.getVideoTracks()[0].enabled`
2. Video element'in `srcObject` set edilmiş mi kontrol et
3. CSS `display: none` veya `visibility: hidden` olmasın

---

## 📊 Success Metrics

Deployment başarılı sayılır when:
- ✅ Database migration sorunsuz tamamlandı
- ✅ Signaling server health check OK dönüyor
- ✅ İki kullanıcı video call başlatabilir
- ✅ Ses ve video kalitesi iyi
- ✅ Mute/unmute, video on/off çalışıyor
- ✅ Connection stable (5 dakika+ test)
- ✅ Mobile device'lardan da çalışıyor

---

## 🎯 Next Steps

1. **TURN Server Ekle** (NAT traversal için):
   - coturn kur
   - credentials oluştur
   - webrtc-client.js'te ice servers'a ekle

2. **Recording Feature** (Opsiyonel):
   - MediaRecorder API kullan
   - Kayıtları S3/R2'ye yükle
   - Database'e kayıt linki sakla

3. **Analytics** (Opsiyonel):
   - Call duration tracking
   - Quality metrics (packet loss, jitter)
   - User satisfaction survey

---

**Deployment Date:** 2025-10-23
**Version:** 1.0.0
**Maintainer:** Diyetlenio Team
