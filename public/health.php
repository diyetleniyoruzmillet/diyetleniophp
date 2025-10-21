<?php
/**
 * Health Check Endpoint
 * Railway için basit health check
 */

header('Content-Type: application/json');
http_response_code(200);

echo json_encode([
    'status' => 'ok',
    'timestamp' => time(),
    'php_version' => phpversion(),
    'server' => 'Railway'
]);
