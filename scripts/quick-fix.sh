#!/bin/bash

echo "======================================"
echo "DİYETLENIO - HIZLI VERİTABANI ONARIMI"
echo "======================================"
echo ""

# Renk kodları
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. MySQL çalışıyor mu kontrol et
echo "1. MySQL servisi kontrol ediliyor..."
if systemctl is-active --quiet mysql; then
    echo -e "${GREEN}✓${NC} MySQL çalışıyor"
else
    echo -e "${RED}✗${NC} MySQL çalışmıyor! Başlatılıyor..."
    sudo systemctl start mysql
fi
echo ""

# 2. Veritabanı ve kullanıcı oluştur
echo "2. Veritabanı ve kullanıcı oluşturuluyor..."
sudo mysql <<MYSQL_SCRIPT
-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS diyetlenio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Kullanıcıyı oluştur (varsa önce sil)
DROP USER IF EXISTS 'diyetlenio_user'@'localhost';
CREATE USER 'diyetlenio_user'@'localhost' IDENTIFIED BY 'diyetlenio2025';

-- Yetkileri ver
GRANT ALL PRIVILEGES ON diyetlenio.* TO 'diyetlenio_user'@'localhost';

-- Yenile
FLUSH PRIVILEGES;

-- Göster
SELECT 'Veritabanı oluşturuldu:' as '';
SHOW DATABASES LIKE 'diyetlenio';

SELECT 'Kullanıcı oluşturuldu:' as '';
SELECT user, host FROM mysql.user WHERE user = 'diyetlenio_user';
MYSQL_SCRIPT

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Veritabanı ve kullanıcı oluşturuldu"
else
    echo -e "${RED}✗${NC} Veritabanı oluşturulamadı!"
    exit 1
fi
echo ""

# 3. .env dosyasını güncelle
echo "3. .env dosyası güncelleniyor..."
if [ -f .env ]; then
    # Backup al
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

    # Değişiklikleri yap
    sed -i 's/^DB_DATABASE=.*/DB_DATABASE=diyetlenio/' .env
    sed -i 's/^DB_USERNAME=.*/DB_USERNAME=diyetlenio_user/' .env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=diyetlenio2025/' .env

    echo -e "${GREEN}✓${NC} .env dosyası güncellendi"
else
    echo -e "${YELLOW}⚠${NC}  .env dosyası bulunamadı"
fi
echo ""

# 4. Veritabanı şemasını import et
echo "4. Veritabanı şeması import ediliyor..."
if [ -f database.sql ]; then
    mysql -u diyetlenio_user -p'diyetlenio2025' diyetlenio < database.sql
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Veritabanı şeması import edildi"
    else
        echo -e "${RED}✗${NC} Şema import edilemedi!"
    fi
else
    echo -e "${YELLOW}⚠${NC}  database.sql dosyası bulunamadı"
fi
echo ""

# 5. Admin kullanıcısını ekle/güncelle
echo "5. Admin kullanıcısı ekleniyor..."
if [ -f scripts/create-admin.sql ]; then
    mysql -u diyetlenio_user -p'diyetlenio2025' diyetlenio < scripts/create-admin.sql
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Admin kullanıcısı eklendi"
    else
        echo -e "${RED}✗${NC} Admin eklenemedi!"
    fi
fi
echo ""

# 6. Test yap
echo "6. Bağlantı test ediliyor..."
php scripts/test-admin-login.php
echo ""

# 7. Sonuç
echo "======================================"
echo -e "${GREEN}KURULUM TAMAMLANDI!${NC}"
echo "======================================"
echo ""
echo "🔑 GİRİŞ BİLGİLERİ:"
echo "---"
echo "URL:   http://localhost:8000/login.php"
echo "Email: admin@diyetlenio.com"
echo "Şifre: Admin123!"
echo ""
echo "📝 VERİTABANI BİLGİLERİ:"
echo "---"
echo "Veritabanı: diyetlenio"
echo "Kullanıcı:  diyetlenio_user"
echo "Şifre:      diyetlenio2025"
echo ""
