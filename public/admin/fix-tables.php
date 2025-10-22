<?php
/**
 * Fix Missing Tables - Emergency
 * Token protected, no login required
 */

// Security token
$token = $_GET['token'] ?? '';
$expectedToken = md5('diyetlenio-fix-tables-2025');

if ($token !== $expectedToken) {
    die('Invalid token. Access denied.');
}

require_once __DIR__ . '/../../includes/bootstrap.php';

$conn = $db->getConnection();

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Missing Tables</title>";
echo "<style>body{font-family:monospace;background:#1e1e1e;color:#00ff00;padding:20px;}";
echo ".success{color:#00ff00;}.error{color:#ff0000;}.info{color:#00aaff;}</style>";
echo "</head><body>";

echo "<h1>🔧 Fixing Missing Tables</h1><pre>";

// Fix 1: client_dietitian_assignments table
echo "\n=== Creating client_dietitian_assignments table ===\n";
try {
    // Drop if exists (to recreate with correct foreign keys)
    $conn->exec("DROP TABLE IF EXISTS client_dietitian_assignments");

    // Create with INT UNSIGNED to match users.id
    $sql = "
    CREATE TABLE client_dietitian_assignments (
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        client_id INT UNSIGNED NOT NULL,
        dietitian_id INT UNSIGNED NOT NULL,
        assigned_by INT UNSIGNED NOT NULL,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        is_active TINYINT(1) DEFAULT 1,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_client_id (client_id),
        INDEX idx_dietitian_id (dietitian_id),
        INDEX idx_is_active (is_active),
        INDEX idx_active_assignment (client_id, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->exec($sql);
    echo "<span class='success'>✓ client_dietitian_assignments table created</span>\n";

} catch (Exception $e) {
    echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

// Fix 2: Check if appointments table exists
echo "\n=== Checking appointments table ===\n";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'appointments'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='info'>✓ appointments table exists</span>\n";
    } else {
        echo "<span class='error'>✗ appointments table missing - creating basic structure</span>\n";

        $sql = "
        CREATE TABLE appointments (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            client_id INT UNSIGNED NOT NULL,
            dietitian_id INT UNSIGNED NOT NULL,
            appointment_date DATETIME NOT NULL,
            status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_client_id (client_id),
            INDEX idx_dietitian_id (dietitian_id),
            INDEX idx_appointment_date (appointment_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $conn->exec($sql);
        echo "<span class='success'>✓ appointments table created</span>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

// Fix 3: Check dietitian_profiles
echo "\n=== Checking dietitian_profiles table ===\n";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'dietitian_profiles'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='info'>✓ dietitian_profiles table exists</span>\n";
    } else {
        echo "<span class='error'>✗ dietitian_profiles table missing</span>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

echo "\n<span class='success'>===✓ DONE ===</span>\n";
echo "\n<span class='info'>Now try: <a href='/admin/users.php' style='color:#00ff00;'>Admin Users Page</a></span>\n";

echo "</pre></body></html>";
?>
