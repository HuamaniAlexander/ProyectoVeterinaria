<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/ServicioModel.php';

class ServicioController extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new ServicioModel();
    }
    
    public function index() {
        try {
            $servicios = $this->model->getDisponibles();
            
            jsonResponse(['success' => true, 'servicios' => $servicios]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $id = $_GET['id'] ?? null;
            $slug = $_GET['slug'] ?? null;
            
            if (!$id && !$slug) {
                jsonResponse(['success' => false, 'message' => 'ID o slug requerido'], 400);
            }
            
            $servicio = $id ? $this->model->getById($id) : $this->model->getBySlug($slug);
            
            if ($servicio) {
                // Decodificar características si no se hizo en el modelo
                if (isset($servicio['caracteristicas']) && is_string($servicio['caracteristicas'])) {
                    $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
                }
                
                jsonResponse(['success' => true, 'servicio' => $servicio]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Servicio no encontrado'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function disponibles() {
        $this->index(); // Alias para compatibilidad
    }
}