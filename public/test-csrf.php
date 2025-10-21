<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "<br>";
echo "Session ID: " . session_id() . "<br>";

try {
    $token = generateCsrfToken();
    echo "CSRF Token generated: " . $token . "<br>";
    echo "Token length: " . strlen($token) . "<br>";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString();
}

echo "<br>Done.";
