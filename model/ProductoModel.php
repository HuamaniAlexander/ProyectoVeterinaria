<?php
require_once __DIR__ . '/../config/conexion.php';

class ProductoModel {
    private $db;
    private $table = 'productos';
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllWithCategory($categoria = '', $busqueda = '') {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
                FROM {$this->table} p 
                INNER JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1";
        $params = [];
        
        if (!empty($categoria)) {
            $sql .= " AND p.categoria_id = ?";
            $params[] = $categoria;
        }
        
        if (!empty($busqueda)) {
            $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $searchTerm = "%{$busqueda}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY p.destacado DESC, p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})");
        return $stmt->execute(array_values($data));
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        $params[] = $id;
        
        $setClause = implode(', ', $fields);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$setClause} WHERE id = ?");
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function updateStock($productId, $quantity) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET stock = stock - ?, ventas = ventas + ? 
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $quantity, $productId]);
    }
}