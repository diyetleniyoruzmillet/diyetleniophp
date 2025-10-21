#!/bin/bash

# Diyetlenio - Veritabanı Kurulum Script
# Bu script veritabanını ve kullanıcıyı oluşturur

echo "============================================"
echo "Diyetlenio - Veritabanı Kurulum"
echo "============================================"
echo ""

# MySQL root şifresi sor
read -sp "MySQL root şifresini girin: " MYSQL_ROOT_PASSWORD
echo ""

# Veritabanı bilgileri
DB_NAME="diyetlenio"
DB_USER="diyetlenio_user"
DB_PASS="diyetlenio2025"

echo "Veritabanı oluşturuluyor..."

# MySQL komutlarını çalıştır
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<MYSQL_SCRIPT
-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Kullanıcıyı oluştur
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';

-- Yetkileri ver
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';

-- Yetkileri yenile
FLUSH PRIVILEGES;

-- Kullanıcı ve veritabanını göster
SELECT user, host FROM mysql.user WHERE user = '${DB_USER}';
SHOW DATABASES LIKE '${DB_NAME}';
MYSQL_SCRIPT

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Veritabanı ve kullanıcı başarıyla oluşturuldu!"
    echo ""
    echo "Veritabanı bilgileri:"
    echo "  Veritabanı: ${DB_NAME}"
    echo "  Kullanıcı: ${DB_USER}"
    echo "  Şifre: ${DB_PASS}"
    echo ""
    echo "Şimdi database.sql dosyasını import ediyoruz..."
    echo ""

    # database.sql dosyasını import et
    mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < database.sql

    if [ $? -eq 0 ]; then
        echo "✓ database.sql başarıyla import edildi!"
        echo ""
        echo "Admin kullanıcısı ekleniyor..."
        php scripts/add-admin.php
    else
        echo "✗ database.sql import edilemedi!"
        exit 1
    fi
else
    echo "✗ Veritabanı oluşturulamadı!"
    exit 1
fi

echo ""
echo "============================================"
echo "Kurulum tamamlandı!"
echo "============================================"
