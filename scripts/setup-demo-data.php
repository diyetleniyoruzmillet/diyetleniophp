#!/usr/bin/env php
<?php
/**
 * Tüm Demo Verileri Ekle
 * Run: php scripts/setup-demo-data.php
 */

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║   🎯 DEMO VERİLERİ EKLEME ARACI     ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

// Run demo dietitians
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1/2: Demo Diyetisyenler ekleniyor...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

require __DIR__ . '/add-demo-dietitians.php';

echo "\n";

// Run demo content
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "2/2: Demo İçerik (Makaleler & Tarifler) ekleniyor...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

require __DIR__ . '/add-demo-content.php';

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║        🎉 HEPSİ TAMAMLANDI! 🎉       ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

echo "📋 Eklenen İçerikler:\n";
echo "   👨‍⚕️ Demo Diyetisyenler: 6 adet\n";
echo "   📰 Demo Makaleler: 5 adet\n";
echo "   🍳 Demo Tarifler: 6 adet\n\n";

echo "🔑 Demo Diyetisyen Girişi:\n";
echo "   Email: ayse.yilmaz@diyetlenio.com\n";
echo "   Şifre: Demo123!\n\n";

echo "🌐 Test Sayfaları:\n";
echo "   - Diyetisyenler: http://localhost:8080/dietitians.php\n";
echo "   - Blog: http://localhost:8080/blog.php\n";
echo "   - Tarifler: http://localhost:8080/recipes.php\n";
echo "   - Admin Panel: http://localhost:8080/admin/dashboard.php\n\n";
