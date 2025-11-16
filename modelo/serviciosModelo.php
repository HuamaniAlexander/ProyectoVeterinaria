<?php
/**
 * Modelo de Servicios - PetZone
 */

class ServiciosModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function listar() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM servicios 
                WHERE disponible = 1 
                ORDER BY orden ASC, nombre ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar servicios: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM servicios WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener servicio: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorSlug($slug) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM servicios WHERE slug = ?");
            $stmt->execute([$slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener servicio por slug: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerDisponibles() {
        try {
            $stmt = $this->db->query("SELECT * FROM vista_servicios_disponibles");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener servicios disponibles: " . $e->getMessage());
            return false;
        }
    }
}