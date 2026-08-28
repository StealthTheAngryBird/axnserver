<?php

header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');

$filePath = __DIR__ . '/GameOptions.bin';

if (file_exists($filePath)) {
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/octet-stream');
    
    $fileSize = filesize($filePath);
    header('Content-Length: ' . $fileSize);
    
    readfile($filePath);
} else {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=UTF-8');
    $resp = "Error: GameOptions.bin not found on local server!";
    header('Content-Length: ' . strlen($resp));
    echo $resp;
}
?>
