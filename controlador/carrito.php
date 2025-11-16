<?php
/**
 * Controlador de Carrito - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

ob_start();

require_once '../config/database.php';
require_once '../modelo/carritoModelo.php';
require_once '../modelo/productosModelo.php';

header('Content-Type: application/json');

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $requestData['action'] ?? $_GET['action'] ?? '';
$sessionId = getCartSessionId();

$carritoModelo = new CarritoModelo();
$productosModelo = new ProductosModelo();

try {
    switch($action) {
        case 'add':
            handleAdd($carritoModelo, $productosModelo, $sessionId);
            break;
        case 'update':
            handleUpdate($carritoModelo, $productosModelo, $sessionId);
            break;
        case 'remove':
            handleRemove($carritoModelo, $sessionId);
            break;
        case 'get':
            handleGet($carritoModelo, $sessionId);
            break;
        case 'clear':
            handleClear($carritoModelo, $sessionId);
            break;
        case 'checkout':
            handleCheckout($carritoModelo, $productosModelo, $sessionId);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleAdd($carritoModelo, $productosModelo, $sessionId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productoId = (int)($data['producto_id'] ?? 0);
    $cantidad = (int)($data['cantidad'] ?? 1);
    
    if ($productoId <= 0 || $cantidad <= 0) {
        jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
    }
    
    $producto = $productosModelo->obtenerPorId($productoId);
    
    if (!$producto) {
        jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }
    
    if ($producto['stock'] < $cantidad) {
        jsonResponse(['success' => false, 'message' => 'Stock insuficiente'], 400);
    }
    
    $itemExistente = $carritoModelo->verificarItemExistente($sessionId, $productoId);
    
    if ($itemExistente) {
        $nuevaCantidad = $itemExistente['cantidad'] + $cantidad;
        
        if ($producto['stock'] < $nuevaCantidad) {
            jsonResponse(['success' => false, 'message' => 'Stock insuficiente'], 400);
        }
        
        $carritoModelo->actualizarCantidad($sessionId, $productoId, $nuevaCantidad, $producto['precio']);
    } else {
        $carritoModelo->agregarItem($sessionId, $productoId, $cantidad, $producto['precio']);
    }
    
    $totales = $carritoModelo->obtenerTotales($sessionId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Producto agregado al carrito',
        'cart' => $totales
    ]);
}

function handleUpdate($carritoModelo, $productosModelo, $sessionId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productoId = (int)($data['producto_id'] ?? 0);
    $cantidad = (int)($data['cantidad'] ?? 1);
    
    if ($productoId <= 0 || $cantidad < 0) {
        jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
    }
    
    if ($cantidad == 0) {
        $carritoModelo->eliminarItem($sessionId, $productoId);
    } else {
        $producto = $productosModelo->obtenerPorId($productoId);
        
        if (!$producto || $producto['stock'] < $cantidad) {
            jsonResponse(['success' => false, 'message' => 'Stock insuficiente'], 400);
        }
        
        $carritoModelo->actualizarCantidad($sessionId, $productoId, $cantidad);
    }
    
    $totales = $carritoModelo->obtenerTotales($sessionId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Carrito actualizado',
        'cart' => $totales
    ]);
}

function handleRemove($carritoModelo, $sessionId) {
    $data = json_decode(file_get_contents('php://input'), true);
    $productoId = (int)($data['producto_id'] ?? 0);
    
    if ($productoId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $carritoModelo->eliminarItem($sessionId, $productoId);
    $totales = $carritoModelo->obtenerTotales($sessionId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Producto eliminado',
        'cart' => $totales
    ]);
}

function handleGet($carritoModelo, $sessionId) {
    $items = $carritoModelo->obtenerItems($sessionId);
    
    foreach ($items as &$item) {
        $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
    }
    
    $totales = $carritoModelo->obtenerTotales($sessionId);
    
    jsonResponse([
        'success' => true,
        'items' => $items,
        'totales' => $totales
    ]);
}

function handleClear($carritoModelo, $sessionId) {
    $carritoModelo->vaciar($sessionId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Carrito vaciado',
        'cart' => ['count' => 0, 'subtotal' => 0, 'total' => 0]
    ]);
}

function handleCheckout($carritoModelo, $productosModelo, $sessionId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombre = sanitize($data['nombre'] ?? '');
    $email = sanitize($data['email'] ?? '');
    $telefono = sanitize($data['telefono'] ?? '');
    $direccion = sanitize($data['direccion'] ?? '');
    $metodo_pago = sanitize($data['metodo_pago'] ?? '');
    $notas = sanitize($data['notas'] ?? '');
    
    if (empty($nombre) || empty($email) || empty($telefono) || empty($direccion) || empty($metodo_pago)) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    $db = getDB();
    
    try {
        $db->beginTransaction();

        $items = $carritoModelo->obtenerItems($sessionId);

        if (empty($items)) {
            jsonResponse(['success' => false, 'message' => 'Carrito vacío'], 400);
        }

        foreach ($items as $item) {
            if (!$productosModelo->verificarStock($item['producto_id'], $item['cantidad'])) {
                throw new Exception("Stock insuficiente para: " . $item['nombre']);
            }
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['cantidad'] * $item['precio_unitario'];
        }

        $codigo_pedido = 'PZ-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $stmt = $db->prepare("
            INSERT INTO pedidos 
            (codigo_pedido, nombre_cliente, email_cliente, telefono_cliente, direccion_envio, 
             subtotal, total, metodo_pago, notas, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
        ");
        $stmt->execute([
            $codigo_pedido, $nombre, $email, $telefono, $direccion,
            $subtotal, $subtotal, $metodo_pago, $notas
        ]);
        
        $pedido_id = $db->lastInsertId();

        $stmt = $db->prepare("
            INSERT INTO detalle_pedidos 
            (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
            
            $stmt->execute([
                $pedido_id, $item['producto_id'], $item['nombre'],
                $item['cantidad'], $item['precio_unitario'], $subtotal_item
            ]);

            $productosModelo->actualizarStock($item['producto_id'], $item['cantidad']);
        }

        $carritoModelo->vaciar($sessionId);

        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'codigo_pedido' => $codigo_pedido
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Error al procesar pedido: ' . $e->getMessage()], 500);
    }
}