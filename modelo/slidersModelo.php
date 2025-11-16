<?php
/**
 * Modelo de Sliders - PetZone
 * SOLO lógica de acceso a datos
 */

class SlidersModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Listar todos los sliders
     */
    public function listarTodos() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sliders ORDER BY orden ASC, id DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar sliders: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener slider por ID
     */
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sliders WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener slider: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener sliders activos (para mostrar en el sitio público)
     */
    public function obtenerActivos($limite = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM sliders 
                WHERE activo = 1 
                ORDER BY orden ASC 
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener sliders activos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener sliders por posición
     */
    public function obtenerPorPosicion($posicion = 'principal') {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM sliders 
                WHERE activo = 1 AND posicion = ? 
                ORDER BY orden ASC
            ");
            $stmt->execute([$posicion]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener sliders por posición: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo slider
     */
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sliders 
                (titulo, descripcion, imagen, enlace, posicion, orden, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $datos['titulo'],
                $datos['descripcion'],
                $datos['imagen'],
                $datos['enlace'],
                $datos['posicion'],
                $datos['orden'],
                $datos['activo']
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error al crear slider: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar slider existente
     */
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE sliders 
                SET titulo = ?, descripcion = ?, imagen = ?, enlace = ?, 
                    posicion = ?, orden = ?, activo = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['titulo'],
                $datos['descripcion'],
                $datos['imagen'],
                $datos['enlace'],
                $datos['posicion'],
                $datos['orden'],
                $datos['activo'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar slider: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar slider
     */
    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM sliders WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar slider: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar orden de slider
     */
    public function actualizarOrden($id, $orden) {
        try {
            $stmt = $this->db->prepare("UPDATE sliders SET orden = ? WHERE id = ?");
            return $stmt->execute([$orden, $id]);
        } catch (PDOException $e) {
            error_log("Error al actualizar orden: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activar/Desactivar slider
     */
    public function cambiarEstado($id, $activo) {
        try {
            $stmt = $this->db->prepare("UPDATE sliders SET activo = ? WHERE id = ?");
            return $stmt->execute([$activo, $id]);
        } catch (PDOException $e) {
            error_log("Error al cambiar estado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Contar sliders activos
     */
    public function contarActivos() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM sliders WHERE activo = 1");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar sliders activos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener siguiente orden disponible
     */
    public function obtenerSiguienteOrden() {
        try {
            $stmt = $this->db->query("SELECT COALESCE(MAX(orden), 0) + 1 as siguiente FROM sliders");
            return $stmt->fetch()['siguiente'];
        } catch (PDOException $e) {
            error_log("Error al obtener siguiente orden: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Registrar actividad administrativa
     */
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