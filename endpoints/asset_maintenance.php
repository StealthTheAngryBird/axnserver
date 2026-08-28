<?php
header('HTTP/1.1 302 Found');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: Date');
header('Asset-Hash: 3f0bff1ecdd77cd4d458ce2136afe0c92464c00d33c12f17adedcf600d9cd255');
header('Asset_hash: 3f0bff1ecdd77cd4d458ce2136afe0c92464c00d33c12f17adedcf600d9cd255');
header('Content-Type: text/html; charset="UTF-8"');
header('Server: Whiskyslice/1.2.38');
header('Connection: keep-alive');

$redirectUrl = "https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/maintenance_data_upd1.5_1770015283.2818265.6a_23";

header('Location: ' . $redirectUrl);

echo "302 Found : Object has several resources -- see URI list";
?>
