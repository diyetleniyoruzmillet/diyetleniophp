# Diyetlenio - Node.js + TypeScript Version

Modern diyetisyen randevu ve danışmanlık platformu. PHP'den Node.js + TypeScript + Prisma + Express.js'e migration.

## 🚀 Teknoloji Stack

### Backend
- **Runtime**: Node.js 18+
- **Language**: TypeScript
- **Framework**: Express.js
- **ORM**: Prisma
- **Database**: MySQL/MariaDB
- **Authentication**: JWT + Passport.js
- **Real-time**: Socket.IO (WebRTC Signaling)
- **Email**: Nodemailer
- **File Upload**: Multer
- **Validation**: Joi
- **Logging**: Winston

### Features
- ✅ Kullanıcı yönetimi (Admin, Diyetisyen, Müşteri)
- ✅ JWT tabanlı authentication
- ✅ Randevu sistemi
- ✅ Mesajlaşma sistemi
- ✅ WebRTC video görüşme (signaling server)
- ✅ Blog/Makale sistemi
- ✅ Tarif sistemi
- ✅ Ödeme sistemi
- ✅ Email servisi
- ✅ File upload servisi
- ✅ Rate limiting
- ✅ Error handling
- ✅ Logging

## 📁 Proje Yapısı

```
diyetlenio/
├── _legacy_php/          # Eski PHP dosyaları (yedek)
├── src/
│   ├── config/           # Konfigürasyon dosyaları
│   │   ├── index.ts
│   │   └── database.ts
│   ├── controllers/      # Route controller'ları
│   │   └── auth/
│   ├── middlewares/      # Express middleware'ler
│   │   ├── auth.middleware.ts
│   │   ├── error-handler.ts
│   │   └── not-found-handler.ts
│   ├── routes/           # API routes
│   │   ├── auth.routes.ts
│   │   ├── user.routes.ts
│   │   ├── appointment.routes.ts
│   │   ├── message.routes.ts
│   │   ├── article.routes.ts
│   │   ├── recipe.routes.ts
│   │   └── payment.routes.ts
│   ├── services/         # Business logic
│   │   ├── auth.service.ts
│   │   ├── email.service.ts
│   │   └── file-upload.service.ts
│   ├── utils/            # Utility fonksiyonlar
│   │   ├── logger.ts
│   │   └── errors.ts
│   ├── types/            # TypeScript type definitions
│   ├── server.ts         # Express server
│   └── signaling-server.ts  # WebRTC signaling
├── prisma/
│   └── schema.prisma     # Prisma schema
├── database/
│   └── migrations/       # SQL migrations (referans)
├── public/               # Static files
├── assets/
│   └── uploads/          # Uploaded files
├── logs/                 # Application logs
├── package.json
├── tsconfig.json
└── .env.example

```

## 🛠️ Kurulum

### 1. Gereksinimler
- Node.js 18 veya üzeri
- npm 9 veya üzeri
- MySQL/MariaDB

### 2. Projeyi Klonlayın
```bash
git clone <repo-url>
cd diyetlenio
```

### 3. Bağımlılıkları Yükleyin
```bash
npm install
```

### 4. Environment Variables
`.env.example` dosyasını `.env` olarak kopyalayın ve düzenleyin:

```bash
cp .env.example .env
```

**Önemli:** Aşağıdaki değişkenleri mutlaka ayarlayın:
- `DATABASE_URL` - Veritabanı bağlantı URL'i
- `JWT_SECRET` - JWT secret key (32 karakter minimum)
- `JWT_REFRESH_SECRET` - JWT refresh secret key
- `SESSION_SECRET` - Session secret key
- `APP_KEY` - Application encryption key
- `MAIL_*` - Email SMTP ayarları

### 5. Prisma Setup
```bash
# Prisma client'ı generate edin
npm run prisma:generate

# Veritabanı migration'larını çalıştırın
npm run prisma:migrate

# (Opsiyonel) Prisma Studio'yu açın
npm run prisma:studio
```

### 6. Geliştirme Sunucusunu Başlatın
```bash
npm run dev
```

Server `http://localhost:3000` adresinde çalışacaktır.

## 📝 API Endpoints

### Authentication
```
POST   /api/auth/register          - Kullanıcı kaydı
POST   /api/auth/login             - Giriş yap
GET    /api/auth/verify-email/:token - Email doğrulama
POST   /api/auth/forgot-password   - Şifre sıfırlama isteği
POST   /api/auth/reset-password/:token - Şifre sıfırlama
POST   /api/auth/change-password   - Şifre değiştirme (authenticated)
POST   /api/auth/refresh-token     - Token yenileme
POST   /api/auth/logout            - Çıkış (authenticated)
GET    /api/auth/me                - Profil bilgisi (authenticated)
```

### Users
```
GET    /api/users/profile          - Profil görüntüleme
PUT    /api/users/profile          - Profil güncelleme
GET    /api/users/all              - Tüm kullanıcılar (admin only)
```

### Appointments
```
GET    /api/appointments           - Randevuları listele
POST   /api/appointments           - Randevu oluştur
GET    /api/appointments/:id       - Randevu detayı
PUT    /api/appointments/:id       - Randevu güncelle
DELETE /api/appointments/:id       - Randevu iptal
```

