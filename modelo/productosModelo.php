<?php
/**
 * Modelo de Productos - PetZone
 */

class ProductosModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function listarTodos($categoria = '', $busqueda = '') {
        try {
            $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
                    FROM productos p 
                    INNER JOIN categorias c ON p.categoria_id = c.id 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($categoria)) {
                $sql .= " AND p.categoria_id = ?";
                $params[] = $categoria;
            }
            
            if (!empty($busqueda)) {
                $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
                $params[] = "%{$busqueda}%";
                $params[] = "%{$busqueda}%";
            }
            
            $sql .= " ORDER BY p.destacado DESC, p.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar productos: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener producto: " . $e->getMessage());
            return false;
        }
    }
    
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO productos 
                (nombre, descripcion, categoria_id, precio, stock, imagen, codigo_sku, destacado, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'],
                $datos['categoria_id'],
                $datos['precio'],
                $datos['stock'],
                $datos['imagen'],
                $datos['codigo_sku'],
                $datos['destacado']
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error al crear producto: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE productos 
                SET nombre = ?, descripcion = ?, categoria_id = ?, precio = ?, 
                    stock = ?, imagen = ?, codigo_sku = ?, destacado = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'],
                $datos['categoria_id'],
                $datos['precio'],
                $datos['stock'],
                $datos['imagen'],
                $datos['codigo_sku'],
                $datos['destacado'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar producto: " . $e->getMessage());
            return false;
        }
    }
    
    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM productos WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar producto: " . $e->getMessage());
            return false;
        }
    }
    
    public function verificarStock($id, $cantidad) {
        try {
            $stmt = $this->db->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            $producto = $stmt->fetch();
            
            return $producto && $producto['stock'] >= $cantidad;
        } catch (PDOException $e) {
            error_log("Error al verificar stock: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizarStock($id, $cantidad) {
        try {
            $stmt = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            return $stmt->execute([$cantidad, $id]);
        } catch (PDOException $e) {
            error_log("Error al actualizar stock: " . $e->getMessage());
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