<?php
/**
 * Password hash oluşturucu
 */

$password = 'Admin123!';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: {$password}\n";
echo "Hash: {$hash}\n\n";

echo "SQL Query:\n";
echo "UPDATE users SET password = '{$hash}', updated_at = NOW() WHERE user_type = 'admin';\n";
