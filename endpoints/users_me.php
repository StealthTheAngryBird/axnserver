<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/db.php';

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

$accessToken = '';
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!empty($authHeader) && stripos($authHeader, 'bearer ') !== false) {
    $accessToken = str_ireplace('bearer ', '', $authHeader);
} elseif (isset($_GET['access_token'])) {
    $accessToken = $_GET['access_token'];
}

$accountId = 'default';
$credential = 'anonymous:guest';
$deviceId = 'default';

if ($accessToken) {
    $decodedToken = urldecode($accessToken);
    $parts = explode(',', $decodedToken);
    if (count($parts) >= 6) {
        $accountId = trim($parts[0]);
        $credential = trim($parts[4]);
        $deviceId = trim($parts[5]);
    }
}

$dbId = ($deviceId !== 'default' && !empty($deviceId)) ? $deviceId : $credential;
$userId = substr(md5($dbId), 0, 8);

$alias = substr(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(md5($credential)))), 0, 5);

try {
    $stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    
    if ($row) {
        $inventory = json_decode($row['inventory_data'], true) ?: [];
        if (!empty($inventory['player_name'])) {
            $alias = $inventory['player_name'];        }
    }
} catch (Exception $e) {
}

$uniqueNetflixAccount = "netflix:user_" . substr(md5($credential), 0, 8);

echo json_encode([
    "account" => $accountId,
    "credentials" => [$credential, $uniqueNetflixAccount],
    "client_ids" => [],
    "alias" => [$alias], 
    "installations" => [[
        "language" => "ru",
        "country" => "RU",
        "model" => "Android Device",
        "device_type" => "android",
        "client_id" => "axp:5476:83949:1.6.2a:android:googleplay"
    ]]
]);
exit;