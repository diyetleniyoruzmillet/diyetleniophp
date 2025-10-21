#!/bin/bash

# Railway Setup Script
# Bu script Railway deployment sırasında gerekli dizinleri oluşturur

echo "🚂 Railway Setup başlatılıyor..."

# Gerekli dizinleri oluştur
mkdir -p storage/sessions
mkdir -p storage/cache
mkdir -p logs
mkdir -p assets/uploads/profiles
mkdir -p assets/uploads/articles
mkdir -p assets/uploads/recipes
mkdir -p assets/uploads/documents

# İzinleri ayarla
chmod -R 755 storage
chmod -R 755 logs
chmod -R 755 assets/uploads

echo "✅ Dizinler oluşturuldu"

# Environment variables kontrolü
echo "🔍 Environment Variables Kontrolü:"
echo "DB_HOST: ${DB_HOST:-'not set'}"
echo "DB_PORT: ${DB_PORT:-'not set'}"
echo "DB_DATABASE: ${DB_DATABASE:-'not set'}"
echo "DB_USERNAME: ${DB_USERNAME:-'not set'}"

echo "✅ Setup tamamlandı!"
