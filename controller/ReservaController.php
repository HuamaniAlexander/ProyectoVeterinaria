<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../model/ReservaModel.php';

class ReservaController extends BaseController {
    protected $requireAuth = false;
    
    public function __construct() {
        $this->model = new ReservaModel();
    }
    
    public function crear() {
        try {
            $data = $this->getRequestData();
            
            // Validaciones
            $required = ['servicio_id', 'nombre', 'email', 'telefono', 'nombre_mascota', 'fecha_reserva', 'hora_reserva'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    jsonResponse(['success' => false, 'message' => "Campo {$field} requerido"], 400);
                }
            }
            
            // Validar email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['success' => false, 'message' => 'Email inválido'], 400);
            }
            
            // Validar fecha no en el pasado
            if (strtotime($data['fecha_reserva']) < strtotime(date('Y-m-d'))) {
                jsonResponse(['success' => false, 'message' => 'No se pueden hacer reservas en el pasado'], 400);
            }
            
            // Sanitizar datos
            $reservaData = [
                'servicio_id' => (int)$data['servicio_id'],
                'nombre_cliente' => sanitize($data['nombre']),
                'email_cliente' => sanitize($data['email']),
                'telefono_cliente' => sanitize($data['telefono']),
                'nombre_mascota' => sanitize($data['nombre_mascota']),
                'tipo_mascota' => sanitize($data['tipo_mascota'] ?? 'perro'),
                'fecha_reserva' => sanitize($data['fecha_reserva']),
                'hora_reserva' => sanitize($data['hora_reserva']),
                'notas' => sanitize($data['notas'] ?? '')
            ];
            
            // Crear reserva con transacción
            $result = $this->model->createWithTransaction($reservaData);
            
            jsonResponse([
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'codigo_reserva' => $result['codigo_reserva'],
                'servicio' => $result['servicio'],
                'fecha' => $result['fecha'],
                'hora' => $result['hora'],
                'total' => $result['total']
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function index() {
        $this->checkAuth();
        
        try {
            $reservas = $this->model->getAllWithService(100);
            
            jsonResponse(['success' => true, 'reservas' => $reservas]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function verificar() {
        try {
            $data = $this->getRequestData();
            
            $servicioId = (int)($data['servicio_id'] ?? 0);
            $fecha = sanitize($data['fecha'] ?? '');
            $hora = sanitize($data['hora'] ?? '');
            
            if ($servicioId == 0 || empty($fecha) || empty($hora)) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
            }
            
            $disponibilidad = $this->model->verificarDisponibilidad($servicioId, $fecha, $hora);
            
            jsonResponse([
                'success' => true,
                'disponible' => $disponibilidad['disponible'],
                'reservas_actuales' => $disponibilidad['reservas_actuales'],
                'limite' => $disponibilidad['limite']
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}