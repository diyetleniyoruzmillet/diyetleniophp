#!/usr/bin/env php
<?php
/**
 * Tüm Diyetisyenleri Onayla
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $conn = $db->getConnection();

    echo "=== Diyetisyen Onaylama Scripti ===\n\n";

    // Önce mevcut durumu göster
    $stmt = $conn->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) as active,
               SUM(CASE WHEN dp.is_approved = 1 THEN 1 ELSE 0 END) as approved
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE u.user_type = 'dietitian'
    ");
    $stats = $stmt->fetch();

    echo "Mevcut Durum:\n";
    echo "  Toplam Diyetisyen: " . $stats['total'] . "\n";
    echo "  Aktif: " . $stats['active'] . "\n";
    echo "  Onaylı: " . $stats['approved'] . "\n\n";

    // Tüm diyetisyenleri onayla ve aktif et
    $stmt = $conn->prepare("
        UPDATE dietitian_profiles dp
        INNER JOIN users u ON dp.user_id = u.id
        SET dp.is_approved = 1,
            u.is_active = 1
        WHERE u.user_type = 'dietitian'
    ");
    $stmt->execute();

    echo "✅ Tüm diyetisyenler onaylandı ve aktif edildi!\n\n";

    // Yeni durumu göster
    $stmt = $conn->query("
        SELECT u.id, u.full_name, u.email, dp.title, dp.specialization
        FROM users u
        INNER JOIN dietitian_profiles dp ON u.id = dp.user_id
        WHERE u.user_type = 'dietitian'
        ORDER BY u.id
    ");
    $dietitians = $stmt->fetchAll();

    echo "Diyetisyen Listesi:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($dietitians as $d) {
        echo sprintf(
            "ID: %3d | %-30s | %-30s\n",
            $d['id'],
            $d['full_name'],
            $d['specialization'] ?? 'N/A'
        );
    }
    echo str_repeat("-", 80) . "\n";
    echo "Toplam: " . count($dietitians) . " diyetisyen\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
