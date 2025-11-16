<?php
/**
 * Controlador de Anuncios - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/anunciosModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Permitir 'activos' sin autenticación
if (!isset($_SESSION['user_id']) && !in_array($_GET['action'] ?? '', ['activos'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $requestData['action'] ?? '';

error_log("ANUNCIOS.PHP - Action: " . $action);

$anunciosModelo = new AnunciosModelo();

try {
    switch($action) {
        case 'list':
            handleList($anunciosModelo);
            break;
        case 'get':
            handleGet($anunciosModelo);
            break;
        case 'create':
            handleCreate($anunciosModelo, $requestData);
            break;
        case 'update':
            handleUpdate($anunciosModelo, $requestData);
            break;
        case 'delete':
            handleDelete($anunciosModelo, $requestData);
            break;
        case 'activos':
            handleActivos($anunciosModelo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida: ' . $action], 400);
    }
} catch (Exception $e) {
    error_log("ANUNCIOS.PHP - Exception: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleList($modelo) {
    $anuncios = $modelo->listarTodos();
    
    if ($anuncios !== false) {
        jsonResponse(['success' => true, 'anuncios' => $anuncios]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar anuncios'], 500);
    }
}

function handleGet($modelo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $anuncio = $modelo->obtenerPorId($id);
    
    if ($anuncio) {
        jsonResponse(['success' => true, 'anuncio' => $anuncio]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Anuncio no encontrado'], 404);
    }
}

function handleCreate($modelo, $data) {
    $mensaje = sanitize($data['mensaje'] ?? '');
    $tipo = sanitize($data['tipo'] ?? 'aviso_general');
    $color_fondo = sanitize($data['color_fondo'] ?? '#23906F');
    $color_texto = sanitize($data['color_texto'] ?? '#FFFFFF');
    $icono = sanitize($data['icono'] ?? '');
    $velocidad = (int)($data['velocidad'] ?? 30);
    $prioridad = (int)($data['prioridad'] ?? 0);
    $activo = isset($data['activo']) ? (int)$data['activo'] : 1;
    
    if (empty($mensaje)) {
        jsonResponse(['success' => false, 'message' => 'Mensaje requerido'], 400);
    }
    
    $datos = [
        'mensaje' => $mensaje,
        'tipo' => $tipo,
        'color_fondo' => $color_fondo,
        'color_texto' => $color_texto,
        'icono' => $icono,
        'velocidad' => $velocidad,
        'prioridad' => $prioridad,
        'activo' => $activo
    ];
    
    $nuevoId = $modelo->crear($datos);
    
    if ($nuevoId) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Anuncio creado', 'Anuncios', "ID: {$nuevoId} - " . substr($mensaje, 0, 50));
        jsonResponse([
            'success' => true,
            'message' => 'Anuncio creado exitosamente',
            'id' => $nuevoId
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al crear anuncio'], 500);
    }
}

function handleUpdate($modelo, $data) {
    $id = (int)($data['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $mensaje = sanitize($data['mensaje'] ?? '');
    $tipo = sanitize($data['tipo'] ?? 'aviso_general');
    $color_fondo = sanitize($data['color_fondo'] ?? '#23906F');
    $color_texto = sanitize($data['color_texto'] ?? '#FFFFFF');
    $icono = sanitize($data['icono'] ?? '');
    $velocidad = (int)($data['velocidad'] ?? 30);
    $prioridad = (int)($data['prioridad'] ?? 0);
    $activo = isset($data['activo']) ? (int)$data['activo'] : 0;
    
    if (empty($mensaje)) {
        jsonResponse(['success' => false, 'message' => 'Mensaje requerido'], 400);
    }
    
    $datos = [
        'mensaje' => $mensaje,
        'tipo' => $tipo,
        'color_fondo' => $color_fondo,
        'color_texto' => $color_texto,
        'icono' => $icono,
        'velocidad' => $velocidad,
        'prioridad' => $prioridad,
        'activo' => $activo
    ];
    
    $result = $modelo->actualizar($id, $datos);
    
    if ($result) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Anuncio actualizado', 'Anuncios', "ID: {$id} - Activo: {$activo}");
        jsonResponse(['success' => true, 'message' => 'Anuncio actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar anuncio'], 500);
    }
}

function handleDelete($modelo, $data) {
    $id = (int)($data['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $result = $modelo->eliminar($id);
    
    if ($result) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Anuncio eliminado', 'Anuncios', "ID: {$id}");
        jsonResponse(['success' => true, 'message' => 'Anuncio eliminado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al eliminar anuncio'], 500);
    }
}

function handleActivos($modelo) {
    $anuncios = $modelo->obtenerActivos();
    
    if ($anuncios !== false) {
        // Incrementar visualizaciones
        foreach ($anuncios as $anuncio) {
            $modelo->incrementarVisualizaciones($anuncio['id']);
        }
        
        jsonResponse(['success' => true, 'anuncios' => $anuncios]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener anuncios activos'], 500);
    }
}