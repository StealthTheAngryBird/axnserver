<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';

$postData = $_POST;
if (empty($postData)) {
    $rawPost = file_get_contents('php://input');
    parse_str($rawPost, $postData);
}

$reqData = $_POST;
if (empty($reqData)) {
    parse_str(file_get_contents('php://input'), $reqData);
}

$deviceId = $reqData['device_id'] ?? $_GET['device_id'] ?? '';

if (empty($deviceId)) {
    $token = $reqData['access_token'] ?? $_GET['access_token'] ?? '';
    if ($token) {
        $parts = explode(',', urldecode($token));
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

try {
    $stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    $inventory = $row ? json_decode($row['inventory_data'], true) : [];
    if (!$inventory) $inventory = [];

    $blitzFlags = ['event_cleared_101', 'event_cleared_511', 'event_cleared_512'];
    foreach($blitzFlags as $flag) {
        if (isset($inventory[$flag])) {
            unset($inventory[$flag]);        }
    }
    if (!isset($inventory['49'])) {
        $inventory['49'] = 0;    }

    $syncKey = (string)time();

    $currency = strtolower($postData['currency'] ?? 'credits');
    $price = (int)($postData['price'] ?? 0);
    $balance = (int)($postData['balance'] ?? ($inventory['credits'] ?? 0));

    $carId = (string)($postData['car_id'] ?? '');
    $levelUp = (int)($postData['levelup'] ?? 0) + 1;

    $responseBody = [];

    if ($price > 0) {
        $newBalance = max(0, $balance - $price);
        if ($currency === 'premium' || $currency === 'crowns') {
            $inventory['crowns'] = $newBalance;
            $responseBody['crowns_partial_sync'] = ["body" => ["balance" => (int)$newBalance]];
        } else {
            $inventory['credits'] = $newBalance;
            $responseBody['credits_partial_sync'] = ["body" => ["balance" => (int)$newBalance]];
        }
    } else {
        $responseBody['credits_partial_sync'] = ["body" => ["balance" => (int)$balance]];
    }

    $cardsSync = new stdClass();
    $hasCards = false;

    foreach ($postData as $key => $value) {
        if (preg_match('/^([a-z0-9_]+)_id$/', $key, $matches)) {
            $prefix = $matches[1]; 
            if (in_array($prefix, ['car', 'device', 'event', 'season', 'client'])) continue;
            
            $cardIdLoop = (string)$value;
            $cardPrice = (int)($postData[$prefix . '_price'] ?? 0);
            
            if ($cardPrice > 0 && $cardIdLoop !== '' && $cardIdLoop !== '0') {
                $clientBalance = isset($postData[$prefix . '_balance']) ? (int)$postData[$prefix . '_balance'] : null;
                $serverBalance = (int)($inventory[$cardIdLoop] ?? 0);
                
                $currentCardBalance = ($clientBalance !== null) ? $clientBalance : $serverBalance;
                $newCardBalance = max(0, $currentCardBalance - $cardPrice);
                
                $inventory[$cardIdLoop] = $newCardBalance;
                $cardsSync->{$cardIdLoop} = (int)$newCardBalance;
                $hasCards = true;
            }
        }
    }

    if ($hasCards) {
        $responseBody['prokits_inventory_partial_sync'] = [
            "body" => [
                "cards" => $cardsSync,
                "sync_key" => $syncKey
            ]
        ];
    }

    $levelupsDB = isset($inventory['levelups']) && is_array($inventory['levelups']) ? $inventory['levelups'] : [];
    if ($carId !== '') {
        $levelupsDB[$carId] = [
            "levelup" => $levelUp,
            "timeout" => (int)$syncKey
        ];
        $inventory['levelups'] = $levelupsDB;
    }

    $sql = "INSERT INTO user_profiles (user_id, inventory_data) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE inventory_data = VALUES(inventory_data)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, json_encode($inventory)]);
    
    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die(json_encode(["error" => "db_fail"]));
}

$metadataBlock = [
    "daily_missions_state_sync" => [
        "body" => json_decode('{"next_day_timestamp":1776459600,"today":0,"missions":{"41001":{"id":41001,"condition":"UpgradeCars","conditionValue":1,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_UPGRADE_CARS","progress":1,"claimed":0,"start_timestamp":0,"end_timestamp":1776459600,"reward_type":"Credits","reward_subtype":"","reward_amount":10000},"41002":{"id":41002,"condition":"PurchaseBoxInShop","conditionValue":3,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_PURCHASE_IN_SHOP","progress":3,"claimed":0,"start_timestamp":0,"end_timestamp":1776459600,"reward_type":"Crowns","reward_subtype":"","reward_amount":5},"41006":{"id":41006,"condition":"PlayAnyRace","conditionValue":3,"subcondition":3,"subconditionType":"take_place_or_better","text":"STR_DAILY_MISSION_PLAY_ANY_RACE_AT_LEAST_POSITION","progress":3,"claimed":0,"start_timestamp":0,"end_timestamp":1776459600,"reward_type":"Credits","reward_subtype":"","reward_amount":10000},"41007":{"id":41007,"condition":"SpendCredits","conditionValue":3000,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_SPEND_CREDITS","progress":3000,"claimed":0,"start_timestamp":0,"end_timestamp":1776459600,"reward_type":"Crowns","reward_subtype":"","reward_amount":5},"41009":{"id":41009,"condition":"ClaimFreeBox","conditionValue":1,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_CLAIM_FREE_BOX","progress":1,"claimed":0,"start_timestamp":0,"end_timestamp":1776459600,"reward_type":"Booster","reward_subtype":"NitroRecharge","reward_amount":1},"41012":{"id":41012,"condition":"PurchaseInBlackMarket","conditionValue":1,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_PURCHASE_IN_BM","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1776459600,"reward_type":"Crowns","reward_subtype":"","reward_amount":5}},"streak_reward_type":"Credits","streak_reward_subtype":"(null)","streak_reward_amount":17500,"streak_reward_claimed":0,"streak_extra_reward_type":"Credits","streak_extra_reward_subtype":"(null)","streak_extra_reward_amount":17500,"streak_extra_reward_claimed":0,"weekly_streak_state":{"event_id":"984d5f14-2c19-11f1-bb75-b8ca3a634708","next_week_timestamp":1776632400,"current_week":1,"mission_finished":5,"milestone_prize":{"12":{"amount":100,"sub_type":"-","type":"Crowns"},"18":{"amount":7,"sub_type":"18","type":"Card"},"24":{"amount":8,"sub_type":"18","type":"Card"},"30":{"amount":2,"sub_type":"154","type":"CardBox"},"6":{"amount":50000,"sub_type":"-","type":"Credits"}},"rewards_claimed":[]},"unclaimed_rewards":[]}', true)
    ],
    "prokits_car_levelups_full_sync" => [
        "body" => [
            "up_to_date" => false,
            "levelups" => empty($levelupsDB) ? new stdClass() : (object)$levelupsDB,
            "upgrade_tutorial_done" => 0,
            "sync_key" => $syncKey
        ]
    ],
    "career_data_asset_sync" => [
        "body" => [
            "up_to_date" => false,
            "asset_name" => "career_info_data_upd1.6.2a_152",
            "asset_etag" => "8c805c8c85a5502a199f41c704101df33326297ff25d6b98b989008f10784cd2"
        ]
    ]
];

$responseBody['metadata'] = $metadataBlock;

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(["body" => $responseBody]);
exit;