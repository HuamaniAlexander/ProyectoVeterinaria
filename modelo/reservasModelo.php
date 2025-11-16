<?php
/**
 * Modelo de Reservas - PetZone
 */

class ReservasModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function crear($datos) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reservas (
                    codigo_reserva, servicio_id, nombre_cliente, email_cliente, 
                    telefono_cliente, nombre_mascota, tipo_mascota, fecha_reserva, 
                    hora_reserva, notas, subtotal, total, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $result = $stmt->execute([
                $datos['codigo_reserva'],
                $datos['servicio_id'],
                $datos['nombre_cliente'],
                $datos['email_cliente'],
                $datos['telefono_cliente'],
                $datos['nombre_mascota'],
                $datos['tipo_mascota'],
                $datos['fecha_reserva'],
                $datos['hora_reserva'],
                $datos['notas'],
                $datos['subtotal'],
                $datos['total']
            ]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error al crear reserva: " . $e->getMessage());
            return false;
        }
    }
    
    public function listar() {
        try {
            $stmt = $this->db->query("
                SELECT r.*, s.nombre as servicio_nombre
                FROM reservas r
                INNER JOIN servicios s ON r.servicio_id = s.id
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
                LIMIT 100
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al listar reservas: " . $e->getMessage());
            return false;
        }
    }
    
    public function verificarDisponibilidad($servicioId, $fecha, $hora) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as reservas_simultaneas 
                FROM reservas 
                WHERE servicio_id = ? 
                AND fecha_reserva = ? 
                AND hora_reserva = ?
                AND estado NOT IN ('cancelada')
            ");
            $stmt->execute([$servicioId, $fecha, $hora]);
            $result = $stmt->fetch();
            
            return (int)$result['reservas_simultaneas'];
        } catch (PDOException $e) {
            error_log("Error al verificar disponibilidad: " . $e->getMessage());
            return false;
        }
    }
    
    public function generarCodigoReserva() {
        return 'RES-' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}