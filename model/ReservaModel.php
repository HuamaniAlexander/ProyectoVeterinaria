<?php
require_once __DIR__ . '/../config/conexion.php';

class ReservaModel extends BaseModel {
    protected $table = 'reservas';
    
    public function generarCodigoReserva() {
        return 'RES-' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    public function verificarDisponibilidad($servicioId, $fecha, $hora, $limite = 3) {
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
        
        return [
            'disponible' => $result['reservas_simultaneas'] < $limite,
            'reservas_actuales' => (int)$result['reservas_simultaneas'],
            'limite' => $limite
        ];
    }
    
    public function getAllWithService($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT r.*, s.nombre as servicio_nombre
            FROM reservas r
            INNER JOIN servicios s ON r.servicio_id = s.id
            ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function createWithTransaction($data) {
        try {
            $this->db->beginTransaction();
            
            // Verificar servicio disponible
            $stmt = $this->db->prepare("SELECT * FROM servicios WHERE id = ? AND disponible = 1");
            $stmt->execute([$data['servicio_id']]);
            $servicio = $stmt->fetch();
            
            if (!$servicio) {
                throw new Exception('Servicio no disponible');
            }
            
            // Verificar disponibilidad horaria
            $disponibilidad = $this->verificarDisponibilidad(
                $data['servicio_id'], 
                $data['fecha_reserva'], 
                $data['hora_reserva']
            );
            
            if (!$disponibilidad['disponible']) {
                throw new Exception('Horario no disponible');
            }
            
            // Generar código
            $data['codigo_reserva'] = $this->generarCodigoReserva();
            $data['subtotal'] = $servicio['precio'];
            $data['total'] = $servicio['precio'];
            $data['estado'] = 'pendiente';
            
            // Crear reserva
            $this->create($data);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'codigo_reserva' => $data['codigo_reserva'],
                'servicio' => $servicio['nombre'],
                'fecha' => $data['fecha_reserva'],
                'hora' => $data['hora_reserva'],
                'total' => $servicio['precio']
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}