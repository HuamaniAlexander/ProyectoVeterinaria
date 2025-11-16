<?php
/**
 * Controlador de Sliders - PetZone
 * Maneja las solicitudes HTTP y usa SlidersModelo
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/slidersModelo.php';

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

$slidersModelo = new SlidersModelo();

try {
    switch($action) {
        case 'list':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleList($slidersModelo);
            break;
            
        case 'get':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleGet($slidersModelo);
            break;
            
        case 'create':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleCreate($slidersModelo);
            break;
            
        case 'update':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleUpdate($slidersModelo);
            break;
            
        case 'delete':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleDelete($slidersModelo);
            break;
            
        case 'activos':
            // Público - obtener sliders activos
            handleActivos($slidersModelo);
            break;
            
        case 'por_posicion':
            // Público - obtener sliders por posición
            handlePorPosicion($slidersModelo);
            break;
            
        case 'cambiar_estado':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
            }
            handleCambiarEstado($slidersModelo);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("SLIDERS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

/**
 * Listar todos los sliders (Admin)
 */
function handleList($modelo) {
    $sliders = $modelo->listarTodos();
    
    if ($sliders !== false) {
        jsonResponse(['success' => true, 'sliders' => $sliders]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar sliders'], 500);
    }
}

/**
 * Obtener slider por ID (Admin)
 */
function handleGet($modelo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $slider = $modelo->obtenerPorId($id);
    
    if ($slider) {
        jsonResponse(['success' => true, 'slider' => $slider]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Slider no encontrado'], 404);
    }
}

/**
 * Crear nuevo slider (Admin)
 */
function handleCreate($modelo) {
    $titulo = sanitize($_POST['titulo'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $enlace = sanitize($_POST['enlace'] ?? '');
    $posicion = sanitize($_POST['posicion'] ?? 'principal');
    $orden = (int)($_POST['orden'] ?? $modelo->obtenerSiguienteOrden());
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if (empty($titulo)) {
        jsonResponse(['success' => false, 'message' => 'Título requerido'], 400);
    }
    
    // Procesar imagen
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = uploadImagen($_FILES['imagen'], 'sliders');
    } else {
        jsonResponse(['success' => false, 'message' => 'Imagen requerida'], 400);
    }
    
    $datos = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'imagen' => $imagen,
        'enlace' => $enlace,
        'posicion' => $posicion,
        'orden' => $orden,
        'activo' => $activo
    ];
    
    $nuevoId = $modelo->crear($datos);
    
    if ($nuevoId) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Slider creado', 'Sliders', "Slider: {$titulo}");
        jsonResponse(['success' => true, 'message' => 'Slider creado exitosamente', 'id' => $nuevoId]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al crear slider'], 500);
    }
}

/**
 * Actualizar slider (Admin)
 */
function handleUpdate($modelo) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $titulo = sanitize($_POST['titulo'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $enlace = sanitize($_POST['enlace'] ?? '');
    $posicion = sanitize($_POST['posicion'] ?? 'principal');
    $orden = (int)($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Obtener imagen actual
    $sliderActual = $modelo->obtenerPorId($id);
    if (!$sliderActual) {
        jsonResponse(['success' => false, 'message' => 'Slider no encontrado'], 404);
    }
    
    $imagen = $sliderActual['imagen'];
    
    // Procesar nueva imagen si se subió
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && $_FILES['imagen']['size'] > 0) {
        // Eliminar imagen anterior
        if ($imagen && file_exists("../{$imagen}")) {
            @unlink("../{$imagen}");
        }
        $imagen = uploadImagen($_FILES['imagen'], 'sliders');
    }
    
    $datos = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'imagen' => $imagen,
        'enlace' => $enlace,
        'posicion' => $posicion,
        'orden' => $orden,
        'activo' => $activo
    ];
    
    $result = $modelo->actualizar($id, $datos);
    
    if ($result) {
        $modelo->registrarActividad($_SESSION['user_id'], 'Slider actualizado', 'Sliders', "Slider ID: {$id}");
        jsonResponse(['success' => true, 'message' => 'Slider actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar slider'], 500);
    }
}

/**
 * Eliminar slider (Admin)
 */
function handleDelete($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $slider = $modelo->obtenerPorId($id);
    
    if (!$slider) {
        jsonResponse(['success' => false, 'message' => 'Slider no encontrado'], 404);
    }
    
    $result = $modelo->eliminar($id);
    
    if ($result) {
        // Eliminar imagen física
        if ($slider['imagen'] && file_exists("../{$slider['imagen']}")) {
            @unlink("../{$slider['imagen']}");
        }
        
        $modelo->registrarActividad($_SESSION['user_id'], 'Slider eliminado', 'Sliders', "Slider: {$slider['titulo']}");
        jsonResponse(['success' => true, 'message' => 'Slider eliminado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al eliminar slider'], 500);
    }
}

/**
 * Obtener sliders activos (Público)
 */
function handleActivos($modelo) {
    $limite = (int)($_GET['limite'] ?? 5);
    
    $sliders = $modelo->obtenerActivos($limite);
    
    if ($sliders !== false) {
        jsonResponse(['success' => true, 'sliders' => $sliders]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener sliders activos'], 500);
    }
}

/**
 * Obtener sliders por posición (Público)
 */
function handlePorPosicion($modelo) {
    $posicion = $_GET['posicion'] ?? 'principal';
    
    $sliders = $modelo->obtenerPorPosicion($posicion);
    
    if ($sliders !== false) {
        jsonResponse(['success' => true, 'sliders' => $sliders]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener sliders'], 500);
    }
}

/**
 * Cambiar estado activo/inactivo (Admin)
 */
function handleCambiarEstado($modelo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $activo = (int)($data['activo'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $result = $modelo->cambiarEstado($id, $activo);
    
    if ($result) {
        $modelo->registrarActividad(
            $_SESSION['user_id'], 
            'Estado de slider actualizado', 
            'Sliders', 
            "Slider ID: {$id} - Activo: {$activo}"
        );
        jsonResponse(['success' => true, 'message' => 'Estado actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
    }
}

/**
 * Función auxiliar para subir imágenes
 */
function uploadImagen($file, $carpeta) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
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