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
$syncKey = (string)time();
$now = time();

$item = (string)($_POST['item'] ?? $_POST['item_id'] ?? 'unknown');
$category = strtolower($_POST['category'] ?? '');
$currency = strtolower($_POST['currency'] ?? 'credits');
$price = (int)($_POST['price'] ?? 0);
$balance = (int)($_POST['balance'] ?? ($inventory['credits'] ?? 0));
$carId = (string)($_POST['car_id'] ?? '0');
$quantity = (int)($_POST['quantity'] ?? 1);
$multiplePurchases = (int)($_POST['multiple_purchases'] ?? 1);

$responseBody = ["item" => $item];
if ($category !== 'prokits_boxes') {
    $responseBody["quantity"] = (string)$quantity;
}

$isFreeClaim = false;
$finalPrice = $price;
$isOverclock = (strpos($item, 'overclock') !== false);

if ($category === 'prokits_boxes' || stripos($item, 'box') !== false) {
    $boxId = "1"; $dropsPerBox = 3; $rates = [];
    
    if (stripos($item, 'Iron') !== false) {
        $boxId = "1"; $dropsPerBox = 3;
        $rates = ['C_Car' => 1500, 'D_Car' => 4000, 'B_Tool' => 1000, 'C_Tool' => 1500, 'D_Tool' => 2000];
    }
    elseif (stripos($item, 'Bronze') !== false) { 
        $boxId = "2"; $dropsPerBox = 3;
        $rates = ['RankUp' => 1000, 'B_Car' => 1000, 'C_Car' => 2500, 'D_Car' => 2000, 'A_Tool' => 500, 'B_Tool' => 1000, 'C_Tool' => 2000];
    }
    elseif (stripos($item, 'Silver') !== false) { 
        $boxId = "3"; $dropsPerBox = 4;
        $rates = ['RankUp' => 1500, 'S_Car' => 500, 'A_Car' => 1500, 'B_Car' => 2000, 'C_Car' => 1500, 'S_Tool' => 500, 'A_Tool' => 1000, 'B_Tool' => 1500];
    }
    elseif (stripos($item, 'Gold') !== false) { 
        $boxId = "4"; $dropsPerBox = 6;
        $rates = ['RankUp' => 2000, 'S_Car' => 2500, 'A_Car' => 2500, 'B_Car' => 1000, 'S_Tool' => 1000, 'A_Tool' => 1000];
    }
    
    if (empty($rates)) {
        $rates = ['D_Car' => 6000, 'D_Tool' => 4000];
    }

    $lastFreeBox = (int)($inventory['_last_free_box'] ?? 0);
    $cooldown = 3600;
    $isFreeBoxReady = ($lastFreeBox == 0 || $now >= ($lastFreeBox + $cooldown));

    if ($boxId == "1" && $multiplePurchases == 1 && ($price == 0 || $isFreeBoxReady)) {
        $isFreeClaim = true;
        $inventory['_last_free_box'] = $now;
        $nextFreeTime = $now + $cooldown;
        $finalPrice = 0;
    } else {
        if ($isFreeBoxReady) {
            $nextFreeTime = 0; 
        } else {
            $nextFreeTime = $lastFreeBox + $cooldown;
        }
    }

    $pools = [
        'D_Car' => [22, 34, 35, 36, 38, 2972], 'C_Car' => [25, 26, 28, 40, 2449, 2896],
        'B_Car' => [15, 17, 18, 19, 27, 30, 32, 39, 2451], 'A_Car' => [8, 11, 12, 13, 52, 2366, 2673, 2739, 2751, 2850, 3011],
        'S_Car' => [7, 23, 43, 44, 47, 50, 53, 54, 55, 56, 2365, 2368, 2955, 3043],
        'D_Tool' => [1], 'C_Tool' => [2], 'B_Tool' => [3], 'A_Tool' => [4], 'S_Tool' => [5], 'RankUp' => [2447]
    ];
    
    $newCards = [];
    $totalDrops = $dropsPerBox * $multiplePurchases;
    
    for ($i = 0; $i < $totalDrops; $i++) {
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
        $id = (string)$possibleIds[array_rand($possibleIds)];
        
        $newCards[$id] = ($newCards[$id] ?? 0) + 1;
        $inventory[$id] = ($inventory[$id] ?? 0) + 1;
    }

    $cardsSync = [];
    foreach($inventory as $k => $v) { if(is_numeric($k)) $cardsSync[$k] = $v; }

    $responseBody['prokits_inventory_partial_sync'] = [
        "body" => [
            "cards" => empty($cardsSync) ? new stdClass() : $cardsSync,
            "card_boxes" => [$boxId => 0],
            "free_boxes_timestamps" => ["1" => (int)$nextFreeTime, "119" => 0, "120" => 0, "121" => 0, "122" => 0, "123" => 0],
            "sync_key" => $syncKey
        ]
    ];
    $responseBody['prokits_box_opened'] = ["body" => ["new_cards" => empty($newCards) ? new stdClass() : $newCards, "cardboxes" => [$boxId => $multiplePurchases]]];
}

