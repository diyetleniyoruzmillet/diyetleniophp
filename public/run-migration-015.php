<?php
/**
 * Web-based Migration Runner for Migration 015
 * Danışan-Diyetisyen atama tablosunu oluşturur.
 * Çalıştırdıktan sonra mutlaka silin!
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Üretimde ek koruma: sadece admin kullanıcı ve gizli token ile çalıştır
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'production') {
    if (!$auth || !$auth->check() || $auth->user()->getUserType() !== 'admin') {
        http_response_code(403);
        die('🔒 Erişim engellendi. Production ortamında yalnızca admin çalıştırabilir.');
    }
}

// Güçlü token doğrulaması (.env üzerinden)
$providedToken = $_GET['token'] ?? '';
$envToken = $_ENV['MIGRATION_TOKEN'] ?? '';
if (empty($envToken)) {
    http_response_code(400);
    die('MIGRATION_TOKEN .env içinde tanımlı değil. Lütfen .env dosyanıza MIGRATION_TOKEN=<güçlü-bir-değer> ekleyin.');
}
if (!hash_equals($envToken, $providedToken)) {
    http_response_code(403);
    die('🔒 Access Denied. Invalid token.');
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration 015 Runner</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
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
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: bold;
        }
        .btn:hover {
            background: #2980b9;
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
        <h1>🔧 Migration 015: Client-Dietitian Assignments</h1>

        <div class="info">
            <strong>📋 Bu migration ne yapar?</strong>
            <ul>
                <li>Danışan-diyetisyen atama tablosu oluşturur</li>
                <li>Admin kullanıcıları danışanlara diyetisyen atayabilir</li>
                <li>Her danışan için bir aktif diyetisyen tutulur</li>
            </ul>
        </div>

        <?php if (isset($_POST['run_migration'])): ?>
            <?php
            try {
                $conn = $db->getConnection();

                $sql = file_get_contents(__DIR__ . '/../database/migrations/015_create_client_dietitian_assignments_table.sql');

                // Split by semicolon and execute each statement
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) {
                        return !empty($stmt) && !preg_match('/^--/', $stmt);
                    }
                );

                $executedCount = 0;
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $conn->exec($statement);
                        $executedCount++;
                    }
                }

                echo '<div class="success">';
                echo '<h3>✅ Migration Başarılı!</h3>';
                echo '<p><strong>Executed statements:</strong> ' . $executedCount . '</p>';
                echo '<p><strong>Table created:</strong> client_dietitian_assignments</p>';
                echo '<p><strong>Indexes created:</strong> 3 indexes</p>';
                echo '</div>';

                // Check table
                $stmt = $conn->query("DESCRIBE client_dietitian_assignments");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<h3>📊 Tablo Yapısı:</h3>';
                echo '<pre>';
                foreach ($columns as $col) {
                    echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Key'] . "\n";
                }
                echo '</pre>';

            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h3>❌ Migration Hatası!</h3>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
        ?>
            <a href="/admin/users.php" class="btn">👥 Kullanıcı Yönetimine Git</a>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="run_migration" class="btn" style="background: #28a745;">
                    ▶️ Migration'ı Çalıştır
                </button>
            </form>

            <h3>📄 Migration SQL:</h3>
            <pre><?php
                echo htmlspecialchars(file_get_contents(__DIR__ . '/../database/migrations/015_create_client_dietitian_assignments_table.sql'));
            ?></pre>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <strong>🔒 Güvenlik:</strong> Bu dosyayı kullandıktan sonra silin!<br>
            <code>rm public/run-migration-015.php</code>
        </div>
    </div>
</body>
</html>
