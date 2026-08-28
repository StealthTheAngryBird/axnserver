<?php
header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');

$jsonResponse = '{"hash":"a242ff19f07e07afd085d29c6952e3799437671ee9019c7a82152e911a3ab825","size":76346}';

header('Content-Length: ' . strlen($jsonResponse));
echo $jsonResponse;
?>
