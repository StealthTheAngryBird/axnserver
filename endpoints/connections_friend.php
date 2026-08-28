<?php
  header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

$jsonResponse = '[
  {
    "id": 1,
    "account": "00000000-0000-0000-0000-000000000000",
    "alias": "ServerBot",
    "game": "mygame",
    "status": "online"
  }
]';

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
?>
