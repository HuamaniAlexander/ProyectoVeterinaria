<?php
/**
 * Controlador de Contenido General - PetZone
 * Maneja las solicitudes HTTP y usa ContenidoModelo
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/contenidoModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? $requestData['action'] ?? 'get';

$contenidoModelo = new ContenidoModelo();

try {
    switch($action) {
        case 'get':
            handleGet($contenidoModelo);
            break;
            
        case 'get_by_key':
            handleGetByKey($contenidoModelo);
            break;
            
        case 'update':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleUpdate($contenidoModelo);
            break;
            
        case 'update_single':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleUpdateSingle($contenidoModelo);
            break;
            
        case 'create':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleCreate($contenidoModelo);
            break;
            
        case 'list_sections':
            handleListSections($contenidoModelo);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("CONTENIDO.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

/**
 * Obtener contenido (opcionalmente filtrado por sección)
 */
function handleGet($modelo) {
    $seccion = $_GET['seccion'] ?? '';
    
    $contenidos = $modelo->obtener($seccion);
    
    if ($contenidos !== false) {
        jsonResponse(['success' => true, 'contenidos' => $contenidos]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar contenido'], 500);
    }
}

/**
 * Obtener contenido específico por sección y clave
 */
function handleGetByKey($modelo) {
    $seccion = $_GET['seccion'] ?? '';
    $clave = $_GET['clave'] ?? '';
    
    if (empty($seccion) || empty($clave)) {
        jsonResponse(['success' => false, 'message' => 'Sección y clave requeridas'], 400);
    }
    
    $contenido = $modelo->obtenerPorClave($seccion, $clave);
    
    if ($contenido) {
        jsonResponse(['success' => true, 'contenido' => $contenido]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Contenido no encontrado'], 404);
    }
}

/**
 * Actualizar múltiples contenidos de una sección
 */
function handleUpdate($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $seccion = sanitize($data['seccion'] ?? '');
    $contenidos = $data['contenidos'] ?? [];
    
    if (empty($seccion) || empty($contenidos)) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    // Sanitizar todos los valores
    $contenidosSanitizados = [];
    foreach ($contenidos as $clave => $valor) {
        $contenidosSanitizados[$clave] = sanitize($valor);
    }
    
    $result = $modelo->actualizarSeccion($seccion, $contenidosSanitizados);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Contenido actualizado', 
            'Contenido', 
            "Sección: {$seccion}"
        );
        
        jsonResponse(['success' => true, 'message' => 'Contenido actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar contenido'], 500);
    }
}

/**
 * Actualizar un solo contenido
 */
function handleUpdateSingle($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $seccion = sanitize($data['seccion'] ?? '');
    $clave = sanitize($data['clave'] ?? '');
    $valor = sanitize($data['valor'] ?? '');
    
    if (empty($seccion) || empty($clave)) {
        jsonResponse(['success' => false, 'message' => 'Sección y clave requeridas'], 400);
    }
    
    // Verificar si es editable
    if (!$modelo->esEditable($seccion, $clave)) {
        jsonResponse(['success' => false, 'message' => 'Este contenido no es editable'], 403);
    }
    
    $result = $modelo->actualizar($seccion, $clave, $valor);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Contenido actualizado', 
            'Contenido', 
            "Sección: {$seccion}, Clave: {$clave}"
        );
        
        jsonResponse(['success' => true, 'message' => 'Contenido actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar contenido'], 500);
    }
}

/**
 * Crear nuevo contenido
 */
function handleCreate($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $datos = [
        'seccion' => sanitize($data['seccion'] ?? ''),
        'clave' => sanitize($data['clave'] ?? ''),
        'valor' => sanitize($data['valor'] ?? ''),
        'tipo' => sanitize($data['tipo'] ?? 'texto'),
        'editable' => isset($data['editable']) ? (int)$data['editable'] : 1,
        'descripcion' => sanitize($data['descripcion'] ?? '')
    ];
    
    if (empty($datos['seccion']) || empty($datos['clave'])) {
        jsonResponse(['success' => false, 'message' => 'Sección y clave requeridas'], 400);
    }
    
    // Verificar si ya existe
    $existente = $modelo->obtenerPorClave($datos['seccion'], $datos['clave']);
    if ($existente) {
        jsonResponse(['success' => false, 'message' => 'Este contenido ya existe'], 400);
    }
    
    $result = $modelo->crear($datos);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Contenido creado', 
            'Contenido', 
            "Sección: {$datos['seccion']}, Clave: {$datos['clave']}"
        );
        
        jsonResponse(['success' => true, 'message' => 'Contenido creado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al crear contenido'], 500);
    }
}

/**
 * Listar todas las secciones disponibles
 */
function handleListSections($modelo) {
    $secciones = $modelo->obtenerSecciones();
    
    if ($secciones !== false) {
        jsonResponse(['success' => true, 'secciones' => $secciones]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar secciones'], 500);
    }
}