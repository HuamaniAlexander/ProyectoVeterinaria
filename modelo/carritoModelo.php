<?php
/**
 * Modelo de Carrito - PetZone
 */

class CarritoModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function obtenerItems($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.nombre, p.imagen, p.descripcion, p.stock
                FROM carrito c
                INNER JOIN productos p ON c.producto_id = p.id
                WHERE c.session_id = ?
                ORDER BY c.fecha_agregado DESC
            ");
            $stmt->execute([$sessionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener items del carrito: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerTotales($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as count,
                    SUM(cantidad) as total_items,
                    SUM(cantidad * precio_unitario) as subtotal
                FROM carrito
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            $totales = $stmt->fetch();
            
            return [
                'count' => (int)$totales['count'],
                'total_items' => (int)$totales['total_items'],
                'subtotal' => (float)$totales['subtotal'],
                'total' => (float)$totales['subtotal']
            ];
        } catch (PDOException $e) {
            error_log("Error al obtener totales del carrito: " . $e->getMessage());
            return false;
        }
    }
    
    public function verificarItemExistente($sessionId, $productoId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM carrito WHERE session_id = ? AND producto_id = ?");
            $stmt->execute([$sessionId, $productoId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al verificar item existente: " . $e->getMessage());
            return false;
        }
    }
    
    public function agregarItem($sessionId, $productoId, $cantidad, $precio) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO carrito (session_id, producto_id, cantidad, precio_unitario) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$sessionId, $productoId, $cantidad, $precio]);
        } catch (PDOException $e) {
            error_log("Error al agregar item: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarCantidad($sessionId, $productoId, $cantidad, $precio = null) {
        try {
            if ($precio !== null) {
                $stmt = $this->db->prepare("
                    UPDATE carrito 
                    SET cantidad = ?, precio_unitario = ? 
                    WHERE session_id = ? AND producto_id = ?
                ");
                return $stmt->execute([$cantidad, $precio, $sessionId, $productoId]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE carrito 
                    SET cantidad = ? 
                    WHERE session_id = ? AND producto_id = ?
                ");
                return $stmt->execute([$cantidad, $sessionId, $productoId]);
            }
        } catch (PDOException $e) {
            error_log("Error al actualizar cantidad: " . $e->getMessage());
            return false;
        }
    }
    
    public function eliminarItem($sessionId, $productoId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM carrito WHERE session_id = ? AND producto_id = ?");
            return $stmt->execute([$sessionId, $productoId]);
        } catch (PDOException $e) {
            error_log("Error al eliminar item: " . $e->getMessage());
            return false;
        }
    }
    
    public function vaciar($sessionId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM carrito WHERE session_id = ?");
            return $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            error_log("Error al vaciar carrito: " . $e->getMessage());
            return false;
        }
    }
}