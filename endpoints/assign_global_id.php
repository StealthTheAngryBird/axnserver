<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../db.php';

header('HTTP/1.1 200 OK');
header('Content-Type: text/plain; charset=UTF-8');

$hardwareId = $_POST['device_id'] ?? $_GET['device_id'] ?? '';

if (empty($hardwareId)) {
    if (!empty($_SERVER['HTTP_GL_GLLIVE_DEVICE_ID'])) {
        $hardwareId = $_SERVER['HTTP_GL_GLLIVE_DEVICE_ID'];
    } elseif (!empty($_SERVER['HTTP_X_DEVICE_ID'])) {
        $hardwareId = $_SERVER['HTTP_X_DEVICE_ID'];
    }
}

if (empty($hardwareId)) {
    $hardwareId = md5($_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

$globalId = '';

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT global_id FROM user_devices WHERE hardware_id = ?");
    $stmt->execute([$hardwareId]);
    $row = $stmt->fetch();

    if ($row) {
        $globalId = $row['global_id'];
    } else {
        $globalId = '7174' . mt_rand(10000, 99999) . mt_rand(10000000, 99999999);
        
        $ins = $pdo->prepare("INSERT INTO user_devices (hardware_id, global_id) VALUES (?, ?)");
        $ins->execute([$hardwareId, $globalId]);
    }
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $globalId = '7174' . mt_rand(10000, 99999) . mt_rand(10000000, 99999999);
}

header('Content-Length: ' . strlen($globalId));
echo $globalId;
exit;