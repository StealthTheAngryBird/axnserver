<?php

header('HTTP/1.1 200 OK');
header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Connection: close');
header('Strict-Transport-Security: max-age=15724800; includeSubDomains');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

header_remove('Content-Type');

header('Content-Length: 0');
exit;
?>
