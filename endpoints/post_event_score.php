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
  
$now = time();

$eventId = (string)($_POST['event_id'] ?? '106');
$carId = (string)($_POST['car_id'] ?? '22');
$position = (int)($_POST['position_in_race'] ?? 1);
$raceTime = (int)($_POST['race_time'] ?? 0);
$star1 = (int)($_POST['star1'] ?? 0);
$star2 = (int)($_POST['star2'] ?? 0);
$star3 = (int)($_POST['star3'] ?? 0);

$clientCredits = (int)($_POST['balance'] ?? ($inventory['credits'] ?? 0));
$clientCrowns = (int)($inventory['crowns'] ?? 0);

$creditsReward = ($position === 1) ? 3000 : (($position === 2) ? 1500 : 500);
$creditsReward += ($star1 + $star2 + $star3) * 500;
$crownsReward = 5;

$syncCards = new stdClass();
$syncBoxes = new stdClass();
$syncDecals = [];

$isFirstWin = empty($inventory['event_cleared_' . $eventId]);

if ($position <= 3) {
    if ($isFirstWin) {
        if ($position === 1) {
            $inventory['event_cleared_' . $eventId] = 1;
        }
    } else {
        $extraCredits = ($position === 1) ? 2500 : (($position === 2) ? 1500 : 1000);
        
        if ((int)$eventId >= 4000 && $position === 1) {
            $extraCredits += 2500; 
        }
        
        $creditsReward += $extraCredits;
    }
}

$inventory['credits'] = $clientCredits + $creditsReward;
$inventory['crowns'] = $clientCrowns + $crownsReward;

$overclockObj = new stdClass();
foreach ($inventory as $k => $v) {
    if (strpos($k, 'ts_booster_overclock_') === 0 && $v > $now) {
        $cId = str_replace('ts_booster_overclock_', '', $k);
        $overclockObj->$cId = (int)$v;
    } elseif (strpos($k, 'ts_booster_overclock_') === 0) {
        unset($inventory[$k]); 
    }
}

$boostersSyncBlock = [
    "body" => [
        "balance" => [
            "booster_double_credits" => (int)($inventory['qty_booster_double_credits'] ?? 0),
            "booster_nitro_recharge" => (int)($inventory['qty_booster_nitro_recharge'] ?? 0),
            "booster_extra_nitro_tank" => (int)($inventory['qty_booster_extra_nitro_tank'] ?? 0),
            "booster_xtreme_wheels" => (int)($inventory['qty_booster_xtreme_wheels'] ?? 0),
            "booster_overclock" => new stdClass()
        ],
        "boosters_activation_end_timestamp" => [
            "booster_double_credits" => (int)($inventory['ts_booster_double_credits'] ?? 0),
            "booster_nitro_recharge" => (int)($inventory['ts_booster_nitro_recharge'] ?? 0),
            "booster_extra_nitro_tank" => (int)($inventory['ts_booster_extra_nitro_tank'] ?? 0),
            "booster_xtreme_wheels" => (int)($inventory['ts_booster_xtreme_wheels'] ?? 0),
            "booster_overclock" => $overclockObj
        ]
    ]
];

$update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
$update->execute([json_encode($inventory), $userId]);

$pdo->commit();

$clientSync = (int)($_POST['career_progress_sync_key'] ?? $_POST['prokits_inventory_sync_key'] ?? 0);
$syncKey = (string)($clientSync > 0 ? $clientSync + 5 : time());
$timestamp = (int)$syncKey;

$response = [
    "body" => [
        "race_time" => $raceTime,
        "position_in_race" => $position,
        "star1" => $star1,
        "star2" => $star2,
        "star3" => $star3,
        "timestamp" => $timestamp,
        
        "credits_full_sync" => ["body" => ["balance" => (int)$inventory['credits']]],
        "crowns_full_sync" => ["body" => ["balance" => (int)$inventory['crowns']]],
        
        "career_progress_partial_sync" => [
            "body" => [
                "events" => [$eventId => ["star1" => $star1, "star2" => $star2, "star3" => $star3]],
                "sync_key" => $syncKey
            ]
        ],
        
        "prokits_inventory_partial_sync" => [
            "body" => [
                "cards" => (empty((array)$syncCards) ? new stdClass() : $syncCards),
                "card_boxes" => (empty((array)$syncBoxes) ? new stdClass() : $syncBoxes),
                "boxes_rank_points" => (int)($inventory['boxes_rank_points'] ?? 0),
                "sync_key" => $syncKey
            ]
        ],
        
        "boosters_sync" => $boostersSyncBlock,
        
        "maintenance_state_partial_sync" => [
            "body" => [
                "up_to_date" => false,
                "cars" => [$carId => ["points" => 130]],
                "mechanics" => new stdClass(),
                "accelerate_tutorial_done" => 0,
                "sync_key" => $syncKey
            ]
        ]
    ]
];

if (!empty($syncDecals)) {
    $response["body"]["server_items_partial_sync"] = [
        "body" => [
            "decals" => $syncDecals,
            "sync_key" => $syncKey
        ]
    ];
}

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($response);
ob_end_flush();
exit;