<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/CitaModel.php';

class CitaController extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new CitaModel();
    }
    
    public function create() {
        try {
            $data = $this->getRequestData();
            
            // Validaciones
            $required = ['nombre', 'correo', 'telefono', 'servicio'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    jsonResponse(['success' => false, 'message' => "Campo {$field} requerido"], 400);
                }
            }
            
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['success' => false, 'message' => 'Correo inválido'], 400);
            }
            
            $citaData = [
                'codigo_cita' => $this->model->generarCodigoCita(),
                'nombre' => sanitize($data['nombre']),
                'correo' => sanitize($data['correo']),
                'telefono' => sanitize($data['telefono']),
                'servicio' => sanitize($data['servicio']),
                'mensaje' => sanitize($data['mensaje'] ?? ''),
                'estado' => 'pendiente',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
            
            $result = $this->model->create($citaData);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Cita registrada exitosamente',
                    'codigo_cita' => $citaData['codigo_cita']
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al registrar cita'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function index() {
        $this->checkAuth();
        
        try {
            $filtro = $_GET['filtro'] ?? 'todas';
            $busqueda = $_GET['busqueda'] ?? '';
            $limite = (int)($_GET['limite'] ?? 50);
            $pagina = (int)($_GET['pagina'] ?? 1);
            
            $resultado = $this->model->getAllWithFilters($filtro, $busqueda, $limite, $pagina);
            
            jsonResponse([
                'success' => true,
                'citas' => $resultado['citas'],
                'total' => $resultado['total'],
                'pagina' => $resultado['pagina'],
                'totalPaginas' => $resultado['totalPaginas']
            ]);
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
            
            $cita = $this->model->getById($id);
            
            if ($cita) {
                jsonResponse(['success' => true, 'cita' => $cita]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Cita no encontrada'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        $this->checkAuth();
        
        try {
            $data = $this->getRequestData();
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            $updateData = [
                'nombre' => sanitize($data['nombre'] ?? ''),
                'correo' => sanitize($data['correo'] ?? ''),
                'telefono' => sanitize($data['telefono'] ?? ''),
                'servicio' => sanitize($data['servicio'] ?? ''),
                'mensaje' => sanitize($data['mensaje'] ?? ''),
                'estado' => sanitize($data['estado'] ?? 'pendiente')
            ];
            
            $result = $this->model->update($id, $updateData);
            
            if ($result) {
                session_start();
                $usuarioModel = new UsuarioModel();
                $usuarioModel->registrarActividad($_SESSION['user_id'], 'Cita actualizada', 'Citas', "Cita ID: {$id}");
                
                jsonResponse(['success' => true, 'message' => 'Cita actualizada exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al actualizar cita'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete() {
        $this->checkAuth();
        
        try {
            $data = $this->getRequestData();
            $id = (int)($data['id'] ?? 0);
            
            if ($id == 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            $cita = $this->model->getById($id);
            
            if (!$cita) {
                jsonResponse(['success' => false, 'message' => 'Cita no encontrada'], 404);
            }
            
            $result = $this->model->delete($id);
            
            if ($result) {
                session_start();
                $usuarioModel = new UsuarioModel();
                $usuarioModel->registrarActividad($_SESSION['user_id'], 'Cita eliminada', 'Citas', "Código: {$cita['codigo_cita']}");
                
                jsonResponse(['success' => true, 'message' => 'Cita eliminada exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al eliminar cita'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function updateEstado() {
        $this->checkAuth();
        
        try {
            $data = $this->getRequestData();
            $id = (int)($data['id'] ?? 0);
            $estado = sanitize($data['estado'] ?? '');
            
            if ($id == 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            $result = $this->model->updateEstado($id, $estado);
            
            if ($result) {
                session_start();
                $usuarioModel = new UsuarioModel();
                $usuarioModel->registrarActividad($_SESSION['user_id'], 'Estado de cita actualizado', 'Citas', "Cita ID: {$id} - Estado: {$estado}");
                
                jsonResponse(['success' => true, 'message' => 'Estado actualizado exitosamente']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al actualizar estado'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function stats() {
        $this->checkAuth();
        
        try {
            $estadisticas = $this->model->getStats();
            
            jsonResponse([
                'success' => true,
                'stats' => $estadisticas['stats'],
                'servicios_top' => $estadisticas['servicios_top'],
                'citas_recientes' => $estadisticas['citas_recientes']
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}