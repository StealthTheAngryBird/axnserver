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
if (empty($reqData)) parse_str(file_get_contents('php://input'), $reqData);

$accessToken = $reqData['access_token'] ?? $_GET['access_token'] ?? '';
if (empty($accessToken)) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['HTTP_AUTHORIZATION']);
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $accessToken = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
}
if (stripos($accessToken, 'bearer ') !== false) $accessToken = str_ireplace('bearer ', '', $accessToken);

$deviceId = 'default_user';
if ($accessToken) {
    $parts = explode(',', urldecode($accessToken));
    $deviceId = $parts[5] ?? $parts[0] ?? $deviceId;
} else {
    $deviceId = $reqData['device_id'] ?? $_GET['device_id'] ?? $deviceId;
}
$userId = substr(md5($deviceId), 0, 8);

$rewardName = $reqData['reward_name'] ?? 'TutorialFirstCarReward';

$pdo->beginTransaction();
$stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ? FOR UPDATE");
$stmt->execute([$userId]);
$row = $stmt->fetch();
$inventory = $row ? json_decode($row['inventory_data'], true) : [];
if (!$inventory) $inventory = [];

$claimedRewards = $inventory['claimed_one_time_rewards'] ?? ["_"];
$syncKey = (string)time();
$cardsSync = new stdClass();

if (!in_array($rewardName, $claimedRewards)) {
    
    if ($rewardName === 'TutorialFirstCarReward') {
        $inventory['34'] = (int)($inventory['34'] ?? 0) + 10;
        $cardsSync->{"34"} = $inventory['34'];
    }
    
    $claimedRewards[] = $rewardName;
    $inventory['claimed_one_time_rewards'] = $claimedRewards;
    
    $update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
    $update->execute([json_encode($inventory), $userId]);
}
$pdo->commit();

$responseBody = [
    "daily_login_bonus_state_sync" => [
        "body" => [
            "next_daily_login_bonus_timestamp" => 0,
            "next_bonus_table" => 1,
            "next_bonus_day" => 1,
            "next_monthly_card_bonus_timestamp" => 0,
            "monthly_card_activation_timestamp" => 0,
            "monthly_card_balance" => 0,
            "monthly_card_reward" => 0,
            "claimed_one_time_rewards" => $claimedRewards
        ]
    ],
    "metadata" => [
        "career_data_asset_sync" => ["body" => ["up_to_date" => true, "asset_name" => "career_info_data_upd1.6.2a_152"]]
    ]
];

if (!empty((array)$cardsSync)) {
    $responseBody["prokits_inventory_partial_sync"] = [
        "body" => ["cards" => $cardsSync, "sync_key" => $syncKey]
    ];
}

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(["body" => $responseBody]);
ob_end_flush();
exit;