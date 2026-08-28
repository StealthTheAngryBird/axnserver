<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/db.php';

$postData = $_POST;
if (empty($postData)) {
    parse_str(file_get_contents('php://input'), $postData);
}

$accessToken = $postData['access_token'] ?? $_GET['access_token'] ?? '';
if (empty($accessToken)) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['HTTP_AUTHORIZATION']);
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
}

if (stripos($accessToken, 'bearer ') !== false) {
    $accessToken = str_ireplace('bearer ', '', $accessToken);
}

$idStr = 'default_user';
if ($accessToken) {
    $parts = explode(',', urldecode($accessToken));
    $idStr = $parts[5] ?? $parts[4] ?? $idStr;
} else {
    $idStr = $postData['device_id'] ?? $_GET['device_id'] ?? $idStr;
}

$userId = substr(md5($idStr), 0, 8);
$pdo->beginTransaction();
$stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ? FOR UPDATE");
$stmt->execute([$userId]);
$row = $stmt->fetch();
$inventory = $row ? json_decode($row['inventory_data'], true) : [];

$carId = (string)($_POST['car_id'] ?? '22');
$bpId = (string)($_POST['bp_id'] ?? '34');
$bpCost = (int)($_POST['bp_price'] ?? 0);

$syncCards = new stdClass();

if ($bpId !== '0' && $bpCost > 0) {
    $currentAmount = (int)($inventory[$bpId] ?? 0);
    $inventory[$bpId] = max(0, $currentAmount - $bpCost);
    $syncCards->{(string)$bpId} = (int)$inventory[$bpId];
}

$inventory[$carId] = 1;

$update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
$update->execute([json_encode($inventory), $userId]);
$pdo->commit(); 

$syncKey = (string)time();

$jsonArray = [
    "body" => [
        "item" => $carId,
        "quantity" => 1,
        "prokits_inventory_partial_sync" => [
            "body" => [
                "cards" => empty((array)$syncCards) ? new stdClass() : $syncCards,
                "sync_key" => $syncKey
            ]
        ],
        "server_items_partial_sync" => [
            "body" => [
                "cars" => [(int)$carId],
                "sync_key" => $syncKey
            ]
        ],
        "metadata" => [
            "career_data_asset_sync" => [
                "body" => [
                    "up_to_date" => true,
                    "asset_name" => "career_info_data_upd1.6.2a_152"
                ]
            ]
        ]
    ]
];

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo json_encode($jsonArray);
exit;