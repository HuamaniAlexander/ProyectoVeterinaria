<?php
/**
 * Controlador de Citas - PetZone
 * Maneja las solicitudes HTTP y usa CitasModelo
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/citasModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $requestData['action'] ?? $_POST['action'] ?? 'list';

$citasModelo = new CitasModelo();

try {
    switch($action) {
        case 'list':
            // Solo admin puede listar
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleList($citasModelo);
            break;
            
        case 'get':
            // Solo admin puede ver detalle
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleGet($citasModelo);
            break;
            
        case 'create':
            // Público - crear cita desde formulario
            handleCreate($citasModelo);
            break;
            
        case 'update':
            // Solo admin puede actualizar
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleUpdate($citasModelo);
            break;
            
        case 'delete':
            // Solo admin puede eliminar
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleDelete($citasModelo);
            break;
            
        case 'update_estado':
            // Solo admin puede cambiar estado
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleUpdateEstado($citasModelo);
            break;
            
        case 'stats':
            // Estadísticas para dashboard
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleStats($citasModelo);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("CITAS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()], 500);
}

/**
 * Listar todas las citas (Admin)
 */
function handleList($modelo) {
    $filtro = $_GET['filtro'] ?? 'todas';
    $busqueda = $_GET['busqueda'] ?? '';
    $limite = (int)($_GET['limite'] ?? 50);
    $pagina = (int)($_GET['pagina'] ?? 1);
    $offset = ($pagina - 1) * $limite;
    
    $citas = $modelo->listar($filtro, $busqueda, $limite, $offset);
    $total = $modelo->contarTotal($filtro, $busqueda);
    
    if ($citas !== false) {
        jsonResponse([
            'success' => true, 
            'citas' => $citas,
            'total' => $total,
            'pagina' => $pagina,
            'totalPaginas' => ceil($total / $limite)
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar citas'], 500);
    }
}

/**
 * Obtener una cita específica (Admin)
 */
function handleGet($modelo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $cita = $modelo->obtenerPorId($id);
    
    if ($cita) {
        jsonResponse(['success' => true, 'cita' => $cita]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Cita no encontrada'], 404);
    }
}

/**
 * Crear nueva cita (Público - Formulario)
 */
function handleCreate($modelo) {
    // Obtener datos del POST o JSON
    $data = $_POST;
    if (empty($data)) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    $nombre = sanitize($data['nombre'] ?? '');
    $correo = sanitize($data['correo'] ?? '');
    $telefono = sanitize($data['telefono'] ?? '');
    $servicio = sanitize($data['servicio'] ?? '');
    $mensaje = sanitize($data['mensaje'] ?? '');
    
    // Validaciones
    if (empty($nombre) || empty($correo) || empty($telefono) || empty($servicio)) {
        jsonResponse(['success' => false, 'message' => 'Todos los campos son requeridos excepto el mensaje'], 400);
    }
    
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Correo electrónico inválido'], 400);
    }
    
    // Generar código único
    $codigoCita = $modelo->generarCodigoCita();
    
    $datos = [
        'codigo_cita' => $codigoCita,
        'nombre' => $nombre,
        'correo' => $correo,
        'telefono' => $telefono,
        'servicio' => $servicio,
        'mensaje' => $mensaje,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    
    try {
        $result = $modelo->crear($datos);
        
        if ($result) {
            jsonResponse([
                'success' => true, 
                'message' => 'Cita registrada exitosamente. Te contactaremos pronto.',
                'codigo_cita' => $codigoCita
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Error al registrar la cita'], 500);
        }
    } catch (Exception $e) {
        error_log("Error al crear cita: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Error al procesar la solicitud'], 500);
    }
}

/**
 * Actualizar cita (Admin)
 */
function handleUpdate($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data)) {
        $data = $_POST;
    }
    
    $id = (int)($data['id'] ?? 0);
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $datos = [
        'nombre' => sanitize($data['nombre'] ?? ''),
        'correo' => sanitize($data['correo'] ?? ''),
        'telefono' => sanitize($data['telefono'] ?? ''),
        'servicio' => sanitize($data['servicio'] ?? ''),
        'mensaje' => sanitize($data['mensaje'] ?? ''),
        'estado' => sanitize($data['estado'] ?? 'pendiente')
    ];
    
    $result = $modelo->actualizar($id, $datos);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Cita actualizada', 
            'Citas', 
            "Cita ID: {$id} - Cliente: {$datos['nombre']}"
        );
        jsonResponse(['success' => true, 'message' => 'Cita actualizada exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar cita'], 500);
    }
}

/**
 * Eliminar cita (Admin)
 */
function handleDelete($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    // Obtener datos antes de eliminar para el log
    $cita = $modelo->obtenerPorId($id);
    
    if (!$cita) {
        jsonResponse(['success' => false, 'message' => 'Cita no encontrada'], 404);
    }
    
    $result = $modelo->eliminar($id);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Cita eliminada', 
            'Citas', 
            "Código: {$cita['codigo_cita']} - Cliente: {$cita['nombre']}"
        );
        jsonResponse(['success' => true, 'message' => 'Cita eliminada exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al eliminar cita'], 500);
    }
}

/**
 * Actualizar solo el estado de la cita (Admin)
 */
function handleUpdateEstado($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $estado = sanitize($data['estado'] ?? '');
    
    $estadosValidos = ['pendiente', 'confirmada', 'completada', 'cancelada'];
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    if (!in_array($estado, $estadosValidos)) {
        jsonResponse(['success' => false, 'message' => 'Estado inválido'], 400);
    }
    
    $result = $modelo->actualizarEstado($id, $estado);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Estado de cita actualizado', 
            'Citas', 
            "Cita ID: {$id} - Nuevo estado: {$estado}"
        );
        jsonResponse(['success' => true, 'message' => 'Estado actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
    }
}

/**
 * Obtener estadísticas de citas (Admin)
 */
function handleStats($modelo) {
    $stats = $modelo->obtenerEstadisticas();
    $serviciosTop = $modelo->obtenerServiciosTop(5);
    $citasRecientes = $modelo->obtenerRecientesPendientes(10);
    
    if ($stats !== false) {
        jsonResponse([
            'success' => true,
            'stats' => $stats,
            'servicios_top' => $serviciosTop,
            'citas_recientes' => $citasRecientes
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener estadísticas'], 500);
    }
}