<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/ProductoModel.php'; // Corregí la ruta
require_once __DIR__ . '/../model/UsuarioModel.php'; // Agregué este require

class ProductoController extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new ProductoModel();
    }
    
    public function index() {
        try {
            $categoria = $_GET['categoria'] ?? '';
            $busqueda = $_GET['busqueda'] ?? '';
            
            $productos = $this->model->getAllWithCategory($categoria, $busqueda);
            
            $this->jsonResponse(['success' => true, 'productos' => $productos]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
            }
            
            $producto = $this->model->getById($id);
            
            if ($producto) {
                $this->jsonResponse(['success' => true, 'producto' => $producto]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        $this->checkAuth();
        
        try {
            $imagen = $this->handleImageUpload($_FILES['imagen'] ?? null, 'productos');
            
            if (!$imagen) {
                $this->jsonResponse(['success' => false, 'message' => 'Imagen requerida'], 400);
            }
            
            $data = [
                'nombre' => $this->sanitize($_POST['nombre'] ?? ''),
                'descripcion' => $this->sanitize($_POST['descripcion'] ?? ''),
                'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
                'precio' => (float)($_POST['precio'] ?? 0),
                'stock' => (int)($_POST['stock'] ?? 0),
                'codigo_sku' => $this->sanitize($_POST['codigo_sku'] ?? ''),
                'destacado' => isset($_POST['destacado']) ? 1 : 0,
                'activo' => 1,
                'imagen' => $imagen
            ];
            
            if (empty($data['nombre']) || $data['categoria_id'] == 0 || $data['precio'] <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
            }
            
            $result = $this->model->create($data);
            
            if ($result) {
                $this->registrarActividad('Producto creado', 'Productos', "Producto: {$data['nombre']}");
                $this->jsonResponse(['success' => true, 'message' => 'Producto creado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al crear producto'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        $this->checkAuth();
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            // Obtener producto actual
            $productoActual = $this->model->getById($id);
            if (!$productoActual) {
                $this->jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
            $imagen = $productoActual['imagen'];
            
            // Procesar nueva imagen si existe
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $this->deleteImage($imagen);
                $imagen = $this->handleImageUpload($_FILES['imagen'], 'productos');
            }
            
            $data = [
                'nombre' => $this->sanitize($_POST['nombre'] ?? ''),
                'descripcion' => $this->sanitize($_POST['descripcion'] ?? ''),
                'categoria_id' => (int)($_POST['categoria_id'] ?? 0),
                'precio' => (float)($_POST['precio'] ?? 0),
                'stock' => (int)($_POST['stock'] ?? 0),
                'codigo_sku' => $this->sanitize($_POST['codigo_sku'] ?? ''),
                'destacado' => isset($_POST['destacado']) ? 1 : 0,
                'imagen' => $imagen
            ];
            
            $result = $this->model->update($id, $data);
            
            if ($result) {
                $this->registrarActividad('Producto actualizado', 'Productos', "ID: {$id}");
                $this->jsonResponse(['success' => true, 'message' => 'Producto actualizado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar producto'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete() {
        $this->checkAuth();
        
        try {
            $data = $this->getRequestData();
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            // Obtener producto para eliminar imagen
            $producto = $this->model->getById($id);
            
            if (!$producto) {
                $this->jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
            // Eliminar imagen
            $this->deleteImage($producto['imagen']);
            
            // Eliminar producto
            $result = $this->model->delete($id);
            
            if ($result) {
                $this->registrarActividad('Producto eliminado', 'Productos', "Producto: {$producto['nombre']}");
                $this->jsonResponse(['success' => true, 'message' => 'Producto eliminado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar producto'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // Métodos auxiliares que faltaban
    protected function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        
        return $_POST;
    }
    
    protected function deleteImage($imagePath) {
        if ($imagePath && file_exists("../{$imagePath}")) {
            @unlink("../{$imagePath}");
        }
    }
    
    protected function handleImageUpload($file, $carpeta) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
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
        $rutaDestino = __DIR__ . "/../IMG/{$carpeta}/";
        
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
    
    protected function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}