<?php
error_reporting(0);
ini_set('display_errors', 0);

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

$deviceId = 'default_user';
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!empty($authHeader) && stripos($authHeader, 'bearer ') !== false) {
    $token = urldecode(str_ireplace('bearer ', '', $authHeader));
    $parts = explode(',', $token);
    if (count($parts) >= 6) {
        $deviceId = trim($parts[5]);
    }
}

$uniqueNetflixAccount = "netflix:user_" . substr(md5($deviceId), 0, 8);

$jsonResponse = json_encode([
  "credential" => $uniqueNetflixAccount,
  "status" => "active"
]);

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
exit;