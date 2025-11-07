<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Producto.php';
require_once __DIR__ . '/../helpers/response.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    Response::error('No autorizado', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$producto = new Producto();

switch ($method) {
    case 'GET':
        // Obtener todos los productos para el dashboard
        $data = $producto->getAll();
        Response::success($data);
        break;
        
    case 'POST':
        // Crear nuevo producto
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validaciones
        if (empty($input['nombre']) || empty($input['categoria']) || empty($input['precio'])) {
            Response::error('Nombre, categoría y precio son obligatorios', 422);
        }
        
        $data = [
            'nombre' => htmlspecialchars($input['nombre']),
            'descripcion' => htmlspecialchars($input['descripcion'] ?? ''),
            'categoria' => htmlspecialchars($input['categoria']),
            'precio' => floatval($input['precio']),
            'stock' => intval($input['stock'] ?? 0),
            'imagen' => $input['imagen'] ?? 'default.jpg'
        ];
        
        if ($producto->create($data)) {
            Response::success(['id' => $producto->getLastInsertId()], 'Producto creado exitosamente');
        } else {
            Response::error('Error al crear producto');
        }
        break;
        
    case 'PUT':
        // Actualizar producto
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            Response::error('ID del producto es requerido', 422);
        }
        
        $data = [
            'nombre' => htmlspecialchars($input['nombre']),
            'descripcion' => htmlspecialchars($input['descripcion'] ?? ''),
            'categoria' => htmlspecialchars($input['categoria']),
            'precio' => floatval($input['precio']),
            'stock' => intval($input['stock'] ?? 0)
        ];
        
        if (isset($input['imagen'])) {
            $data['imagen'] = $input['imagen'];
        }
        
        if ($producto->update($id, $data)) {
            Response::success([], 'Producto actualizado exitosamente');
        } else {
            Response::error('Error al actualizar producto');
        }
        break;
        
    case 'DELETE':
        // Eliminar producto
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            Response::error('ID del producto es requerido', 422);
        }
        
        if ($producto->delete($id)) {
            Response::success([], 'Producto eliminado exitosamente');
        } else {
            Response::error('Error al eliminar producto');
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}