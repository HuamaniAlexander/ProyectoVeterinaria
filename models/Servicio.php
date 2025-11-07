<?php
require_once __DIR__ . '/../core/Model.php';

class Servicio extends Model {
    protected $table = 'servicios';
    
    // Obtener servicios activos
    public function getActivos() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1 ORDER BY orden, nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener características del servicio como array
    public function getCaracteristicas($id) {
        $servicio = $this->getById($id);
        if ($servicio && isset($servicio['caracteristicas'])) {
            return json_decode($servicio['caracteristicas'], true);
        }
        return [];
    }
}