if ($isFreeClaim) {
    $responseBody['credits_partial_sync'] = ["body" => ["balance" => $balance]];
    $inventory['credits'] = $balance;
} elseif ($finalPrice > 0 && !$isOverclock) {
    $total = $finalPrice * ($category === 'prokits_boxes' ? $multiplePurchases : 1);
    
    if ($currency === 'premium' || $currency === 'crowns') {
        $invBalance = (int)($inventory['crowns'] ?? 0);
        $newVal = max(0, $invBalance - $total);
        $inventory['crowns'] = $newVal;
        $responseBody['crowns_partial_sync'] = ["body" => ["balance" => $newVal]];
    } elseif ($currency === 'credits') {
        $invBalance = (int)($inventory['credits'] ?? 0);
        $newVal = max(0, $invBalance - $total);
        $inventory['credits'] = $newVal;
        $responseBody['credits_partial_sync'] = ["body" => ["balance" => $newVal]];
    }
}

if ($category === 'boosters' || stripos($item, 'booster') !== false) {
    foreach (['booster_xtreme_wheels', 'booster_nitro_recharge', 'booster_extra_nitro_tank', 'booster_double_credits'] as $b) {
        if (($inventory['ts_' . $b] ?? 0) < $now) $inventory['ts_' . $b] = 0; 
    }

    $durationToAdd = $quantity * 60;

    if ($isOverclock && $carId !== '0') {
        $responseBody["item"] = "booster_overclock";

        $cardsToDeduct = ($price > 0) ? $price : 1;
        $wildcards = ['38', '40', '39', '52', '56', $carId];
        $targetCardId = $carId;
        
        foreach ($wildcards as $w) {
            if (isset($inventory[$w]) && (int)$inventory[$w] === $balance) {
                $targetCardId = (string)$w;
                break;
            }
        }

        $currentCards = (int)($inventory[$targetCardId] ?? $balance);
        $inventory[$targetCardId] = max(0, $currentCards - $cardsToDeduct);

        $cardsSync = new stdClass();
        foreach($inventory as $k => $v) { 
            if(is_numeric($k)) $cardsSync->{$k} = (int)$v; 
        }
        $responseBody['prokits_inventory_partial_sync'] = [
            "body" => [
                "cards" => $cardsSync,
                "card_boxes" => new stdClass(),
                "sync_key" => $syncKey
            ]
        ];

        $curr = (int)($inventory['ts_booster_overclock_'.$carId] ?? 0);
        if ($curr < $now) $curr = $now;
        $inventory['ts_booster_overclock_'.$carId] = $curr + $durationToAdd;
        
    } else {
        $curr = (int)($inventory['ts_'.$item] ?? 0);
        if ($curr < $now) $curr = $now;
        $inventory['ts_'.$item] = $curr + $durationToAdd;
    }
}

if ($category === 'decals') {
    $inventory['item_' . $item] = 1;
    $numericId = (int)str_replace('Decal_', '', $item);
    $responseBody['server_items_partial_sync'] = [
        "body" => [
            "decals" => [$numericId],
            "sync_key" => $syncKey
        ]
    ];
}

$overclockObj = new stdClass();
$hasOverclock = false;
foreach ($inventory as $k => $v) {
    if (strpos($k, 'ts_booster_overclock_') === 0 && $v > $now) {
        $cId = str_replace('ts_booster_overclock_', '', $k);
        $overclockObj->$cId = (int)$v;
        $hasOverclock = true;
    } elseif (strpos($k, 'ts_booster_overclock_') === 0) {
        unset($inventory[$k]); 
    }
}
if (!$hasOverclock) $overclockObj = new stdClass();

$activeBoosters = false;
foreach (['booster_double_credits', 'booster_nitro_recharge', 'booster_extra_nitro_tank', 'booster_xtreme_wheels'] as $b) {
    if (($inventory['ts_' . $b] ?? 0) > $now) $activeBoosters = true;
}
if ($hasOverclock) $activeBoosters = true;

if ($activeBoosters) {
    $responseBody['boosters_sync'] = [
        "body" => [
            "balance" => ["booster_double_credits"=>0,"booster_nitro_recharge"=>0,"booster_extra_nitro_tank"=>0,"booster_xtreme_wheels"=>0,"booster_overclock"=>new stdClass()],
            "boosters_activation_end_timestamp" => [
                "booster_double_credits" => (int)($inventory['ts_booster_double_credits'] ?? 0),
                "booster_nitro_recharge" => (int)($inventory['ts_booster_nitro_recharge'] ?? 0),
                "booster_extra_nitro_tank" => (int)($inventory['ts_booster_extra_nitro_tank'] ?? 0),
                "booster_xtreme_wheels" => (int)($inventory['ts_booster_xtreme_wheels'] ?? 0),
                "booster_overclock" => $overclockObj
            ]
        ]
    ];
}

$update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
$update->execute([json_encode($inventory), $userId]);

$pdo->commit();

$responseBody['metadata'] = ["daily_missions_state_sync" => ["body" => ["next_day_timestamp" => $now + 86400, "today" => 0, "missions" => new stdClass()]]];
$jsonResponse = json_encode(["body" => $responseBody]);

ob_clean();
header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo $jsonResponse;
exit;