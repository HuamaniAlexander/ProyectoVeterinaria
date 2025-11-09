<?php
/**
 * API de Pedidos - PetZone
 * Archivo: api/pedidos.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

$action = $_GET['action'] ?? 'list';

try {
    switch($action) {
        case 'list':
            listPedidos();
            break;
        case 'get':
            getPedido();
            break;
        case 'update-estado':
            updateEstado();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function listPedidos() {
    $estado = $_GET['estado'] ?? '';
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';
    
    $db = getDB();
    $sql = "SELECT * FROM pedidos WHERE 1=1";
    $params = [];
    
    if (!empty($estado)) {
        $sql .= " AND estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($fecha_desde)) {
        $sql .= " AND DATE(fecha_pedido) >= ?";
        $params[] = $fecha_desde;
    }
    
    if (!empty($fecha_hasta)) {
        $sql .= " AND DATE(fecha_pedido) <= ?";
        $params[] = $fecha_hasta;
    }
    
    $sql .= " ORDER BY fecha_pedido DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'pedidos' => $pedidos]);
}

function getPedido() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $db = getDB();
    
    // Obtener pedido
    $stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();
    
    if (!$pedido) {
        jsonResponse(['success' => false, 'message' => 'Pedido no encontrado'], 404);
    }
    
    // Obtener detalles del pedido
    $stmt = $db->prepare("SELECT * FROM detalle_pedidos WHERE pedido_id = ?");
    $stmt->execute([$id]);
    $detalles = $stmt->fetchAll();
    
    $pedido['detalles'] = $detalles;
    
    jsonResponse(['success' => true, 'pedido' => $pedido]);
}

function updateEstado() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = (int)($data['id'] ?? 0);
    $estado = sanitize($data['estado'] ?? '');
    
    if ($id == 0 || empty($estado)) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    $estados_validos = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
    if (!in_array($estado, $estados_validos)) {
        jsonResponse(['success' => false, 'message' => 'Estado inválido'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $result = $stmt->execute([$estado, $id]);
    
    if ($result) {
        registrarActividad($db, $_SESSION['user_id'], 'Estado de pedido actualizado', 'Pedidos', "Pedido ID: {$id} - Estado: {$estado}");
        jsonResponse(['success' => true, 'message' => 'Estado actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
    }
}

function registrarActividad($db, $userId, $accion, $modulo, $detalle = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $accion, $modulo, $detalle, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}
?>