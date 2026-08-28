<?php
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

if (strpos($path, '/assign_global_id') !== false) {
    require 'endpoints/assign_global_id.php';
}
elseif (strpos($path, '/connectionstatus') !== false) {
    require 'endpoints/connectionstatus.php';
}
elseif (strpos($path, '/datacenters/gold0021/urls') !== false) {
    require 'endpoints/datacenters_urls.php';
}
elseif (strpos($path, '/datacenters') !== false) {
    require 'endpoints/datacenters.php';
}

elseif (preg_match('#/v1/users/(.+)/authorize#', $path)) {
    require 'endpoints/authorize.php';
}
elseif (strpos($path, '/v1/users/me/credentials') !== false) {
    require 'endpoints/users_me_credentials.php';
}
elseif (strpos($path, '/v1/users/me') !== false) {
    require 'endpoints/users_me.php';
}

elseif (strpos($path, '/v1/accounts/me/connections/friend/count') !== false) {
    require 'endpoints/connections_friend_count.php';
}
elseif (strpos($path, '/v1/accounts/me/connections/friend') !== false) {
    require 'endpoints/connections_friend.php';
}

elseif (strpos($path, '/v1/accounts/me/import/profile') !== false || strpos($path, '/v1/accounts/me/import/friends') !== false) {
    require 'endpoints/accounts_import.php';
}
elseif (strpos($path, '/v1/alerts/subscribe') !== false) {
    require 'endpoints/alerts_subscribe.php';
}
elseif (strpos($path, '/v1/leaderboards/') !== false) {
    require 'endpoints/leaderboards.php';
}
elseif (strpos($path, '/v1/messages/secured/me') !== false) {
    require 'endpoints/messages.php';
}

elseif (strpos($path, '/v1/profiles/me/myprofile') !== false) {
    require 'endpoints/profiles_me_myprofile.php';
}
elseif (strpos($path, '/v1/configs/users/me') !== false) {
    require 'endpoints/configs_users_me.php';
}
elseif (strpos($path, '/v1/games/mygame/alias') !== false) {
    require 'endpoints/mygame_alias.php';
}
elseif (strpos($path, '/v1/devices/mydevice') !== false) {
    require 'endpoints/devices_mydevice.php';
}

elseif (preg_match('#^/v1/assets/([^/]+)/GameOptions/metadata$#', $path)) {
    require 'endpoints/gameoptions_metadata.php';
}
elseif (preg_match('#^/v1/assets/([^/]+)/GameOptions$#', $path)) {
    require 'endpoints/gameoptions.php';
}
elseif (strpos($path, '/store_config_x') !== false) {
    require 'endpoints/store_config_x.php';
}
elseif (strpos($path, '/texts.jpk/metadata') !== false) {
    require 'endpoints/texts_metadata.php';
}

elseif (strpos($path, 'sync_all.php') !== false) {
    require 'endpoints/sync_all.php';
}
elseif (strpos($path, 'sem_events') !== false) {
    require 'endpoints/data_me_sem_events.php';
    exit;
}
elseif (strpos($path, 'configs') !== false) {
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    echo '{}';
    exit;
}
elseif (strpos($path, 'pre_career_race.php') !== false) {
    require 'endpoints/pre_career_race.php';
}
elseif (strpos($path, 'buy_item.php') !== false) {
    require 'endpoints/buy_item.php';
}
elseif (strpos($path, 'post_event_score.php') !== false) {
    require 'endpoints/post_event_score.php';
}
elseif (strpos($path, 'upgrade_car.php') !== false) {
    require 'endpoints/upgrade_car.php';
    exit;
}
elseif (strpos($path, 'craft_car.php') !== false) {
    require 'endpoints/craft_car.php';
    exit;
}
elseif (strpos($path, 'open_cardboxes.php') !== false) {
    require 'endpoints/open_cardboxes.php';
    exit;
}
elseif (strpos($path, 'activate_booster.php') !== false) {    
    require 'endpoints/activate_booster.php';
    exit;
}
elseif (strpos($path, 'claim_season_completion_reward.php') !== false) {    
    require 'endpoints/claim_season_completion_reward.php';
    exit;
}
elseif (strpos($path, 'claim_one_time_reward.php') !== false) {
    require 'endpoints/claim_one_time_reward.php';
    exit;
}
elseif (strpos($path, '/binary') !== false) {
    require 'endpoints/binary.php';
}

else {
    error_log("Unknown endpoint requested: " . $path);
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    echo '{"body": {}}';
    exit;
}
?>