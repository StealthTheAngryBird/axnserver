<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/db.php';

$reqData = $_POST;
if (empty($reqData)) {
    parse_str(file_get_contents('php://input'), $reqData);
}

$accessToken = $reqData['access_token'] ?? $_GET['access_token'] ?? '';
if (empty($accessToken)) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['HTTP_AUTHORIZATION']);
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $accessToken = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
}
if (stripos($accessToken, 'bearer ') !== false) {
    $accessToken = str_ireplace('bearer ', '', $accessToken);
}

$deviceId = $reqData['device_id'] ?? $_GET['device_id'] ?? '';
if (empty($deviceId) && $accessToken) {
    $parts = explode(',', urldecode($accessToken));
    $deviceId = $parts[5] ?? $parts[0] ?? ''; 
}

if (empty($deviceId)) {
    $deviceId = 'default_user';
}

$userId = substr(md5($deviceId), 0, 8);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    
    $inventory = $row ? json_decode($row['inventory_data'], true) : [];
    if (!$inventory) $inventory = [];

    if (empty($inventory['player_name'])) {
        $inventory['player_name'] = substr(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(md5($deviceId)))), 0, 5);
        
        $sql = "INSERT INTO user_profiles (user_id, inventory_data) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE inventory_data = VALUES(inventory_data)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, json_encode($inventory)]);
    }

    $alias = $inventory['player_name'];
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $alias = substr(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(md5($deviceId)))), 0, 5);
}

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(["alias" => $alias, "status" => "valid"]);
exit;