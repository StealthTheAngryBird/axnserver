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

$uniqueId = $reqData['anon_credential'] ?? '';
if (empty($uniqueId) || $uniqueId === 'anonymous:guest') {
    $token = $reqData['access_token'] ?? $_GET['access_token'] ?? '';
    if ($token) {
        $parts = explode(',', urldecode($token));
        $uniqueId = $parts[4] ?? ''; 
    }
}
if (empty($uniqueId) || $uniqueId === 'anonymous:guest') {
    $uniqueId = $reqData['device_id'] ?? 'default_user';
}

$userId = substr(md5($uniqueId), 0, 8);

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
$seasonId = (string)($_POST['season_id'] ?? '1');

$seasonRewards = [
    '1' => ['credits' => 5000,  'crowns' => 5],
    '2' => ['credits' => 18750, 'crowns' => 125],
    '3' => ['credits' => 20625, 'crowns' => 185],
    '4' => ['credits' => 22500, 'crowns' => 240],
    '5' => ['credits' => 24375, 'crowns' => 285],
    '6' => ['credits' => 26250, 'crowns' => 335],
    '7' => ['credits' => 28125, 'crowns' => 365],
    '8' => ['credits' => 30000, 'crowns' => 395],
    '9' => ['credits' => 31875, 'crowns' => 425],
    '10' => ['credits' => 33750, 'crowns' => 455],
    '11' => ['credits' => 35625, 'crowns' => 485],
    '12' => ['credits' => 37500, 'crowns' => 515],
    '13' => ['credits' => 39375, 'crowns' => 545],
    '14' => ['credits' => 41250, 'crowns' => 575],
    '15' => ['credits' => 43125, 'crowns' => 605],
    '16' => ['credits' => 60000, 'crowns' => 635],
    '17' => ['credits' => 63000, 'crowns' => 665],
    '18' => ['credits' => 65000, 'crowns' => 695],
    '19' => ['credits' => 67500, 'crowns' => 725],
    'default' => ['credits' => 2000, 'crowns' => 2]
];

$reward = $seasonRewards[$seasonId] ?? $seasonRewards['default'];
$rewardCredits = $reward['credits'];
$rewardCrowns = $reward['crowns'];

$currentCredits = (int)($inventory['credits'] ?? 0);
$currentCrowns = (int)($inventory['crowns'] ?? 0);

$inventory['credits'] = $currentCredits + $rewardCredits;
$inventory['crowns'] = $currentCrowns + $rewardCrowns;

$claimedSeasons = $inventory['claimed_seasons'] ?? [];
if (!in_array($seasonId, $claimedSeasons)) {
    $claimedSeasons[] = $seasonId;
}
$inventory['claimed_seasons'] = $claimedSeasons;

$update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
$update->execute([json_encode($inventory), $userId]);

$pdo->commit();

$syncKey = (string)time();

$responseBody = [
    "crowns_partial_sync" => [
        "body" => ["balance" => $inventory['crowns']]
    ],
    "credits_partial_sync" => [
        "body" => ["balance" => $inventory['credits']]
    ],
    "career_progression_partial_sync" => [
        "body" => [
            "up_to_date" => false,
            "season_complete_rewards_claimed" => array_values($claimedSeasons), 
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
];

$jsonResponse = json_encode(["body" => $responseBody]);

ob_clean();
header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo $jsonResponse;
exit;