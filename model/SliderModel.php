<?php
require_once __DIR__ . '/../config/conexion.php';

class SliderModel extends BaseModel {
    protected $table = 'sliders';
    
    public function getActivos($limit = 5) {
        return $this->getAll(['activo' => 1], 'orden ASC', $limit);
    }
    
    public function incrementClicks($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET clicks = clicks + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}