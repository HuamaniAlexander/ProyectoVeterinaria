<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/CategoriaModel.php';

class CategoriaController extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new CategoriaModel();
    }
    
    public function index() {
        try {
            $categorias = $this->model->getActivas();
            
            jsonResponse([
                'success' => true, 
                'categorias' => $categorias,
                'total' => count($categorias)
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}