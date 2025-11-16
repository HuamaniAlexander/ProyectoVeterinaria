<?php
/**
 * Modelo de Citas - PetZone
 * SOLO lógica de acceso a datos
 */

class CitasModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Listar todas las citas con filtros
     */
    public function listar($filtro = 'todas', $busqueda = '', $limite = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM vista_citas WHERE 1=1";
            $params = [];
            
            if ($filtro !== 'todas') {
                $sql .= " AND estado = ?";
                $params[] = $filtro;
            }
            
            if (!empty($busqueda)) {
                $sql .= " AND (nombre LIKE ? OR correo LIKE ? OR telefono LIKE ? OR codigo_cita LIKE ?)";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }
            
            $sql .= " ORDER BY fecha_solicitud DESC LIMIT ? OFFSET ?";
            $params[] = $limite;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar citas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Contar total de citas con filtros
     */
    public function contarTotal($filtro = 'todas', $busqueda = '') {
        try {
            $sql = "SELECT COUNT(*) as total FROM citas WHERE 1=1";
            $params = [];
            
            if ($filtro !== 'todas') {
                $sql .= " AND estado = ?";
                $params[] = $filtro;
            }
            
            if (!empty($busqueda)) {
                $sql .= " AND (nombre LIKE ? OR correo LIKE ? OR telefono LIKE ? OR codigo_cita LIKE ?)";
                $busquedaParam = "%{$busqueda}%";
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
                $params[] = $busquedaParam;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar citas: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener cita por ID
     */
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM citas WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener cita: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nueva cita
     */
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO citas 
                (codigo_cita, nombre, correo, telefono, servicio, mensaje, estado, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)
            ");
            
            return $stmt->execute([
                $datos['codigo_cita'],
                $datos['nombre'],
                $datos['correo'],
                $datos['telefono'],
                $datos['servicio'],
                $datos['mensaje'],
                $datos['ip_address']
            ]);
        } catch (PDOException $e) {
            error_log("Error al crear cita: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar cita
     */
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE citas 
                SET nombre = ?, correo = ?, telefono = ?, servicio = ?, mensaje = ?, estado = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['correo'],
                $datos['telefono'],
                $datos['servicio'],
                $datos['mensaje'],
                $datos['estado'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar cita: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar solo el estado
     */
    public function actualizarEstado($id, $estado) {
        try {
            $stmt = $this->db->prepare("UPDATE citas SET estado = ? WHERE id = ?");
            return $stmt->execute([$estado, $id]);
        } catch (PDOException $e) {
            error_log("Error al actualizar estado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar cita
     */
    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM citas WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar cita: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de citas
     */
    public function obtenerEstadisticas() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                    SUM(CASE WHEN DATE(fecha_solicitud) = CURDATE() THEN 1 ELSE 0 END) as hoy,
                    SUM(CASE WHEN WEEK(fecha_solicitud) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as esta_semana,
                    SUM(CASE WHEN MONTH(fecha_solicitud) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as este_mes
                FROM citas
            ");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener servicios más solicitados
     */
    public function obtenerServiciosTop($limite = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT servicio, COUNT(*) as total 
                FROM citas 
                GROUP BY servicio 
                ORDER BY total DESC 
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener servicios top: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener citas recientes pendientes
     */
    public function obtenerRecientesPendientes($limite = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM vista_citas 
                WHERE estado = 'pendiente' 
                ORDER BY fecha_solicitud DESC 
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener citas recientes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar código único para cita
     */
    public function generarCodigoCita() {
        return 'CITA-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
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