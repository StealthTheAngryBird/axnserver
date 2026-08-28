<?php
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Expose-Headers: Date');
header('Server: CherryPy/18.10.0');
header('Vary: Accept-Encoding');
header('Content-Type: text/html;charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    header('HTTP/1.1 200 OK');
    
    $dataLength = isset($_POST['data']) ? strlen($_POST['data']) : 1411;
    
    echo "Data of length " . $dataLength . " saved in namespace axp for user me under public_write key _sem_events";

} else {
    
    header('HTTP/1.1 404 Not Found');
    
    $errorText = "404 Not Found : Key _sem_events not found in namespace axp for user me" . str_repeat(" ", 329);
    echo $errorText;
}
?>
