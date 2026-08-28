<?php
  error_reporting(0);
ini_set('display_errors', 0);

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
$dbFile = __DIR__ . '/inventory_' . $userId . '.json';
if (!file_exists($dbFile)) {
    file_put_contents($dbFile, json_encode([]));
}
$inventory = json_decode(@file_get_contents($dbFile), true) ?: [];
$now = time();

$boosterType = (string)($postData['booster_type'] ?? '');
$carId = (string)($postData['car_id'] ?? '0');

if ($boosterType !== '') {
    $qtyKey = 'qty_' . $boosterType;
    $currentQty = (int)($inventory[$qtyKey] ?? 0);
    
    if ($currentQty > 0) {
        $inventory[$qtyKey] = $currentQty - 1;
        
        $tsKey = 'ts_' . $boosterType;
        if (strpos($boosterType, 'overclock') !== false) {
            $tsKey = 'ts_booster_overclock_' . $carId;
        }
        
        $currTs = (int)($inventory[$tsKey] ?? 0);
        if ($currTs < $now) {
            $currTs = $now;
        }
        
        $duration = 0;
        if ($boosterType === 'booster_xtreme_wheels' || $boosterType === 'booster_overclock') {
            $duration = 900;
        } elseif ($boosterType === 'booster_nitro_recharge') {
            $duration = 600;
        } elseif ($boosterType === 'booster_extra_nitro_tank' || $boosterType === 'booster_double_credits') {
            $duration = 7200;
        } else {
            $duration = 3600;
        }
        
        $inventory[$tsKey] = $currTs + $duration;
    }
}

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

file_put_contents($dbFile, json_encode($inventory));

$responseBody = [
    "boosters_sync" => $boostersSyncBlock,
    "metadata" => [
        "career_data_asset_sync" => [
            "body" => [
                "up_to_date" => false,
                "asset_name" => "career_info_data_upd1.6.2a_152",
                "asset_etag" => "8c805c8c85a5502a199f41c704101df33326297ff25d6b98b989008f10784cd2"
            ]
        ]
    ]
];

header('HTTP/1.1 200 OK');
header('Content-Type: text/html; charset=UTF-8');
echo json_encode(["body" => $responseBody]);
exit;
