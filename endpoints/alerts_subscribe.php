<?php
  header('HTTP/1.1 200 OK');
header('Content-Type: text/event-stream; charset=utf-8');
header('Connection: close');
echo "retry: 60000\n\n";
?>
