<?php 
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php';

header('HTTP/1.1 200 OK');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$credential = $credential ?? $_GET['credential'] ?? 'anonymous:guest';

$accessToken = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['HTTP_AUTHORIZATION']);
elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $accessToken = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}
if (stripos($accessToken, 'bearer ') !== false) {
    $accessToken = str_ireplace('bearer ', '', $accessToken);
}

$deviceId = '';
$fedId = '';
if ($accessToken) {
    $parts = explode(',', urldecode($accessToken));
    $fedId = $parts[0] ?? '';
    $deviceId = $parts[5] ?? $parts[0] ?? '';
}

if (empty($deviceId)) $deviceId = 'default_user';
$userId = substr(md5($deviceId), 0, 8);

$playerName = "Racer_" . substr(md5($deviceId), 0, 5);
if (empty($fedId)) {
    $hash = md5($credential . $deviceId);
    $fedId = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20, 12);
}

try {
    $stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $inventory = json_decode($row['inventory_data'], true);
        if (!empty($inventory['player_name'])) {
            $playerName = $inventory['player_name'];
        }
    }
} catch (Exception $e) {}

$response = [
  "credential" => $credential,
  "name" => $playerName,
  "avatar" => "",
  "game" => "axp",
  "status_line" => "",
  "fed_id" => $fedId,
  "avatar_name" => null,
  "online" => false,
  "seconds_since_last_status_change" => null,
  "games" => [
    "axp" => ["last_time_played" => time()]
  ],
  "country" => null,
  "language" => null,
  "groups" => [],
  "participations" => []
];

$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
exit;