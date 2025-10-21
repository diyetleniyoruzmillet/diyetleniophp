#!/bin/bash

# Railway MySQL Database Import Script
# Bu script Railway'deki MySQL'e database.sql'i import eder

echo "🚂 Railway MySQL Database Import"
echo "=================================="
echo ""

# Railway MySQL bağlantı bilgilerini al
echo "Railway Dashboard'dan MySQL servisine gidin:"
echo "→ Connect sekmesine tıklayın"
echo "→ Bağlantı bilgilerini aşağıya girin"
echo ""

read -p "MySQL Host: " MYSQL_HOST
read -p "MySQL Port (genelde 3306): " MYSQL_PORT
read -p "MySQL User: " MYSQL_USER
read -sp "MySQL Password: " MYSQL_PASSWORD
echo ""
read -p "MySQL Database: " MYSQL_DATABASE

echo ""
echo "Bağlantı test ediliyor..."

# Bağlantıyı test et
mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT 1;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Bağlantı başarılı!"
    echo ""
    echo "Database import ediliyor..."

    mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < database.sql

    if [ $? -eq 0 ]; then
        echo ""
        echo "🎉 Database başarıyla import edildi!"
        echo ""
        echo "Test için kontrol ediliyor..."
        mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SHOW TABLES;"
    else
        echo "❌ Database import hatası!"
        exit 1
    fi
else
    echo "❌ MySQL bağlantısı başarısız!"
    echo "Lütfen bağlantı bilgilerini kontrol edin."
    exit 1
fi
