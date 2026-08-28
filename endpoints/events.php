<?php
header('HTTP/1.1 200 OK');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Expose-Headers: Date');
header('Server: Whiskyslice/1.2.38');
header('Vary: Accept-Encoding');
if (isset($_GET['event_ids']) || empty($eventId)) {
    
    $jsonFilePath = __DIR__ . '/events.json';
    
    if (file_exists($jsonFilePath)) {
        readfile($jsonFilePath);
    } else {
        echo '[]';
        error_log("events.json is missing!");
    }

} else {
    $jsonFilePath = __DIR__ . '/daily_login_event.json';
    
    if (file_exists($jsonFilePath)) {
        readfile($jsonFilePath);
    } else {
        echo '{
          "name": "Fallback_Event",
          "category": "sem_axp",
          "status": "started",
          "id": "' . htmlspecialchars($eventId) . '"
        }';
    }
}
?>
