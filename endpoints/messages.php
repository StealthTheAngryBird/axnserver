<?php
  header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

$jsonResponse = '{"items":[]}';

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
?>
