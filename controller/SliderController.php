<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/SliderModel.php';

class SliderController extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new SliderModel();
    }
    
    public function index() {
        $this->checkAuth();
        
        try {
            $sliders = $this->model->getAll([], 'orden ASC, id DESC');
            
            jsonResponse(['success' => true, 'sliders' => $sliders]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        $this->checkAuth();
        
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
            }
            
            $slider = $this->model->getById($id);
            
            if ($slider) {
                jsonResponse(['success' => true, 'slider' => $slider]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Slider no encontrado'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function create() {
        $this->checkAuth();
        
        try {
            $imagen = $this->handleImageUpload($_FILES['imagen'] ?? null, 'sliders');
            
            if (!$imagen) {
                jsonResponse(['success' => false, 'message' => 'Imagen requerida'], 400);
            }
            
            $data = [
                'titulo' => sanitize($_POST['titulo'] ?? ''),
                'descripcion' => sanitize($_POST['descripcion'] ?? ''),
                'imagen' => $imagen,
                'enlace' => sanitize($_POST['enlace'] ?? ''),
                'posicion' => sanitize($_POST['posicion'] ?? 'principal'),
                'orden' => (int)($_POST['orden'] ?? 0),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            if (empty($data['titulo'])) {
                jsonResponse(['success' => false, 'message' => 'Título requerido'], 400);
            }
            
            $result = $this->model->create($data);
            
            if ($result) {
                $this->registrarActividad('Slider creado', 'Sliders', "Slider: {$data['titulo']}");
                jsonResponse(['success' => true, 'message' => 'Slider creado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al crear slider'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        $this->checkAuth();
        
        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id == 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            // Obtener slider actual para la imagen
            $sliderActual = $this->model->getById($id);
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
                $imagen = $this->handleImageUpload($_FILES['imagen'], 'sliders');
            }
            
            $data = [
                'titulo' => sanitize($_POST['titulo'] ?? ''),
                'descripcion' => sanitize($_POST['descripcion'] ?? ''),
                'imagen' => $imagen,
                'enlace' => sanitize($_POST['enlace'] ?? ''),
                'posicion' => sanitize($_POST['posicion'] ?? 'principal'),
                'orden' => (int)($_POST['orden'] ?? 0),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];
            
            $result = $this->model->update($id, $data);
            
            if ($result) {
                $this->registrarActividad('Slider actualizado', 'Sliders', "Slider ID: {$id}");
                jsonResponse(['success' => true, 'message' => 'Slider actualizado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al actualizar slider'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete() {
        $this->checkAuth();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            $slider = $this->model->getById($id);
            
            if (!$slider) {
                jsonResponse(['success' => false, 'message' => 'Slider no encontrado'], 404);
            }
            
            $result = $this->model->delete($id);
            
            if ($result) {
                // Eliminar imagen del servidor
                if ($slider['imagen'] && file_exists("../{$slider['imagen']}")) {
                    @unlink("../{$slider['imagen']}");
                }
                
                $this->registrarActividad('Slider eliminado', 'Sliders', "Slider: {$slider['titulo']}");
                jsonResponse(['success' => true, 'message' => 'Slider eliminado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al eliminar slider'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function activos() {
        try {
            $sliders = $this->model->getActivos(5);
            jsonResponse(['success' => true, 'sliders' => $sliders]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
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
            throw new Exception('El archivo es demasiado grande');
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
}