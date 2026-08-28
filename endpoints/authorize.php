<?php
error_reporting(0);
ini_set('display_errors', 0);

header('HTTP/1.1 200 OK');
header('Content-Type: application/json;charset=UTF-8');

$credential = $_POST['anon_credential'] ?? $_POST['credential'] ?? 'anonymous:guest';
$deviceId = $_POST['device_id'] ?? $_GET['device_id'] ?? '';

if (empty($deviceId)) {
    $deviceId = '7174' . mt_rand(100000000, 999999999);
}

$hash = md5($credential . "axp_secret");
$fedId = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20, 12);

$tokenScope = "leaderboard_ro auth social alert storage_ro storage message config transaction";
$tokenApp = "axp:5476:83949:1.6.2a:android:googleplay";
$tokenTime = time() . ".000000";

$fullToken = "{$fedId},{$tokenScope},{$tokenApp},{$tokenTime},{$credential},{$deviceId},gold0021|" . md5($fedId);
try {
    require_once __DIR__ . '/../db.php';
    $currentUserId = substr(md5($deviceId), 0, 8);
    
    $stmt = $pdo->prepare("INSERT INTO user_credentials (credential, user_id) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)");
    $stmt->execute([$credential, $currentUserId]);
} catch (Exception $e) {
}
if (isset($_POST['access_token_only']) && $_POST['access_token_only'] === '1') {
    header('Content-Length: ' . strlen($fullToken));
    echo $fullToken;
} else {
    $jsonResponse = json_encode([
        "access_token" => $fullToken,
        "token_type" => "gameloft_online",
        "fed_id" => $fedId,
        "scope" => $tokenScope,
        "refresh_token" => "refresh|" . $fullToken
    ]);
    header('Content-Length: ' . strlen($jsonResponse));
    echo $jsonResponse;
}
exit;