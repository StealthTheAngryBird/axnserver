<?php
  error_reporting(0);
ini_set('display_errors', 0);

$jsonFilePath = __DIR__ . '/configs_users_me.json';

if (file_exists($jsonFilePath)) {
    $jsonResponse = file_get_contents($jsonFilePath);
    
    $jsonResponse = preg_replace('/"expiry"\s*:\s*"[^"]+"/', '"expiry":"9999-12-31T17:00:00Z"', $jsonResponse);
} else {
    $jsonResponse = '{"groupware":{"events":{"advertisement":[],"participation":[]}}}';
}

$etag = '"' . md5($jsonResponse) . '"';

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    header("HTTP/1.1 304 Not Modified");
    header("Cache-Control: public, max-age=86400");
    header("ETag: $etag");
    exit;
}

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    ob_start("ob_gzhandler");
} else {
    ob_start();
}

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');
header('Connection: close');
header('Cache-Control: public, max-age=86400');
header("ETag: $etag");

echo $jsonResponse;
ob_end_flush();
exit;
