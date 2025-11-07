<?php
require_once __DIR__ . '/../core/Model.php';

class Reserva extends Model {
    protected $table = 'reservas';
    
    // Obtener reservas por estado
    public function getByEstado($estado) {
        $query = "SELECT * FROM " . $this->table . " WHERE estado = ? ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener reservas de hoy
    public function getHoy() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE DATE(fecha_creacion) = CURDATE() 
                  ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cambiar estado de reserva
    public function cambiarEstado($id, $estado) {
        return $this->update($id, ['estado' => $estado]);
    }
    
    // Obtener último ID insertado
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }
}