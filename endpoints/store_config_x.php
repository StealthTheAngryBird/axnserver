<?php
header('HTTP/1.1 302 Found');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: Date');

header('Location: https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/store_config_x_1770015284.812788');

$resp = "302 Found : Object has several resources -- see URI list";

header('Content-Length: ' . strlen($resp));
echo $resp;
?>
