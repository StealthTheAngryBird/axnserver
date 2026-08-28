<?php

header('HTTP/1.1 200 OK');
header('Accept-Ranges: bytes');
header('Content-Type: text/plain'); 
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,HEAD');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 3000');
header('Connection: keep-alive');

$basename = basename($filename);
$filePath = __DIR__ . '/../assets/' . $basename;

if (file_exists($filePath)) {
    $filesize = filesize($filePath);
    header('Content-Length: ' . $filesize);
    
    $etag = md5_file($filePath);
    header('ETag: "' . $etag . '"');
    
    readfile($filePath);
} else {
    header("HTTP/1.1 404 Not Found");
    echo "CDN File not found.";
    error_log("Missing CDN asset: " . $basename);
}
?>
