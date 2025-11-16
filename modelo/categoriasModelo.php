<?php
/**
 * Modelo de Categorías - PetZone
 */

class CategoriasModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function obtenerActivas() {
        try {
            $stmt = $this->db->query("
                SELECT id, nombre, slug, descripcion, icono, orden, activo
                FROM categorias 
                WHERE activo = 1 
                ORDER BY orden ASC, nombre ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerTodas() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM categorias 
                ORDER BY orden ASC, nombre ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener todas las categorías: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener categoría por ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorSlug($slug) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categorias WHERE slug = ?");
            $stmt->execute([$slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener categoría por slug: " . $e->getMessage());
            return false;
        }
    }
}