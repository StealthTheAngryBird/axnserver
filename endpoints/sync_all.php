<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/db.php'; 

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
$pdo->beginTransaction();
$stmt = $pdo->prepare("SELECT inventory_data FROM user_profiles WHERE user_id = ? FOR UPDATE");
$stmt->execute([$userId]);
$row = $stmt->fetch();
$inventory = $row ? json_decode($row['inventory_data'], true) : [];
if (!$inventory) $inventory = [];

$now = time();
$inventoryChanged = false;

$overclockObj = new stdClass();
$hasOverclock = false;

foreach ($inventory as $k => $v) {
    if (strpos($k, 'ts_booster_overclock_') === 0) {
        if ($v > $now) {
            $cId = str_replace('ts_booster_overclock_', '', $k);
            $overclockObj->$cId = (int)$v;
            $hasOverclock = true;
        } else {
            unset($inventory[$k]); 
            $inventoryChanged = true;
        }
    }
}
if (!$hasOverclock) $overclockObj = new stdClass();

$activeBoosters = false;
foreach (['booster_double_credits', 'booster_nitro_recharge', 'booster_extra_nitro_tank', 'booster_xtreme_wheels'] as $b) {
    if (($inventory['ts_' . $b] ?? 0) > $now) {
        $activeBoosters = true;
    } elseif (isset($inventory['ts_' . $b])) {
        unset($inventory['ts_' . $b]); 
        $inventoryChanged = true;
    }
}
if ($hasOverclock) $activeBoosters = true;

if ($inventoryChanged) {
    $update = $pdo->prepare("UPDATE user_profiles SET inventory_data = ? WHERE user_id = ?");
    $update->execute([json_encode($inventory), $userId]);
}
$pdo->commit();

$cardsSync = new stdClass();
foreach ($inventory as $k => $v) {
    if (is_numeric($k) && (int)$v > 0) $cardsSync->{$k} = (int)$v;
}
$levelupsDB = isset($inventory['levelups']) && is_array($inventory['levelups']) ? $inventory['levelups'] : new stdClass();



