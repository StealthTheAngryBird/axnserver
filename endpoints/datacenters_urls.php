<?php
  header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = "http://localhost"; <-- REPLACE THIS

$jsonResponse = '{
  "customer_care": "https://helpshift-api.tools.gameloft.com/gold0021",
  "etsv2": "' . $host . '",
  "federation": "' . $host . '",
  "federation-internal": "' . $host . '",
  "game_portal": "' . $host . '",
  "gdid": "' . $host . '",
  "glid": "' . $host . '",
  "marketing_site": "https://201205igp.gameloft.com",
  "master_federation": "' . $host . '",
  "online_connectivity": "' . $host . '",
  "status": "none"
}';

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
?>
