<?php
/**
 * API de Estadísticas - PetZone
 * Archivo: api/estadisticas.php
 */

require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM estadisticas_dashboard");
    $stats = $stmt->fetch();
    
    jsonResponse(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>

<?php
/**
 * API de Categorías - PetZone
 * Archivo: api/categorias.php
 */

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY orden ASC");
    $categorias = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'categorias' => $categorias]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>

<?php
/**
 * API de Actividad - PetZone
 * Archivo: api/actividad.php
 */

require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT a.*, u.nombre_completo 
        FROM actividad_admin a
        INNER JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY a.fecha DESC
        LIMIT 10
    ");
    $stmt->execute();
    $actividades = $stmt->fetchAll();
    
    // Formatear fechas
    foreach ($actividades as &$act) {
        $act['fecha'] = date('d/m/Y H:i', strtotime($act['fecha']));
    }
    
    jsonResponse(['success' => true, 'actividades' => $actividades]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>