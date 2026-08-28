<?php
header('HTTP/1.1 200 OK');
header('Content-Type: text/html; charset=UTF-8');
header('X-Powered-By: PHP/5.6.40');
header('Vary: Accept-Encoding');

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

$jsonResponse = '{
  "body": {
    "metadata": {
      "daily_missions_state_sync": {
        "body": {
          "next_day_timestamp": 1774818000,
          "today": 0,
          "missions": {
            "41001": {
              "id": 41001,
              "condition": "UpgradeCars",
              "conditionValue": 1,
              "subcondition": 0,
              "subconditionType": "number_of_times",
              "text": "STR_DAILY_MISSION_UPGRADE_CARS",
              "progress": 0,
              "claimed": 0,
              "start_timestamp": 0,
              "end_timestamp": 1774818000,
              "reward_type": "Credits",
              "reward_subtype": "",
              "reward_amount": 10000
            },
            "41002": {
              "id": 41002,
              "condition": "PurchaseBoxInShop",
              "conditionValue": 3,
              "subcondition": 0,
              "subconditionType": "number_of_times",
              "text": "STR_DAILY_MISSION_PURCHASE_IN_SHOP",
              "progress": 0,
              "claimed": 0,
              "start_timestamp": 0,
              "end_timestamp": 1774818000,
              "reward_type": "Crowns",
              "reward_subtype": "",
              "reward_amount": 5
            }
          },
          "streak_reward_type": "Credits",
          "streak_reward_amount": 17500,
          "streak_reward_claimed": 0,
          "weekly_streak_state": {
            "event_id": "",
            "next_week_timestamp": 1774818000,
            "current_week": 1,
            "mission_finished": 0,
            "milestone_prize": {},
            "rewards_claimed": []
          },
          "unclaimed_rewards": []
        }
      },
      "request_rate_asset_sync": {
        "body": {
          "up_to_date": false,
          "asset_name": "request_rate_data_upd1.5.6a_29",
          "asset_etag": "26eaa3916055fcd667d5cd0cf58dbd2263db44fcb735a75b02178a16f6567559"
        }
      }
    }
  }
}';

echo $jsonResponse;

ob_end_flush();
?>
