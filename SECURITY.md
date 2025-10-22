# 🔐 Güvenlik Dokümantasyonu - Diyetlenio

## Kritik Güvenlik Uyarıları

### 1. Ortam Değişkenleri (.env)

**ÇOK ÖNEMLİ:** `.env` dosyasını ASLA git'e commit ETMEYİN!

```bash
# İlk kurulum:
cp .env.example .env
nano .env  # Tüm şifreleri değiştirin
```

**Değiştirilmesi gereken kritik şifreler:**
- `DB_PASSWORD` - Veritabanı şifresi (min 16 karakter, karışık)
- `MAIL_PASSWORD` - Email SMTP şifresi
- `APP_KEY` - Uygulama encryption key

**Şifre oluşturma örneği:**
```bash
# Güçlü rastgele şifre oluşturma:
openssl rand -base64 32
```

### 2. Production Checklist

Canlıya almadan önce **MUTLAKA** kontrol edin:

- [ ] `.env` dosyası git'e commit edilmemiş
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `DEBUG_MODE=false`
- [ ] `SESSION_SECURE=true` (HTTPS gerekli)
- [ ] Tüm varsayılan şifreler değiştirilmiş
- [ ] Veritabanı kullanıcısı sadece gerekli izinlere sahip
- [ ] Dosya yükleme klasörü (`assets/uploads/`) yazma izinli
- [ ] Log klasörü (`logs/`) yazma izinli
- [ ] Session klasörü (`storage/sessions/`) sadece root erişebilir (0700)

### 3. Veritabanı Güvenliği

```sql
-- Production için özel DB kullanıcısı oluşturun:
CREATE USER 'diyetlenio_prod'@'localhost' IDENTIFIED BY 'SUPER_STRONG_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON diyetlenio_db.* TO 'diyetlenio_prod'@'localhost';
FLUSH PRIVILEGES;

-- DROP, CREATE TABLE gibi tehlikeli yetkileri vermeyin!
```

### 4. Dosya İzinleri

```bash
# Önerilen izinler:
chmod 755 public/
chmod 644 public/*.php
chmod 700 storage/sessions/
chmod 755 storage/cache/
chmod 755 assets/uploads/
chmod 600 .env
chmod 644 .env.example
```

### 5. HTTPS Zorunluluğu

Production'da HTTPS kullanmak **zorunludur**!

**Apache .htaccess örneği:**
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Nginx örneği:**
```nginx
server {
    listen 80;
    server_name diyetlenio.com www.diyetlenio.com;
    return 301 https://$server_name$request_uri;
}
```

### 6. Güvenlik Headers

Sunucu yapılandırmanıza ekleyin:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

### 7. Yedekleme Stratejisi

```bash
# Günlük otomatik yedek (crontab):
0 2 * * * /usr/bin/mysqldump -u backup_user -p'PASSWORD' diyetlenio_db | gzip > /backup/db_$(date +\%Y\%m\%d).sql.gz

# Son 30 günü sakla:
find /backup/ -name "db_*.sql.gz" -mtime +30 -delete
```

### 8. Log İzleme

```bash
# Hata loglarını düzenli kontrol edin:
tail -f logs/error.log

# Şüpheli aktivite araştırması:
grep -i "sql injection\|xss\|csrf" logs/error.log
grep -i "failed login" logs/activity.log
```

### 9. Güvenlik Güncellemeleri

- PHP'yi düzenli güncelleyin (minimum 8.1+)
- MySQL/MariaDB'yi güncelleyin
- Composer bağımlılıklarını güncelleyin:
  ```bash
  composer update
  ```

### 10. İzleme ve Alarm

**Önerilen araçlar:**
- **Sentry.io** - Hata izleme
- **Fail2Ban** - Brute force koruması
- **ModSecurity** - Web application firewall
- **Cloudflare** - DDoS koruması

### 11. Acil Durum İletişim

Güvenlik açığı tespit ederseniz:
- **Email:** security@diyetlenio.com
- Hassas bilgileri public issue tracker'da PAYLAŞMAYIN
- Responsable disclosure ilkelerine uyun

### 12. Şifre Politikası

Kullanıcı şifreleri için:
- Minimum 8 karakter
- En az 1 büyük harf
- En az 1 küçük harf  
- En az 1 rakam
- En az 1 özel karakter

Admin şifreleri için:
- Minimum 12 karakter
- 2FA zorunlu (gelecek versiyonda)

---

## Son Güncelleme

Bu doküman son güncellenme: 2025-10-22

Sorularınız için: dev@diyetlenio.com