### Messages
```
GET    /api/messages               - Mesajları listele
POST   /api/messages               - Mesaj gönder
GET    /api/messages/:id           - Mesaj detayı
PUT    /api/messages/:id/read      - Okundu olarak işaretle
```

### Articles
```
GET    /api/articles               - Makaleleri listele
GET    /api/articles/:slug         - Makale detayı
POST   /api/articles               - Makale oluştur (dietitian/admin)
PUT    /api/articles/:id           - Makale güncelle
DELETE /api/articles/:id           - Makale sil
```

### Recipes
```
GET    /api/recipes                - Tarifleri listele
GET    /api/recipes/:slug          - Tarif detayı
POST   /api/recipes                - Tarif oluştur (dietitian/admin)
PUT    /api/recipes/:id            - Tarif güncelle
DELETE /api/recipes/:id            - Tarif sil
```

### Payments
```
GET    /api/payments               - Ödemeleri listele
POST   /api/payments/create        - Ödeme oluştur
POST   /api/payments/webhook       - Payment webhook
GET    /api/payments/commissions   - Komisyonlar (admin only)
```

## 🎥 WebRTC Video Görüşme

Socket.IO ile real-time video görüşme için signaling server:

```javascript
// Client tarafında
import io from 'socket.io-client';

const socket = io('http://localhost:3000');

// Odaya katıl
socket.emit('join-room', {
  roomId: 'room-123',
  userId: 1,
  userName: 'John Doe',
  userType: 'client'
});

// WebRTC offer gönder
socket.emit('offer', {
  to: 'target-socket-id',
  offer: rtcOffer
});

// Events
socket.on('user-joined', (data) => { /* ... */ });
socket.on('offer', (data) => { /* ... */ });
socket.on('answer', (data) => { /* ... */ });
socket.on('ice-candidate', (data) => { /* ... */ });
```

## 🧪 Testing

```bash
npm test
```

## 📦 Production Build

```bash
# Build
npm run build

# Start
npm start
```

## 🔧 Scripts

```bash
npm run dev              # Development server (nodemon + ts-node)
npm run build            # TypeScript build
npm start                # Production server
npm run prisma:generate  # Generate Prisma client
npm run prisma:migrate   # Run migrations
npm run prisma:studio    # Open Prisma Studio
npm run lint             # ESLint
npm run format           # Prettier
```

## 🗂️ Database Schema

Prisma schema `prisma/schema.prisma` dosyasında tanımlıdır. Ana modeller:

- **User** - Kullanıcılar (admin, dietitian, client)
- **DietitianProfile** - Diyetisyen profili
- **ClientHealthInfo** - Müşteri sağlık bilgileri
- **Appointment** - Randevular
- **Message** - Mesajlar
- **DietPlan** - Diyet planları
- **Article** - Blog makaleleri
- **Recipe** - Tarifler
- **Payment** - Ödemeler
- **Notification** - Bildirimler
- **Review** - Değerlendirmeler

## 📧 Email Templates

Email template'leri `src/templates/emails/` klasöründe Handlebars formatında:

- `email-verification.hbs` - Email doğrulama
- `password-reset.hbs` - Şifre sıfırlama
- `appointment-confirmation.hbs` - Randevu onayı
- `appointment-reminder.hbs` - Randevu hatırlatması

## 🔐 Güvenlik

- ✅ JWT token authentication
- ✅ Password hashing (bcrypt)
- ✅ CORS protection
- ✅ Helmet.js security headers
- ✅ Rate limiting
- ✅ Input validation
- ✅ SQL injection protection (Prisma)
- ✅ XSS protection

## 📊 Logging

Winston ile loglama:
- Development: Console output (colorized)
- Production: File logging (`logs/app.log`, `logs/app-error.log`)

## 🐛 Debugging

```bash
# Development mode with debug logs
NODE_ENV=development LOG_LEVEL=debug npm run dev
```

## 🚀 Deployment

### Railway / Render / Heroku

1. Environment variables'ları ayarlayın
2. `DATABASE_URL` Prisma formatında olmalı
3. Build command: `npm run build && npm run prisma:generate`
4. Start command: `npm start`

### Docker (Opsiyonel)

```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build
RUN npm run prisma:generate
EXPOSE 3000
CMD ["npm", "start"]
```

## 🆕 PHP'den Migration

Eski PHP dosyaları `_legacy_php/` klasöründe yedeklenmiştir.

### Önemli Değişiklikler:
1. **ORM**: Native MySQL → Prisma
2. **Auth**: PHP Sessions → JWT
3. **File Handling**: PHP functions → Multer
4. **Email**: PHP Mail → Nodemailer
5. **Real-time**: PHP polling → Socket.IO

## 🤝 Contributing

1. Branch oluşturun: `git checkout -b feature/amazing-feature`
2. Commit edin: `git commit -m 'Add amazing feature'`
3. Push edin: `git push origin feature/amazing-feature`
4. Pull Request açın

## 📄 License

MIT License

## 👥 Team

Diyetlenio Development Team

---

**Not**: Bu proje PHP'den Node.js/TypeScript'e migration edilmiş halidir. Eski PHP kodları `_legacy_php/` klasöründe bulunmaktadır.
