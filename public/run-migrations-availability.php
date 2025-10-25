<?php
/**
 * Migration Runner: Availability System
 * Bu sayfayı bir kez çalıştırın: /run-migrations-availability.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Sadece admin çalıştırabilir
if (!$auth->check() || $auth->user()->getUserType() !== 'admin') {
    die('Bu sayfaya sadece admin erişebilir.');
}

$conn = $db->getConnection();
$results = [];

try {
    // Migration 020: dietitian_availability
    $sql = file_get_contents(__DIR__ . '/../database/migrations/020_create_dietitian_availability.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->exec($statement);
                $results[] = ['status' => 'success', 'message' => 'Executed: ' . substr($statement, 0, 50) . '...'];
            } catch (PDOException $e) {
                $results[] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
    }

    // Migration 021: dietitian_availability_exceptions
    $sql = file_get_contents(__DIR__ . '/../database/migrations/021_create_availability_exceptions.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->exec($statement);
                $results[] = ['status' => 'success', 'message' => 'Executed: ' . substr($statement, 0, 50) . '...'];
            } catch (PDOException $e) {
                $results[] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
    }

    // Migration 022: appointment_reminders
    $sql = file_get_contents(__DIR__ . '/../database/migrations/022_create_appointment_reminders.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->exec($statement);
                $results[] = ['status' => 'success', 'message' => 'Executed: ' . substr($statement, 0, 50) . '...'];
            } catch (PDOException $e) {
                $results[] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
    }

    $results[] = ['status' => 'success', 'message' => '✅ All migrations completed!'];

} catch (Exception $e) {
    $results[] = ['status' => 'error', 'message' => 'Fatal error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Migration Results</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .result { padding: 10px; margin: 5px 0; background: white; border-left: 4px solid #ccc; }
        .result.success { border-color: green; }
        .result.error { border-color: red; }
    </style>
</head>
<body>
    <h1>Migration Results - Availability System</h1>
    <?php foreach ($results as $result): ?>
        <div class="result <?= $result['status'] ?>">
            <strong><?= strtoupper($result['status']) ?>:</strong> <?= htmlspecialchars($result['message']) ?>
        </div>
    <?php endforeach; ?>
    <hr>
    <p><a href="/admin/dashboard.php">← Back to Admin Dashboard</a></p>
</body>
</html>
