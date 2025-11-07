<?php
/**
 * Run Migration 024: Dietitian Availability Table
 *
 * IMPORTANT: Delete this file after running!
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration 024</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #56ab2f;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #17a2b8;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9em;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
            border-left: 5px solid #ffc107;
        }
        .feature-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .feature-list li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Migration 024: Dietitian Availability</h1>

        <div class="info">
            <strong>📝 Bu Migration Ne Yapar?</strong>
            <ul class="feature-list">
                <li>Diyetisyenlerin haftalık müsaitlik takvimini saklar</li>
                <li>Her gün için başlangıç ve bitiş saati belirlenir</li>
                <li>Randevu sistemi için müsaitlik kontrolü sağlar</li>
                <li>Unique constraint ile aynı günde çift kayıt engellenir</li>
            </ul>
        </div>

        <?php
        try {
            // Check if table already exists
            $stmt = $conn->query("SHOW TABLES LIKE 'dietitian_availability'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="info">✅ dietitian_availability tablosu zaten mevcut.</div>';

                // Show existing structure
                $stmt = $conn->query("DESCRIBE dietitian_availability");
                echo '<h3>📊 Mevcut Tablo Yapısı:</h3>';
                echo '<pre>';
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo sprintf("%-20s %-35s %-10s\n",
                        $row['Field'],
                        $row['Type'],
                        $row['Key']
                    );
                }
                echo '</pre>';

                // Show count
                $stmt = $conn->query("SELECT COUNT(*) as count FROM dietitian_availability");
                $count = $stmt->fetch()['count'];
                echo '<div class="info">📈 Kayıt Sayısı: ' . $count . '</div>';

            } else {
                // Read and execute migration
                $sql = file_get_contents(__DIR__ . '/../database/migrations/024_create_dietitian_availability.sql');

                // Remove comments
                $sql = preg_replace('/^--.*$/m', '', $sql);

                // Execute
                $conn->exec($sql);

                echo '<div class="success">✅ Migration 024 başarıyla çalıştırıldı!</div>';
                echo '<div class="success">✅ dietitian_availability tablosu oluşturuldu.</div>';

                // Show table structure
                $stmt = $conn->query("DESCRIBE dietitian_availability");
                echo '<h3>📊 Tablo Yapısı:</h3>';
                echo '<pre>';
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo sprintf("%-20s %-35s %-10s\n",
                        $row['Field'],
                        $row['Type'],
                        $row['Key']
                    );
                }
                echo '</pre>';

                echo '<div class="info">📈 Kayıt Sayısı: 0 (Yeni oluşturuldu)</div>';
            }

            // Show related pages
            echo '<div class="success">';
            echo '<h3>✨ Aktif Olan Sayfalar:</h3>';
            echo '<ul>';
            echo '<li><strong>/dietitian/availability.php</strong> - Diyetisyen müsaitlik takvimi</li>';
            echo '<li><strong>/admin/users.php</strong> - Kullanıcı yönetimi</li>';
            echo '<li><strong>/admin/dietitians.php</strong> - Diyetisyen onaylama</li>';
            echo '<li><strong>/admin/appointments.php</strong> - Randevu yönetimi</li>';
            echo '</ul>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="error">❌ Hata: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>

        <div class="warning">
            <strong>🔒 ÖNEMLİ GÜVENLİK UYARISI:</strong><br><br>
            ✅ Migration tamamlandı.<br>
            ⚠️ Bu dosyayı (<code>run-migration-024.php</code>) MUTLAKA silin!<br>
            🔐 Güvenlik için bu tür dosyaları sunucuda bırakmayın.
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="/admin/dashboard.php" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                🏠 Admin Dashboard'a Git
            </a>
        </div>
    </div>
</body>
</html>
