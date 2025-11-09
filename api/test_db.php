<?php
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    $db = getDB();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Conexión a BD exitosa',
        'session_status' => session_status(),
        'session_id' => session_id()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>