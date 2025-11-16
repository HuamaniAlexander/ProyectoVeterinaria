<?php
/**
 * Modelo de Contenido General - PetZone
 * Gestión de contenido dinámico del sitio
 */

class ContenidoModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtener todo el contenido o filtrado por sección
     */
    public function obtener($seccion = '') {
        try {
            $sql = "SELECT * FROM contenido_general WHERE 1=1";
            $params = [];
            
            if (!empty($seccion)) {
                $sql .= " AND seccion = ?";
                $params[] = $seccion;
            }
            
            $sql .= " ORDER BY seccion, clave";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener contenido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener contenido por sección y clave específica
     */
    public function obtenerPorClave($seccion, $clave) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM contenido_general 
                WHERE seccion = ? AND clave = ?
            ");
            $stmt->execute([$seccion, $clave]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener contenido por clave: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar contenido por sección (múltiples claves)
     */
    public function actualizarSeccion($seccion, $contenidos) {
        try {
            $this->db->beginTransaction();
            
            foreach ($contenidos as $clave => $valor) {
                $stmt = $this->db->prepare("
                    UPDATE contenido_general 
                    SET valor = ? 
                    WHERE seccion = ? AND clave = ? AND editable = 1
                ");
                $stmt->execute([$valor, $seccion, $clave]);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al actualizar sección: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar un contenido específico
     */
    public function actualizar($seccion, $clave, $valor) {
        try {
            $stmt = $this->db->prepare("
                UPDATE contenido_general 
                SET valor = ? 
                WHERE seccion = ? AND clave = ? AND editable = 1
            ");
            return $stmt->execute([$valor, $seccion, $clave]);
        } catch (PDOException $e) {
            error_log("Error al actualizar contenido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo contenido
     */
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO contenido_general 
                (seccion, clave, valor, tipo, editable, descripcion) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $datos['seccion'],
                $datos['clave'],
                $datos['valor'],
                $datos['tipo'] ?? 'texto',
                $datos['editable'] ?? 1,
                $datos['descripcion'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error al crear contenido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las secciones disponibles
     */
    public function obtenerSecciones() {
        try {
            $stmt = $this->db->query("
                SELECT DISTINCT seccion 
                FROM contenido_general 
                ORDER BY seccion
            ");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error al obtener secciones: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si un contenido es editable
     */
    public function esEditable($seccion, $clave) {
        try {
            $stmt = $this->db->prepare("
                SELECT editable FROM contenido_general 
                WHERE seccion = ? AND clave = ?
            ");
            $stmt->execute([$seccion, $clave]);
            $result = $stmt->fetch();
            return $result ? (bool)$result['editable'] : false;
        } catch (PDOException $e) {
            error_log("Error al verificar editable: " . $e->getMessage());
            return false;
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