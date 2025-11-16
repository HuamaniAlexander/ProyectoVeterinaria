<?php
/**
 * Controlador de Pedidos - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/pedidosModelo.php';

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

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $requestData['action'] ?? 'list';

$pedidosModelo = new PedidosModelo();

error_log("PEDIDOS.PHP - Action: " . $action);

try {
    switch($action) {
        case 'list':
            handleList($pedidosModelo);
            break;
        case 'get':
            handleGet($pedidosModelo);
            break;
        case 'update-estado':
            handleUpdateEstado($pedidosModelo, $requestData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("PEDIDOS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleList($modelo) {
    $estado = $_GET['estado'] ?? '';
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    
    $pedidos = $modelo->listar($estado, $fecha_desde, $fecha_hasta);
    
    if ($pedidos !== false) {
        error_log("LIST PEDIDOS - Total encontrados: " . count($pedidos));
        jsonResponse(['success' => true, 'pedidos' => $pedidos]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar pedidos'], 500);
    }
}

function handleGet($modelo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $pedido = $modelo->obtenerPorId($id);
    
    if (!$pedido) {
        jsonResponse(['success' => false, 'message' => 'Pedido no encontrado'], 404);
    }
    
    $detalles = $modelo->obtenerDetalles($id);
    $pedido['detalles'] = $detalles;
    
    jsonResponse(['success' => true, 'pedido' => $pedido]);
}

function handleUpdateEstado($modelo, $data) {
    $id = (int)($data['id'] ?? 0);
    $estado = sanitize($data['estado'] ?? '');
    
    error_log("UPDATE ESTADO - ID: " . $id);
    error_log("UPDATE ESTADO - Nuevo estado: " . $estado);
    
    if ($id == 0 || empty($estado)) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    $estados_validos = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
    if (!in_array($estado, $estados_validos)) {
        jsonResponse(['success' => false, 'message' => 'Estado inválido'], 400);
    }
    
    $pedidoActual = $modelo->obtenerPorId($id);
    
    if (!$pedidoActual) {
        jsonResponse(['success' => false, 'message' => 'Pedido no encontrado'], 404);
    }
    
    error_log("UPDATE ESTADO - Estado actual en BD: " . $pedidoActual['estado']);
    
    $result = $modelo->actualizarEstado($id, $estado);
    
    $pedidoNuevo = $modelo->obtenerPorId($id);
    
    error_log("UPDATE ESTADO - Nuevo estado en BD: " . $pedidoNuevo['estado']);
    
    if ($result && $pedidoNuevo['estado'] === $estado) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Estado de pedido actualizado', 
            'Pedidos', 
            "Pedido ID: {$id} - De '{$pedidoActual['estado']}' a '{$estado}'"
        );
        
        jsonResponse([
            'success' => true, 
            'message' => 'Estado actualizado exitosamente',
            'nuevo_estado' => $estado,
            'debug' => [
                'id' => $id,
                'estado_anterior' => $pedidoActual['estado'],
                'estado_nuevo' => $pedidoNuevo['estado']
            ]
        ]);
    } else {
        error_log("UPDATE ESTADO - ERROR: No se pudo actualizar");
        jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
    }
}