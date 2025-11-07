<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Slider.php';
require_once __DIR__ . '/../helpers/response.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    Response::error('No autorizado', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$slider = new Slider();

switch ($method) {
    case 'GET':
        $data = $slider->getAll();
        Response::success($data);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['titulo']) || empty($input['imagen'])) {
            Response::error('Título e imagen son obligatorios', 422);
        }
        
        $data = [
            'titulo' => htmlspecialchars($input['titulo']),
            'imagen' => htmlspecialchars($input['imagen']),
            'enlace' => htmlspecialchars($input['enlace'] ?? ''),
            'orden' => intval($input['orden'] ?? 0),
            'activo' => isset($input['activo']) ? 1 : 0
        ];
        
        if ($slider->create($data)) {
            Response::success(['id' => $slider->getLastInsertId()], 'Slider creado exitosamente');
        } else {
            Response::error('Error al crear slider');
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            Response::error('ID del slider es requerido', 422);
        }
        
        $data = [
            'titulo' => htmlspecialchars($input['titulo']),
            'enlace' => htmlspecialchars($input['enlace'] ?? ''),
            'orden' => intval($input['orden'] ?? 0),
            'activo' => isset($input['activo']) ? 1 : 0
        ];
        
        if (isset($input['imagen'])) {
            $data['imagen'] = htmlspecialchars($input['imagen']);
        }
        
        if ($slider->update($id, $data)) {
            Response::success([], 'Slider actualizado exitosamente');
        } else {
            Response::error('Error al actualizar slider');
        }
        break;
        
    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            Response::error('ID del slider es requerido', 422);
        }
        
        if ($slider->delete($id)) {
            Response::success([], 'Slider eliminado exitosamente');
        } else {
            Response::error('Error al eliminar slider');
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}