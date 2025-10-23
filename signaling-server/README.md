# Diyetlenio WebRTC Signaling Server

WebRTC video calls için signaling server. Socket.io kullanarak peer-to-peer bağlantı kurmayı sağlar.

## 🚀 Kurulum

### 1. Bağımlılıkları Yükle

```bash
cd signaling-server
npm install
```

### 2. Environment Ayarları

`.env` dosyası oluştur:

```bash
cp .env.example .env
```

`.env` dosyasını düzenle:
- `PORT`: Server portu (varsayılan: 3000)
- `ALLOWED_ORIGINS`: İzin verilen domain'ler (virgülle ayrılmış)

### 3. Server'ı Başlat

**Development:**
```bash
npm run dev
```

**Production:**
```bash
npm start
```

**PM2 ile Production:**
```bash
pm2 start server.js --name diyetlenio-signaling
pm2 save
pm2 startup
```

## 📡 API Endpoints

### Health Check
```
GET /health
```

Response:
```json
{
  "status": "ok",
  "activeRooms": 5,
  "timestamp": "2025-10-23T12:00:00.000Z"
}
```

### Room Info
```
GET /room/:roomId
```

Response:
```json
{
  "roomId": "abc123",
  "participants": ["user1", "user2"],
  "createdAt": "2025-10-23T12:00:00.000Z"
}
```

## 🔌 Socket.IO Events

### Client → Server

#### join-room
Odaya katıl
```javascript
socket.emit('join-room', {
  roomId: 'abc123',
  userId: '42',
  userName: 'Ahmet Yılmaz'
});
```

#### offer
WebRTC offer gönder
```javascript
socket.emit('offer', {
  to: 'socketId',
  offer: rtcSessionDescription,
  roomId: 'abc123'
});
```

#### answer
WebRTC answer gönder
```javascript
socket.emit('answer', {
  to: 'socketId',
  answer: rtcSessionDescription,
  roomId: 'abc123'
});
```

#### ice-candidate
ICE candidate gönder
```javascript
socket.emit('ice-candidate', {
  to: 'socketId',
  candidate: iceCandidate,
  roomId: 'abc123'
});
```

#### leave-room
Odadan ayrıl
```javascript
socket.emit('leave-room', { roomId: 'abc123' });
```

### Server → Client

#### existing-participants
Mevcut katılımcılar (odaya katılınca)
```javascript
socket.on('existing-participants', (participants) => {
  // participants: [{ socketId, userId, userName, joinedAt }]
});
```

#### user-joined
Yeni kullanıcı katıldı
```javascript
socket.on('user-joined', ({ socketId, userId, userName }) => {
  // Yeni peer connection oluştur
});
```

#### user-left
Kullanıcı ayrıldı
```javascript
socket.on('user-left', ({ socketId, userId, userName }) => {
  // Peer connection'ı kapat
});
```

#### offer
WebRTC offer alındı
```javascript
socket.on('offer', ({ from, offer, roomId }) => {
  // Answer oluştur ve gönder
});
```

#### answer
WebRTC answer alındı
```javascript
socket.on('answer', ({ from, answer, roomId }) => {
  // Remote description ayarla
});
```

#### ice-candidate
ICE candidate alındı
```javascript
socket.on('ice-candidate', ({ from, candidate, roomId }) => {
  // ICE candidate ekle
});
```

## 🔧 Railway Deployment

### 1. Railway CLI Kur
```bash
npm i -g @railway/cli
railway login
```

### 2. Proje Oluştur
```bash
cd signaling-server
railway init
```

### 3. Environment Variables Ayarla
Railway Dashboard'dan:
- `PORT`: 3000
- `NODE_ENV`: production
- `ALLOWED_ORIGINS`: https://www.diyetlenio.com

### 4. Deploy
```bash
railway up
```

### 5. Domain Ayarla
Railway Dashboard → Settings → Networking → Generate Domain

## 📊 Monitoring

### Logs (PM2)
```bash
pm2 logs diyetlenio-signaling
```

### Aktif Odalar
```bash
curl http://localhost:3000/health
```

## 🐛 Troubleshooting

### CORS Hatası
`.env` dosyasında `ALLOWED_ORIGINS` kontrolü yap.

### Connection Failed
- Firewall kurallarını kontrol et
- PORT değişkenini kontrol et
- HTTPS kullanıyorsan signaling server de HTTPS olmalı

### ICE Connection Failed
- STUN/TURN server konfigürasyonunu kontrol et
- Network kısıtlamalarını kontrol et (corporate firewall)

## 📝 License

MIT License - Diyetlenio Project
