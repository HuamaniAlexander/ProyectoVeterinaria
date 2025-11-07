<?php
require_once __DIR__ . '/../core/Model.php';

class Anuncio extends Model {
    protected $table = 'anuncios';
    
    // Obtener anuncios activos y vigentes
    public function getVigentes() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE activo = 1 
                  AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
                  AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                  ORDER BY fecha_inicio DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener anuncios por tipo
    public function getByTipo($tipo) {
        $query = "SELECT * FROM " . $this->table . " WHERE tipo = ? ORDER BY fecha_inicio DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}