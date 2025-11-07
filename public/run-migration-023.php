<?php
/**
 * Run Migration 023: Emergency Consultations Table
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
    <title>Migration 023</title>
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
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Migration 023: Emergency Consultations</h1>

        <?php
        try {
            // Check if table already exists
            $stmt = $conn->query("SHOW TABLES LIKE 'emergency_consultations'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="info">✅ emergency_consultations tablosu zaten mevcut.</div>';
            } else {
                // Read and execute migration
                $sql = file_get_contents(__DIR__ . '/../database/migrations/023_create_emergency_consultations.sql');

                // Remove comments
                $sql = preg_replace('/^--.*$/m', '', $sql);

                // Execute
                $conn->exec($sql);

                echo '<div class="success">✅ Migration 023 başarıyla çalıştırıldı!</div>';
                echo '<div class="success">✅ emergency_consultations tablosu oluşturuldu.</div>';
            }

            // Show table structure
            $stmt = $conn->query("DESCRIBE emergency_consultations");
            echo '<h3>📊 Tablo Yapısı:</h3>';
            echo '<pre>';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo sprintf("%-20s %-30s %-10s\n",
                    $row['Field'],
                    $row['Type'],
                    $row['Key']
                );
            }
            echo '</pre>';

            // Show record count
            $stmt = $conn->query("SELECT COUNT(*) as count FROM emergency_consultations");
            $count = $stmt->fetch()['count'];
            echo '<div class="info">📈 Kayıt Sayısı: ' . $count . '</div>';

            echo '<div class="error">⚠️ <strong>UYARI:</strong> Migration tamamlandı. Güvenlik için bu dosyayı silin!</div>';

        } catch (Exception $e) {
            echo '<div class="error">❌ Hata: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
            <strong>🔒 Güvenlik Notu:</strong><br>
            Bu migration dosyasını çalıştırdıktan sonra mutlaka silin veya sunucudan kaldırın!
        </div>
    </div>
</body>
</html>
