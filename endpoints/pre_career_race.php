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
$carId = (string)($_POST['car_id'] ?? '22');

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

$syncKey = (string)time();

$response = [
    "body" => [
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

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($response);
ob_end_flush();
exit;