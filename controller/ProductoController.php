<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/ProductoModel.php';

class ProductoController {
    private $model;
    
    public function __construct() {
        $this->model = new ProductoModel();
    }
    
    public function list() {
        try {
            $categoria = $_GET['categoria'] ?? '';
            $busqueda = $_GET['busqueda'] ?? '';
            
            $productos = $this->model->getAllWithCategory($categoria, $busqueda);
            
            jsonResponse(['success' => true, 'productos' => $productos]);
            
        } catch (Exception $e) {
            error_log("ERROR en ProductoController::list - " . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Error al cargar productos'], 500);
        }
    }
    
    public function get() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
        }
        
        try {
            $producto = $this->model->getById($id);
            
            if ($producto) {
                jsonResponse(['success' => true, 'producto' => $producto]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }
        
        try {
            $data = [
                'nombre' => sanitize($_POST['nombre'] ?? ''),
                'descripcion' => sanitize($_POST['descripcion'] ?? ''),
                'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
                'precio' => (float)($_POST['precio'] ?? 0),
                'stock' => (int)($_POST['stock'] ?? 0),
                'codigo_sku' => sanitize($_POST['codigo_sku'] ?? ''),
                'destacado' => isset($_POST['destacado']) ? 1 : 0,
                'activo' => 1
            ];
            
            if (empty($data['nombre']) || $data['categoria_id'] == 0 || $data['precio'] <= 0) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
            }
            
            // Procesar imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $data['imagen'] = $this->uploadImagen($_FILES['imagen']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Imagen requerida'], 400);
            }
            
            $result = $this->model->create($data);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Producto creado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al crear producto'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id == 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
        }
        
        try {
            $productoActual = $this->model->getById($id);
            if (!$productoActual) {
                jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
            $data = [
                'nombre' => sanitize($_POST['nombre'] ?? ''),
                'descripcion' => sanitize($_POST['descripcion'] ?? ''),
                'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
                'precio' => (float)($_POST['precio'] ?? 0),
                'stock' => (int)($_POST['stock'] ?? 0),
                'codigo_sku' => sanitize($_POST['codigo_sku'] ?? ''),
                'destacado' => isset($_POST['destacado']) ? 1 : 0,
                'imagen' => $productoActual['imagen']
            ];
            
            // Procesar nueva imagen si existe
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                if ($data['imagen'] && file_exists(__DIR__ . "/../public/" . $data['imagen'])) {
                    @unlink(__DIR__ . "/../public/" . $data['imagen']);
                }
                $data['imagen'] = $this->uploadImagen($_FILES['imagen']);
            }
            
            $result = $this->model->update($id, $data);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Producto actualizado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al actualizar producto'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        
        if ($id == 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
        }
        
        try {
            $producto = $this->model->getById($id);
            
            if (!$producto) {
                jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
            // Eliminar imagen
            if ($producto['imagen'] && file_exists(__DIR__ . "/../public/" . $producto['imagen'])) {
                @unlink(__DIR__ . "/../public/" . $producto['imagen']);
            }
            
            $result = $this->model->delete($id);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Producto eliminado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al eliminar producto'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function uploadImagen($file) {
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
        $rutaDestino = __DIR__ . "/../public/img/productos/";
        
        if (!file_exists($rutaDestino)) {
            mkdir($rutaDestino, 0777, true);
        }
        
        $rutaCompleta = $rutaDestino . $nombreArchivo;
        
        if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            return "img/productos/{$nombreArchivo}";
        } else {
            throw new Exception('Error al subir imagen');
        }
    }
}