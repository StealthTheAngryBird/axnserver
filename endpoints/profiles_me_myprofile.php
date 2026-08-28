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
$carId = (string)($_POST['car_id'] ?? 'unknown');
$bpId = (string)($_POST['bp_id'] ?? '0');
$bpCost = (int)($_POST['bp_price'] ?? 0);

$blueprintsReq = [
    '22' => ['22' => 4], '34' => ['34' => 6], '35' => ['35' => 10], '36' => ['36' => 15], '38' => ['38' => 20], '2972' => ['2972' => 25],
    '25' => ['25' => 8], '26' => ['26' => 12], '28' => ['28' => 18], '40' => ['40' => 25], '2449' => ['2449' => 30], '2896' => ['2896' => 35],
    '15' => ['15' => 12], '17' => ['17' => 18], '18' => ['18' => 25], '19' => ['19' => 30], '27' => ['27' => 35], '30' => ['30' => 40], '32' => ['32' => 45], '39' => ['39' => 50], '2451' => ['2451' => 55],
    '8' => ['8' => 20], '11' => ['11' => 25], '12' => ['12' => 30], '13' => ['13' => 35], '52' => ['52' => 40], '2366' => ['2366' => 45], '2673' => ['2673' => 50], '2739' => ['2739' => 55], '2751' => ['2751' => 60], '2850' => ['2850' => 65], '3011' => ['3011' => 70],
    '7' => ['7' => 25], '23' => ['23' => 30], '43' => ['43' => 35], '44' => ['44' => 40], '47' => ['47' => 45], '50' => ['50' => 50], '53' => ['53' => 55], '54' => ['54' => 60], '55' => ['55' => 65], '56' => ['56' => 70], '2365' => ['2365' => 75], '2368' => ['2368' => 80], '2955' => ['2955' => 85], '3043' => ['3043' => 90]
];

$syncCards = new stdClass();

if (isset($blueprintsReq[$carId])) {
    $reqCards = $blueprintsReq[$carId];
    foreach ($reqCards as $cardId => $amount) {
        $currentAmount = (int)($inventory[$cardId] ?? 0);
        $inventory[$cardId] = max(0, $currentAmount - $amount);
        $syncCards->{(string)$cardId} = (int)$inventory[$cardId];
    }
}

$inventory[$carId] = 1;

$update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
$update->execute([json_encode($inventory), $userId]);
$pdo->commit(); 

$clientSync = (int)($_POST['prokits_inventory_sync_key'] ?? 0);
$syncKey = (string)($clientSync > 0 ? $clientSync + 2 : time());

$responseBody = [
    "item" => $carId,
    "prokits_inventory_partial_sync" => [
        "body" => [
            "cards" => empty((array)$syncCards) ? new stdClass() : $syncCards,
            "card_boxes" => new stdClass(),
            "boxes_rank_points" => (int)($inventory['boxes_rank_points'] ?? 0),
            "sync_key" => $syncKey
        ]
    ]
];

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(["body" => $responseBody]);
exit;