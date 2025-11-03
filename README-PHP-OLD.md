# 🥗 DİYETLENIO

Diyetisyenler ve danışanları bir araya getiren, video görüşme özellikli web platformu.

## 🚀 Özellikler

- ✅ Video görüşme (WebRTC)
- ✅ Randevu sistemi
- ✅ Danışan takip sistemi
- ✅ Blog ve makale yönetimi
- ✅ Yemek tarifleri
- ✅ Acil nöbetçi sistem
- ✅ CMS (İçerik yönetimi)

## 🛠️ Teknolojiler

- **Backend:** PHP 8.3+
- **Database:** MySQL 8.0
- **Frontend:** HTML5, CSS3, JavaScript
- **Video:** WebRTC
- **CSS Framework:** Bootstrap 5

## 📦 Kurulum
```bash
# Veritabanını oluştur
mysql -u root -p < database.sql

# (Opsiyonel) Composer bağımlılıkları
# Not: Projede zorunlu composer.json bulunmamaktadır.
# SMTP gibi gelişmiş mail kullanımı eklenecekse composer yapılandırılabilir.
# composer install

# Yapılandırma
cp .env.example .env
# .env dosyasını düzenle

# Geliştirme sunucusunu başlat
php -S localhost:8000 -t public
```

Notlar:
- Deploy ortamı PHP 8.3 ile hizalanmıştır (Nixpacks yapılandırması güncellendi).
- WebRTC signaling server için güncel dizin: `signaling-server/` (Node.js).
- Migration dosyaları (`public/run-migration-015.php`, `public/run-migration-019.php`) sadece kontrollü kurulumda kullanılmalı ve çalıştırma sonrası silinmelidir. Production’da admin girişi ve .env’de `MIGRATION_TOKEN` zorunludur.

## 📧 İletişim

- Website: https://diyetlenio.com
- Email: info@diyetlenio.com

## 📄 Lisans

Copyright © 2025 Diyetlenio. Tüm hakları saklıdır.
