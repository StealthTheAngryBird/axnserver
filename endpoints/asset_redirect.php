<?php
header('HTTP/1.1 302 Found');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: Date');
header('Content-Type: text/html; charset="UTF-8"');
header('Server: Whiskyslice/1.2.38');
header('Connection: keep-alive');

$assetsMap = [
    'maintenance_data_upd1.5.6a_23' => [
        'hash' => '3f0bff1ecdd77cd4d458ce2136afe0c92464c00d33c12f17adedcf600d9cd255',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/maintenance_data_upd1.5_1770015283.2818265.6a_23'
    ],
    'prokits_data_upd1.5.6a_49' => [
        'hash' => 'a7dcc7708d16e93971d9f86e4ac5a823502da9a49eefc017968acde246480f32',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/prokits_data_upd1.5_1770015285.4951046.6a_49'
    ],
    'request_rate_data_upd1.5.6a_29' => [
        'hash' => '26eaa3916055fcd667d5cd0cf58dbd2263db44fcb735a75b02178a16f6567559',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/request_rate_data_upd1.5_1770015285.0912476.6a_29'
    ]
];

if (isset($assetsMap[$assetName])) {
    $assetInfo = $assetsMap[$assetName];
    
    header('Asset-Hash: ' . $assetInfo['hash']);
    header('Asset_hash: ' . $assetInfo['hash']);
    
    header('Location: ' . $assetInfo['url']);
    
    echo "302 Found : Object has several resources -- see URI list";
} else {
    header("HTTP/1.1 404 Not Found");
    echo "Asset not found in emulator database.";
    error_log("Missing asset requested: " . $assetName);
}
$assetsMap = [
    'maintenance_data_upd1.5.6a_23' => [
        'hash' => '3f0bff1ecdd77cd4d458ce2136afe0c92464c00d33c12f17adedcf600d9cd255',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/maintenance_data_upd1.5_1770015283.2818265.6a_23'
    ],
    'prokits_data_upd1.5.6a_49' => [
        'hash' => 'a7dcc7708d16e93971d9f86e4ac5a823502da9a49eefc017968acde246480f32',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/prokits_data_upd1.5_1770015285.4951046.6a_49'
    ],
    'request_rate_data_upd1.5.6a_29' => [
        'hash' => '26eaa3916055fcd667d5cd0cf58dbd2263db44fcb735a75b02178a16f6567559',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/request_rate_data_upd1.5_1770015285.0912476.6a_29'
    ],
    'store_config_x' => [
        'hash' => 'ab4c0f345e3463e54d5c46adda13bdc6f4a1e50a11c83efa67dccb2c0861d030',
        'url'  => 'https://iris07-gold-ssl.gameloft.com/axp/axp:5476:83949:1.5.6a:android:googleplay/store_config_x_1770015284.812788'
    ]
];
?>
