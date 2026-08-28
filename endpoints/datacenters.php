<?php
  header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

$jsonResponse = '[
  {
    "name": "gold0021",
    "status": "active",
    "preferred": true,
    "country_code": "US",
    "_datacenter_id": "gold0021_axp_05"
  }
]';

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
?>
