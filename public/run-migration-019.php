<?php
/**
 * Migration Runner - 019_create_video_sessions
 * Bu dosya yalnızca kontrollü kurulum sırasında çalıştırılmalıdır.
 * Çalıştırdıktan sonra mutlaka silin!
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Üretimde ek koruma: sadece admin kullanıcı ve gizli token ile çalıştır
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'production') {
    if (!$auth || !$auth->check() || $auth->user()->getUserType() !== 'admin') {
        http_response_code(403);
        die('Forbidden: Sadece admin kullanıcılar production ortamında migration çalıştırabilir.');
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
    die('Geçersiz güvenlik token\'ı. Lütfen doğru token ile tekrar deneyin.');
}

$migrationFile = __DIR__ . '/../database/migrations/019_create_video_sessions.sql';

if (!file_exists($migrationFile)) {
    die('Migration file not found!');
}

$sql = file_get_contents($migrationFile);

// Split by semicolon to execute multiple statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    }
);

$conn = $db->getConnection();
$results = [];
$errors = [];

echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration 019 - Video Sessions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 40px 0; }
        .container { max-width: 900px; }
        .card { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success-box { background: #d1e7dd; border-left: 4px solid #0f5132; padding: 15px; margin: 10px 0; }
        .error-box { background: #f8d7da; border-left: 4px solid #842029; padding: 15px; margin: 10px 0; }
        .code-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 0.9em; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-database me-2"></i>Migration 019: Video Sessions</h4>
            </div>
            <div class="card-body">';

echo '<div class="warning-box">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>UYARI:</strong> Bu migration çalıştırıldıktan sonra bu dosyayı silin!<br>
    <code>rm public/run-migration-019.php</code>
</div>';

echo '<h5 class="mt-4">Migration başlıyor...</h5>';

$successCount = 0;
$errorCount = 0;

foreach ($statements as $index => $statement) {
    $statementNum = $index + 1;

    // Skip comments
    if (strpos(trim($statement), '--') === 0) {
        continue;
    }

    try {
        $conn->exec($statement);
        $successCount++;

        // Extract table name or operation for display
        if (preg_match('/CREATE TABLE[^`]*`?(\w+)`?/i', $statement, $matches)) {
            $operation = "CREATE TABLE: {$matches[1]}";
        } elseif (preg_match('/ALTER TABLE[^`]*`?(\w+)`?/i', $statement, $matches)) {
            $operation = "ALTER TABLE: {$matches[1]}";
        } else {
            $operation = "Statement #$statementNum";
        }

        echo '<div class="success-box">
            <i class="fas fa-check-circle me-2"></i>
            <strong>✓ Success:</strong> ' . htmlspecialchars($operation) . '
        </div>';

        $results[] = [
            'success' => true,
            'operation' => $operation
        ];

    } catch (PDOException $e) {
        $errorCount++;

        // Check if error is "already exists" which is OK
        if (strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo '<div class="success-box">
                <i class="fas fa-info-circle me-2"></i>
                <strong>⚠ Already exists:</strong> Statement #' . $statementNum . ' (Bu normal, tablo zaten var)
            </div>';
        } else {
            echo '<div class="error-box">
                <i class="fas fa-times-circle me-2"></i>
                <strong>✗ Error in statement #' . $statementNum . ':</strong><br>
                ' . htmlspecialchars($e->getMessage()) . '
            </div>';

            $errors[] = [
                'statement' => $statementNum,
                'error' => $e->getMessage()
            ];
        }
    }
}

echo '<hr class="my-4">';

// Summary
echo '<h5>Migration Özeti</h5>';
echo '<div class="row">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3>' . $successCount . '</h3>
                <p class="mb-0">Başarılı</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3>' . $errorCount . '</h3>
                <p class="mb-0">Hata</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3>' . count($statements) . '</h3>
                <p class="mb-0">Toplam</p>
            </div>
        </div>
    </div>
</div>';

// Verify tables created
echo '<h5 class="mt-4">Tablo Doğrulama</h5>';

$tablesToCheck = ['video_sessions', 'video_session_events'];
foreach ($tablesToCheck as $table) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();

        if ($exists) {
            // Get row count
            $countStmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $countStmt->fetch()['count'];

            echo '<div class="success-box">
                <i class="fas fa-check-circle me-2"></i>
                <strong>✓ Table exists:</strong> ' . $table . ' (Kayıt sayısı: ' . $count . ')
            </div>';
        } else {
            echo '<div class="error-box">
                <i class="fas fa-times-circle me-2"></i>
                <strong>✗ Table NOT found:</strong> ' . $table . '
            </div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error-box">
            <i class="fas fa-times-circle me-2"></i>
            <strong>✗ Error checking table ' . $table . ':</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
}

// Check appointments table columns
echo '<h5 class="mt-4">Appointments Table - Video Kolonları</h5>';
try {
    $stmt = $conn->query("SHOW COLUMNS FROM appointments LIKE 'video%'");
    $columns = $stmt->fetchAll();

    if (count($columns) > 0) {
        echo '<div class="success-box">
            <i class="fas fa-check-circle me-2"></i>
            <strong>✓ Video kolonları eklendi:</strong><ul class="mb-0 mt-2">';
        foreach ($columns as $column) {
            echo '<li>' . $column['Field'] . ' (' . $column['Type'] . ')</li>';
        }
        echo '</ul></div>';
    } else {
        echo '<div class="error-box">
            <i class="fas fa-times-circle me-2"></i>
            <strong>✗ Video kolonları bulunamadı!</strong>
        </div>';
    }
} catch (PDOException $e) {
    echo '<div class="error-box">
        <i class="fas fa-times-circle me-2"></i>
        <strong>✗ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>';
}

// Final instructions
if ($errorCount === 0) {
    echo '<div class="alert alert-success mt-4">
        <h5><i class="fas fa-check-circle me-2"></i>Migration Başarıyla Tamamlandı!</h5>
        <p>Şimdi şu adımları takip edin:</p>
        <ol>
            <li>Bu dosyayı silin: <code>rm public/run-migration-019.php</code></li>
            <li>Signaling server\'ı deploy edin</li>
            <li>Frontend entegrasyonunu tamamlayın</li>
        </ol>
    </div>';
} else {
    echo '<div class="alert alert-warning mt-4">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Migration Tamamlandı ama Hatalar Var</h5>
        <p>Lütfen yukarıdaki hataları kontrol edin ve gerekirse manuel olarak düzeltin.</p>
    </div>';
}

echo '      </div>
        </div>
    </div>
</body>
</html>';
?>
