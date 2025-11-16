<?php
/**
 * Modelo de Pedidos - PetZone
 */

class PedidosModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function listar($estado = '', $fechaDesde = '', $fechaHasta = '') {
        try {
            $sql = "SELECT * FROM pedidos WHERE 1=1";
            $params = [];
            
            if (!empty($estado)) {
                $sql .= " AND estado = ?";
                $params[] = $estado;
            }
            
            if (!empty($fechaDesde)) {
                $sql .= " AND DATE(fecha_pedido) >= ?";
                $params[] = $fechaDesde;
            }
            
            if (!empty($fechaHasta)) {
                $sql .= " AND DATE(fecha_pedido) <= ?";
                $params[] = $fechaHasta;
            }
            
            $sql .= " ORDER BY fecha_pedido DESC LIMIT 100";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $
fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar pedidos: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener pedido: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerDetalles($pedidoId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM detalle_pedidos WHERE pedido_id = ?");
            $stmt->execute([$pedidoId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener detalles del pedido: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarEstado($id, $estado) {
        try {
            $stmt = $this->db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            return $stmt->execute([$estado, $id]);
        } catch (PDOException $e) {
            error_log("Error al actualizar estado: " . $e->getMessage());
            return false;
        }
    }
    
    public function registrarActividad($userId, $accion, $modulo, $detalle = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $userId,
                $accion,
                $modulo,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error al registrar actividad: " . $e->getMessage());
            return false;
        }
    }
}