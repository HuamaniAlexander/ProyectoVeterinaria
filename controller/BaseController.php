<?php
abstract class BaseController {
    protected $model;
    protected $requireAuth = true;
    
    public function __construct() {
        if ($this->requireAuth) {
            $this->checkAuth();
        }
    }
    
    protected function checkAuth() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }
    }
    
    protected function getRequestData() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }
        return $data;
    }
    
    protected function handleImageUpload($file, $folder) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Archivo demasiado grande (máx 5MB)');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
        $rutaDestino = __DIR__ . "/../public/img/{$folder}/";
        
        if (!file_exists($rutaDestino)) {
            mkdir($rutaDestino, 0777, true);
        }
        
        $rutaCompleta = $rutaDestino . $nombreArchivo;
        
        if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            return "img/{$folder}/{$nombreArchivo}";
        }
        
        throw new Exception('Error al subir imagen');
    }
    
    protected function deleteImage($imagePath) {
        if ($imagePath) {
            $fullPath = __DIR__ . "/../public/{$imagePath}";
            if (file_exists($fullPath)) {
                return unlink($fullPath);
            }
        }
        return false;
    }
}