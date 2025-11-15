<?php
require_once __DIR__ . '/../config/conexion.php';

class AnuncioModel extends BaseModel {
    protected $table = 'anuncios';
    
    public function getActivos($limit = 3) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE activo = 1 
            AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
            AND (fecha_fin IS NULL OR fecha_fin >= NOW())
            ORDER BY prioridad DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function incrementViews($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET visualizaciones = visualizaciones + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}