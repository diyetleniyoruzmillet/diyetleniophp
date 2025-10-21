#!/bin/bash

# Diyetlenio - Veritabanı Kurulum Script'i

echo "======================================"
echo "Diyetlenio Veritabanı Kurulumu"
echo "======================================"
echo ""

# Renk kodları
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# MySQL root şifresini sor
echo -e "${YELLOW}MySQL root şifrenizi girin:${NC}"
read -s MYSQL_ROOT_PASS

# Veritabanını oluştur
echo ""
echo -e "${YELLOW}[1/4] Veritabanı oluşturuluyor...${NC}"
sudo mysql -u root -p"${MYSQL_ROOT_PASS}" << EOF
CREATE DATABASE IF NOT EXISTS diyetlenio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Veritabanı oluşturuldu${NC}"
else
    echo -e "${RED}✗ Veritabanı oluşturulamadı${NC}"
    exit 1
fi

# Kullanıcı oluştur
echo -e "${YELLOW}[2/4] Kullanıcı oluşturuluyor...${NC}"
sudo mysql -u root -p"${MYSQL_ROOT_PASS}" << EOF
CREATE USER IF NOT EXISTS 'diyetlenio_user'@'localhost' IDENTIFIED BY 'diyetlenio2025';
GRANT ALL PRIVILEGES ON diyetlenio.* TO 'diyetlenio_user'@'localhost';
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Kullanıcı oluşturuldu ve yetkiler verildi${NC}"
else
    echo -e "${RED}✗ Kullanıcı oluşturulamadı${NC}"
    exit 1
fi

# Database.sql dosyasını import et
echo -e "${YELLOW}[3/4] Tablolar oluşturuluyor...${NC}"
mysql -u diyetlenio_user -p'diyetlenio2025' diyetlenio < database.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Tablolar başarıyla oluşturuldu${NC}"
else
    echo -e "${RED}✗ Tablolar oluşturulamadı${NC}"
    exit 1
fi

# Kontrol et
echo -e "${YELLOW}[4/4] Kurulum kontrol ediliyor...${NC}"
TABLE_COUNT=$(mysql -u diyetlenio_user -p'diyetlenio2025' -D diyetlenio -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'diyetlenio';")

echo ""
echo "======================================"
echo -e "${GREEN}✓ Kurulum Tamamlandı!${NC}"
echo "======================================"
echo ""
echo "Veritabanı: diyetlenio"
echo "Kullanıcı: diyetlenio_user"
echo "Şifre: diyetlenio2025"
echo "Oluşturulan tablo sayısı: ${TABLE_COUNT}"
echo ""
echo -e "${GREEN}Artık PHP sunucusunu başlatabilirsiniz:${NC}"
echo "php -S localhost:8000 -t public"
echo ""
