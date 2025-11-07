<?php
require_once __DIR__ . '/../core/Model.php';

class Producto extends Model {
    protected $table = 'productos';
    
    // Obtener productos por categoría
    public function getByCategory($categoria) {
        if ($categoria === 'todos') {
            return $this->getAll();
        }
        
        $query = "SELECT * FROM " . $this->table . " WHERE categoria = ? ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$categoria]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener categorías únicas
    public function getCategories() {
        $query = "SELECT DISTINCT categoria FROM " . $this->table . " ORDER BY categoria";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Buscar productos
    public function search($termino) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE nombre LIKE ? OR descripcion LIKE ? 
                  ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $search = "%$termino%";
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}