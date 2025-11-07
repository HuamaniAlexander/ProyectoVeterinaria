<?php
require_once __DIR__ . '/../core/Model.php';

class Slider extends Model {
    protected $table = 'sliders';
    
    // Obtener sliders activos ordenados
    public function getActivos() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1 ORDER BY orden ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Reordenar sliders
    public function reordenar($ordenamiento) {
        foreach ($ordenamiento as $id => $orden) {
            $this->update($id, ['orden' => $orden]);
        }
        return true;
    }
    
    // Obtener último ID insertado
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }
}