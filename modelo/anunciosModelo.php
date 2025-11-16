<?php
/**
 * Modelo de Anuncios - PetZone
 */

class AnunciosModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function listarTodos() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM anuncios ORDER BY prioridad DESC, id DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar anuncios: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPorId($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM anuncios WHERE id = ?");
            $stmt->execute([$id]);
            $anuncio = $stmt->fetch();
            
            if ($anuncio) {
                $anuncio['activo'] = (int)$anuncio['activo'];
            }
            
            return $anuncio;
        } catch (PDOException $e) {
            error_log("Error al obtener anuncio: " . $e->getMessage());
            return false;
        }
    }
    
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO anuncios 
                (mensaje, tipo, color_fondo, color_texto, icono, velocidad, prioridad, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $datos['mensaje'],
                $datos['tipo'],
                $datos['color_fondo'],
                $datos['color_texto'],
                $datos['icono'],
                $datos['velocidad'],
                $datos['prioridad'],
                $datos['activo']
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error al crear anuncio: " . $e->getMessage());
            return false;
        }
    }
    
    public function actualizar($id, $datos) {
        try {
            $stmt = $this->db->prepare("
                UPDATE anuncios 
                SET mensaje = ?, tipo = ?, color_fondo = ?, color_texto = ?, 
                    icono = ?, velocidad = ?, prioridad = ?, activo = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $datos['mensaje'],
                $datos['tipo'],
                $datos['color_fondo'],
                $datos['color_texto'],
                $datos['icono'],
                $datos['velocidad'],
                $datos['prioridad'],
                $datos['activo'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar anuncio: " . $e->getMessage());
            return false;
        }
    }
    
    public function eliminar($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM anuncios WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar anuncio: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerActivos() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM anuncios 
                WHERE activo = 1 
                AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
                AND (fecha_fin IS NULL OR fecha_fin >= NOW())
                ORDER BY prioridad DESC 
                LIMIT 3
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener anuncios activos: " . $e->getMessage());
            return false;
        }
    }
    
    public function incrementarVisualizaciones($id) {
        try {
            $stmt = $this->db->prepare("UPDATE anuncios SET visualizaciones = visualizaciones + 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al incrementar visualizaciones: " . $e->getMessage());
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