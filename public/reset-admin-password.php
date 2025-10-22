<?php
/**
 * Emergency Admin Password Reset
 * DELETE THIS FILE AFTER USE!
 */

// Security token
$token = $_GET['token'] ?? '';
$expectedToken = md5('diyetlenio-emergency-reset-2025');

if ($token !== $expectedToken) {
    die('Invalid token. Access denied.');
}

// Direct database connection
require_once __DIR__ . '/../includes/bootstrap.php';

$conn = $db->getConnection();

// New admin password
$newPassword = 'Admin2025!';
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    // Update admin password
    $stmt = $conn->prepare("
        UPDATE users
        SET password = ?,
            updated_at = NOW()
        WHERE email = 'admin@diyetlenio.com'
    ");

    $stmt->execute([$hashedPassword]);

    if ($stmt->rowCount() > 0) {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Password Reset Success</title>";
        echo "<style>body{font-family:monospace;background:#1e1e1e;color:#00ff00;padding:40px;}";
        echo ".success{color:#00ff00;font-size:24px;margin:20px 0;}";
        echo ".info{color:#00aaff;margin:10px 0;}";
        echo ".warning{color:#ffaa00;font-weight:bold;margin:20px 0;}";
        echo "code{background:#000;padding:5px 10px;border-radius:3px;}</style>";
        echo "</head><body>";

        echo "<h1>✅ Admin Password Reset Successful!</h1>";

        echo "<div class='success'>Admin password has been reset successfully!</div>";

        echo "<div class='info'><strong>Email:</strong> <code>admin@diyetlenio.com</code></div>";
        echo "<div class='info'><strong>New Password:</strong> <code>{$newPassword}</code></div>";

        echo "<div class='warning'>⚠️ IMPORTANT: DELETE THIS FILE NOW!</div>";
        echo "<p>Run: <code>rm " . __FILE__ . "</code></p>";

        echo "<p style='margin-top:30px;'>";
        echo "<a href='/login.php' style='background:#00ff00;color:#000;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Login</a>";
        echo "</p>";

        echo "</body></html>";
    } else {
        echo "❌ ERROR: Admin user not found in database!";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . htmlspecialchars($e->getMessage());
}
?>
