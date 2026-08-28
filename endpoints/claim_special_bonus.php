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

$bonusDay = (int)($_POST['bonus_day'] ?? 1);
$eventId = $_POST['event_id'] ?? 'a393c97e-432a-11ee-abd3-b8ca3a60b3cc';
$isNewbie = $_POST['is_newbie'] ?? 1;

$currentBalance = 9183;
$rewardAmount = 7000;
$newBalance = $currentBalance + $rewardAmount;

$nextBonusDay = $bonusDay + 1;
$nextTimestamp = time() + 86400; 
$endDate = time() + (30 * 86400);

$isNewbieStr = $isNewbie ? 'true' : 'false';

$jsonResponse = '{
  "body": {
    "credits_partial_sync": {
      "body": {
        "balance": ' . $newBalance . '
      }
    },
    "metadata": {
      "special_login_bonus_state_sync": {
        "body": {
          "' . $eventId . '": {
            "end_date": ' . $endDate . ',
            "next_special_login_bonus_timestamp": ' . $nextTimestamp . ',
            "next_bonus_day": ' . $nextBonusDay . ',
            "is_newbie": ' . $isNewbieStr . '
          }
        }
      },
      "career_data_asset_sync": {
        "body": {
          "up_to_date": false,
          "asset_name": "career_info_data_upd1.5.6a_152",
          "asset_etag": "8c805c8c85a5502a199f41c704101df33326297ff25d6b98b989008f10784cd2"
        }
      }
    }
  }
}';

echo $jsonResponse;

ob_end_flush();
?>
