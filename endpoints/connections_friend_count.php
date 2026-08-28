<?php
header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

$jsonResponse = '{"count": 0}';

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
?>
