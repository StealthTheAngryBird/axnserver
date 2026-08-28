<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/db.php';

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

$reqData = $_POST;
if (empty($reqData)) {
    parse_str(file_get_contents('php://input'), $reqData);
}

$deviceId = $reqData['device_id'] ?? $_GET['device_id'] ?? '';

if (empty($deviceId)) {
    $token = $reqData['access_token'] ?? $_GET['access_token'] ?? '';
    if ($token) {
        $parts = explode(',', $token);
        $deviceId = $parts[5] ?? $parts[0] ?? '';
    }
}

if (empty($deviceId)) {
    $cred = $reqData['anon_credential'] ?? $reqData['credential'] ?? '';
    if ($cred) {
        $parts = explode(',', urldecode($cred));
        $deviceId = $parts[4] ?? $parts[0] ?? $cred;
    }
}

if (empty($deviceId)) {
    $deviceId = 'default_user';
}

$userId = substr(md5($deviceId), 0, 8);

$pdo->beginTransaction();

$stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ? FOR UPDATE");
$stmt->execute([$userId]);
$row = $stmt->fetch();

if ($row) {
    $inventory = json_decode($row['inventory_data'], true) ?: [];
} else {
    $inventory = [];
    $insert = $pdo->prepare("INSERT INTO user_profiles (user_id, inventory_data) VALUES (?, '{}')");
    $insert->execute([$userId]);
}
$cardboxesRaw = $_POST['cardboxes'] ?? '{"1":1}';
$cardboxes = json_decode($cardboxesRaw, true) ?: [];

$newCards = [];
$syncCards = new stdClass();
$syncBoxes = new stdClass();

$rankPoints = (int)($inventory['boxes_rank_points'] ?? 0);

$pools = [
    'D_Car' => [22, 34, 35, 36, 38, 2972], 'C_Car' => [25, 26, 28, 40, 2449, 2896],
    'B_Car' => [15, 17, 18, 19, 27, 30, 32, 39, 2451], 'A_Car' => [8, 11, 12, 13, 52, 2366, 2673, 2739, 2751, 2850, 3011],
    'S_Car' => [7, 23, 43, 44, 47, 50, 53, 54, 55, 56, 2365, 2368, 2955, 3043],
    'D_Tool' => [1], 'C_Tool' => [2], 'B_Tool' => [3], 'A_Tool' => [4], 'S_Tool' => [5], 'RankUp' => [2447]
];

foreach ($cardboxes as $boxId => $amount) {
    $currentBoxCount = (int)($inventory['box_' . $boxId] ?? $amount);
    $inventory['box_' . $boxId] = max(0, $currentBoxCount - $amount);
    $syncBoxes->{(string)$boxId} = $inventory['box_' . $boxId];

    $dropsPerBox = 3; $rates = [];
    if ($boxId == "1") { 
        $dropsPerBox = 3; $rates = ['C_Car' => 61, 'D_Car' => 6149, 'B_Tool' => 446, 'C_Tool' => 1217, 'D_Tool' => 2127]; 
    } elseif ($boxId == "2") { 
        $dropsPerBox = 3; $rates = ['RankUp' => 477, 'C_Car' => 1383, 'D_Car' => 4098, 'B_Tool' => 884, 'C_Tool' => 1314, 'D_Tool' => 1844]; 
    } elseif ($boxId == "3") { 
        $dropsPerBox = 4; $rates = ['RankUp' => 898, 'A_Car' => 756, 'B_Car' => 607, 'C_Car' => 1264, 'D_Car' => 3214, 'S_Tool' => 661, 'A_Tool' => 999, 'B_Tool' => 542, 'C_Tool' => 382, 'D_Tool' => 677]; 
    } elseif ($boxId == "4") { 
        $dropsPerBox = 6; $rates = ['RankUp' => 280, 'A_Car' => 2237, 'B_Car' => 1098, 'C_Car' => 1742, 'D_Car' => 2172, 'S_Tool' => 169, 'A_Tool' => 238, 'B_Tool' => 668, 'C_Tool' => 689, 'D_Tool' => 707]; 
    } else {
        $dropsPerBox = 3; $rates = ['D_Car' => 6149, 'D_Tool' => 3851];
    }

    for ($i = 0; $i < ($dropsPerBox * $amount); $i++) {
        $rand = mt_rand(1, 10000);
        $cumulative = 0;
        $selectedCategory = 'D_Car';
        foreach ($rates as $cat => $chance) {
            $cumulative += $chance;
            if ($rand <= $cumulative) {
                $selectedCategory = $cat; 
                break;
            }
        }
        $possibleIds = $pools[$selectedCategory] ?? $pools['D_Car'];
        $randomCardId = (string)$possibleIds[array_rand($possibleIds)];
        
        $newCards[$randomCardId] = ($newCards[$randomCardId] ?? 0) + 1;
    }
}

$inventory['boxes_rank_points'] = $rankPoints;

foreach ($newCards as $cardId => $amount) {
    $currentAmount = (int)($inventory[$cardId] ?? 0);
    $inventory[$cardId] = $currentAmount + $amount;
    $syncCards->{(string)$cardId} = $inventory[$cardId];
}

$update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
$update->execute([json_encode($inventory), $userId]);

$pdo->commit();

$clientSync = (int)($_POST['prokits_inventory_sync_key'] ?? 0);
$syncKey = (string)($clientSync > 0 ? $clientSync + 10 : time());

$response = [
    "body" => [
        "prokits_inventory_partial_sync" => [
            "body" => [
                "cards" => empty((array)$syncCards) ? new stdClass() : $syncCards,
                "card_boxes" => empty((array)$syncBoxes) ? new stdClass() : $syncBoxes,
                "boxes_rank_points" => $rankPoints,
                "sync_key" => $syncKey
            ]
        ],
        "prokits_box_opened" => [
            "body" => [
                "new_cards" => empty($newCards) ? new stdClass() : $newCards,
                "cardboxes" => $cardboxes
            ]
        ],
        "metadata" => [
            "career_data_asset_sync" => [
                "body" => [
                    "up_to_date" => true,
                    "asset_name" => "career_info_data_upd1.6.2a_152",
                    "asset_etag" => "8c805c8c85a5502a199f41c704101df33326297ff25d6b98b989008f10784cd2"
                ]
            ]
        ]
    ]
];

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($response);
ob_end_flush();
exit;