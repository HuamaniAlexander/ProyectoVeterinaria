<?php
/**
 * Controlador de Productos - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/productosModelo.php';

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

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $requestData['action'] ?? $_POST['action'] ?? 'list';

$productosModelo = new ProductosModelo();

try {
    switch($action) {
        case 'list':
            handleList($productosModelo);
            break;
        case 'get':
            handleGet($productosModelo);
            break;
        case 'create':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleCreate($productosModelo);
            break;
        case 'update':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleUpdate($productosModelo);
            break;
        case 'delete':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleDelete($productosModelo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("PRODUCTOS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleList($modelo) {
    $categoria = $_GET['categoria'] ?? '';
    $busqueda = $_GET['busqueda'] ?? '';
    
    $productos = $modelo->listarTodos($categoria, $busqueda);
    
    if ($productos !== false) {
        jsonResponse(['success' => true, 'productos' => $productos]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar productos'], 500);
    }
}

function handleGet($modelo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $producto = $modelo->obtenerPorId($id);
    
    if ($producto) {
        jsonResponse(['success' => true, 'producto' => $producto]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }
}

function handleCreate($modelo) {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $codigo_sku = sanitize($_POST['codigo_sku'] ?? '');
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    if (empty($nombre) || $categoria_id == 0 || $precio <= 0) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = uploadImagen($_FILES['imagen'], 'productos');
    }
    
    $datos = [
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'categoria_id' => $categoria_id,
        'precio' => $precio,
        'stock' => $stock,
        'imagen' => $imagen,
        'codigo_sku' => $codigo_sku,
        'destacado' => $destacado
    ];
    
    $nuevoId = $modelo->crear($datos);
    
    if ($nuevoId) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Producto creado', 'Productos', "Producto: {$nombre}");
        jsonResponse(['success' => true, 'message' => 'Producto creado exitosamente', 'id' => $nuevoId]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al crear producto'], 500);
    }
}

function handleUpdate($modelo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $nombre = sanitize($_POST['nombre'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $codigo_sku = sanitize($_POST['codigo_sku'] ?? '');
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    
    $productoActual = $modelo->obtenerPorId($id);
    $imagen = $productoActual['imagen'];
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        if ($imagen && file_exists("../{$imagen}")) {
            @unlink("../{$imagen}");
        }
        $imagen = uploadImagen($_FILES['imagen'], 'productos');
    }
    
    $datos = [
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'categoria_id' => $categoria_id,
        'precio' => $precio,
        'stock' => $stock,
        'imagen' => $imagen,
        'codigo_sku' => $codigo_sku,
        'destacado' => $destacado
    ];
    
    $result = $modelo->actualizar($id, $datos);
    
    if ($result) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Producto actualizado', 'Productos', "ID: {$id}");
        jsonResponse(['success' => true, 'message' => 'Producto actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar producto'], 500);
    }
}

function handleDelete($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $producto = $modelo->obtenerPorId($id);
    
    if (!$producto) {
        jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }
    
    if ($producto['imagen'] && file_exists("../{$producto['imagen']}")) {
        @unlink("../{$producto['imagen']}");
    }
    
    $result = $modelo->eliminar($id);
    
    if ($result) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Producto eliminado', 'Productos', "Producto: {$producto['nombre']}");
        jsonResponse(['success' => true, 'message' => 'Producto eliminado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al eliminar producto'], 500);
    }
}

function uploadImagen($file, $carpeta) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande (máx 5MB)');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
    $rutaDestino = __DIR__ . "/../public/IMG/{$carpeta}/";
    
    if (!file_exists($rutaDestino)) {
        mkdir($rutaDestino, 0777, true);
    }
    
    $rutaCompleta = $rutaDestino . $nombreArchivo;
    
    if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
        return "IMG/{$carpeta}/{$nombreArchivo}";
    } else {
        throw new Exception('Error al subir imagen');
    }
}