$baseJsonString = '{"prokits_inventory_full_sync":{"body":{"up_to_date":true,"cards":{"34":0},"card_boxes":{},"boxes_rank_points":0,"last_open_box":0,"free_boxes_timestamps":{"1":0,"119":0,"120":0,"121":0,"122":0,"123":0},"sync_key":"1775051014"}},"prokits_asset_sync":{"body":{"up_to_date":true,"asset_name":"prokits_data_upd1.5.6a_49","asset_etag":"a7dcc7708d16e93971d9f86e4ac5a823502da9a49eefc017968acde246480f32"}},"whats_new_asset_sync":{"body":{"up_to_date":true,"asset_name":"whats_new_data_upd1.5.6a_","asset_etag":"66cb040540006e8c9d3fdfde3abce73be200c0eaf026322ba59bf524372bfefa"}},"prokits_car_levelups_full_sync":{"body":{"up_to_date":true,"levelups":{},"upgrade_tutorial_done":0,"sync_key":"0"}},"maintenance_asset_sync":{"body":{"up_to_date":true}},"maintenance_state_full_sync":{"body":{"up_to_date":true}},"career_progression_full_sync":{"body":{"up_to_date":true,"events":{},"season_complete_rewards_claimed":[],"sync_key":"0"}},"server_items_full_sync":{"body":{"up_to_date":true}},"energy_full_sync":{"body":{"up_to_date":true,"regeneration_config":[600,600,600,600,600,600,600,600,600,600,600,600,600,600],"energy_status":{"balance":14,"last_sync_ts":1775050877,"current_ts":1775051298,"seconds_until_next_level":null,"total_seconds_needed_for_next_level":null},"available_refills":0,"sync_key":"1775050877_1_0"}},"fuses_full_sync":{"body":{"up_to_date":true,"max_fuses":3,"fuses_regeneration_config":0,"fuses_status":{"balance":3,"last_sync_ts":1775050877,"current_ts":1775051298},"available_refills":0,"sync_key":"1775050877_1_0"}},"crowns_full_sync":{"body":{"balance":10}},"credits_full_sync":{"body":{"balance":8000}},"black_market_full_sync":{"body":{"cards":{},"step":0,"refresh":0,"skips":0,"sync_key":1775050877,"current_day":3,"current_event_id":"8a2e5892-298b-11f1-9663-b8ca3a60b3cc","bought_cards_in_today":{"2":5,"2896":5,"4":5,"52":5,"53":5},"current_pool_index":0,"current_max_skip_number":2,"has_bought_today":true}},"boosters_sync":{"body":{"balance":{"booster_double_credits":0,"booster_nitro_recharge":0,"booster_extra_nitro_tank":0,"booster_xtreme_wheels":0,"booster_overclock":{}},"boosters_activation_end_timestamp":{"booster_double_credits":0,"booster_nitro_recharge":0,"booster_extra_nitro_tank":0,"booster_xtreme_wheels":0,"booster_overclock":{}}}},"daily_login_bonus_state_sync":{"body":{"next_daily_login_reward_timestamp":0,"next_daily_login_reward_day":1,"monthly_milestone_reward":[{"day":7,"has_claimed":true},{"day":14,"has_claimed":true},{"day":21,"has_claimed":true},{"day":28,"has_claimed":true}],"claimed_one_time_rewards":["_","TutorialFirstCarReward"],"monthly_end_date_timestamp":1777593540,"daily_login_reward_event_id":"991e4b96-2be3-11f1-ae59-b8ca3a60b3cc","total_login_day":0,"credit_scale_factor":0,"crown_scale_factor":0,"tool_scale_factor":1,"blueprint_scale_factor":1,"box_scale_factor":1}},"special_login_bonus_state_sync":{"body":{}},"daily_missions_state_sync":{"body":{"next_day_timestamp":1775077200,"today":0,"missions":{"41001":{"id":41001,"condition":"UpgradeCars","conditionValue":1,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_UPGRADE_CARS","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1775077200,"reward_type":"Credits","reward_subtype":"","reward_amount":10000},"41002":{"id":41002,"condition":"PurchaseBoxInShop","conditionValue":3,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_PURCHASE_IN_SHOP","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1775077200,"reward_type":"Crowns","reward_subtype":"","reward_amount":5},"41006":{"id":41006,"condition":"PlayAnyRace","conditionValue":3,"subcondition":3,"subconditionType":"take_place_or_better","text":"STR_DAILY_MISSION_PLAY_ANY_RACE_AT_LEAST_POSITION","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1775077200,"reward_type":"Credits","reward_subtype":"","reward_amount":10000},"41007":{"id":41007,"condition":"SpendCredits","conditionValue":3000,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_SPEND_CREDITS","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1775077200,"reward_type":"Crowns","reward_subtype":"","reward_amount":5},"41009":{"id":41009,"condition":"ClaimFreeBox","conditionValue":1,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_CLAIM_FREE_BOX","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1775077200,"reward_type":"Booster","reward_subtype":"NitroRecharge","reward_amount":1},"41012":{"id":41012,"condition":"PurchaseInBlackMarket","conditionValue":1,"subcondition":0,"subconditionType":"number_of_times","text":"STR_DAILY_MISSION_PURCHASE_IN_BM","progress":0,"claimed":0,"start_timestamp":0,"end_timestamp":1775077200,"reward_type":"Crowns","reward_subtype":"","reward_amount":5}},"streak_reward_type":"Credits","streak_reward_subtype":"(null)","streak_reward_amount":20000,"streak_reward_claimed":0,"streak_extra_reward_type":"Credits","streak_extra_reward_subtype":"(null)","streak_extra_reward_amount":15000,"streak_extra_reward_claimed":0,"weekly_streak_state":{"event_id":"f1d79fb4-017a-11f1-b73e-b8ca3a60b3cc","next_week_timestamp":1775422800,"current_week":1,"mission_finished":0,"milestone_prize":{"12":{"amount":100,"sub_type":"-","type":"Crowns"},"18":{"amount":7,"sub_type":"2850","type":"Card"},"24":{"amount":8,"sub_type":"2850","type":"Card"},"30":{"amount":2,"sub_type":"154","type":"CardBox"},"6":{"amount":50000,"sub_type":"-","type":"Credits"}},"rewards_claimed":[]},"unclaimed_rewards":[]}},"world_series_sync":{"body":{"win_streak":0,"next_reward":null,"season_infor":{"close_time":1777589999,"end_time":1777593599,"season":"04-2026","start_time":1775001600},"previous_season":{},"season_reward":[],"claim_historic":{"4":"0","6":"0","8":"0","10":"0"},"elo_rating":1000,"level":1}},"tle_sync":{"body":{"tournaments":{},"sync_key":0,"up_to_date":true}},"rad_sync":{"body":{"series":{},"sync_key":0,"up_to_date":true}},"se_sync":{"body":{"seasonal_events":{},"sync_key":0,"up_to_date":true}},"pw_sync":{"body":{"phantom_weeks":{},"sync_key":0,"up_to_date":true}},"career_data_asset_sync":{"body":{"up_to_date":true,"asset_name":"career_info_data_upd1.5.6a_152","asset_etag":"8c805c8c85a5502a199f41c704101df33326297ff25d6b98b989008f10784cd2"}},"ad_rewards_full_sync":{"body":{"up_to_date":true,"ad_rewards_status":{"ads_Crowns":0,"ads_Credits":0,"ads_Double_Credits":0,"ads_Tickets":0,"ads_Maintenance":0,"ads_Fuses":0,"ads_LevelUp":0,"ads_ExtraCard":0,"ads_CardBox_":{},"ads_ExtraCard_":{}},"current_car":22}},"metadata":{"career_data_asset_sync":{"body":{"up_to_date":true,"asset_name":"career_info_data_upd1.5.6a_152","asset_etag":"8c805c8c85a5502a199f41c704101df33326297ff25d6b98b989008f10784cd2"}}}}';

$responseBody = json_decode($baseJsonString, true);



$responseBody['credits_full_sync']['body']['balance'] = (int)($inventory['credits'] ?? 8000);
$responseBody['crowns_full_sync']['body']['balance'] = (int)($inventory['crowns'] ?? 10);

$responseBody['prokits_inventory_full_sync']['body']['cards'] = empty((array)$cardsSync) ? new stdClass() : $cardsSync;
$responseBody['prokits_inventory_full_sync']['body']['boxes_rank_points'] = (int)($inventory['boxes_rank_points'] ?? 0);

$responseBody['prokits_car_levelups_full_sync']['body']['levelups'] = $levelupsDB;

if ($activeBoosters) {
    $responseBody['boosters_sync']['body']['boosters_activation_end_timestamp'] = [
        "booster_double_credits" => (int)($inventory['ts_booster_double_credits'] ?? 0),
        "booster_nitro_recharge" => (int)($inventory['ts_booster_nitro_recharge'] ?? 0),
        "booster_extra_nitro_tank" => (int)($inventory['ts_booster_extra_nitro_tank'] ?? 0),
        "booster_xtreme_wheels" => (int)($inventory['ts_booster_xtreme_wheels'] ?? 0),
        "booster_overclock" => $overclockObj
    ];
}

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
echo json_encode(["body" => $responseBody]);
exit;