<?php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/helpers/response.php';

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$producto = new Producto();

switch ($method) {
    case 'GET':
        // Obtener todos los productos o filtrar por categoría
        if (isset($_GET['categoria'])) {
            $data = $producto->getByCategory($_GET['categoria']);
        } elseif (isset($_GET['id'])) {
            $data = $producto->getById($_GET['id']);
        } elseif (isset($_GET['buscar'])) {
            $data = $producto->search($_GET['buscar']);
        } else {
            $data = $producto->getAll();
        }
        Response::success($data);
        break;
        
    case 'POST':
        // Crear nuevo producto
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($producto->create($input)) {
            Response::success([], 'Producto creado exitosamente');
        } else {
            Response::error('Error al crear producto');
        }
        break;
        
    case 'PUT':
        // Actualizar producto
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if ($id && $producto->update($id, $input)) {
            Response::success([], 'Producto actualizado exitosamente');
        } else {
            Response::error('Error al actualizar producto');
        }
        break;
        
    case 'DELETE':
        // Eliminar producto
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if ($id && $producto->delete($id)) {
            Response::success([], 'Producto eliminado exitosamente');
        } else {
            Response::error('Error al eliminar producto');
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}