<?php
  header('HTTP/1.1 404 Not Found');
header('Content-Type: text/plain; charset=UTF-8');

$resp = "404 Not Found : Asset texts.jpk not found";

header('Content-Length: ' . strlen($resp));
echo $resp;
?>
