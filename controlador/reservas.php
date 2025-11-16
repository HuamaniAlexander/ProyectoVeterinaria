<?php
/**
 * Controlador de Reservas - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/reservasModelo.php';
require_once __DIR__ . '/../modelo/serviciosModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $requestData['action'] ?? $_GET['action'] ?? '';

$reservasModelo = new ReservasModelo();
$serviciosModelo = new ServiciosModelo();

error_log("RESERVAS.PHP - Action: " . $action);

try {
    switch($action) {
        case 'crear':
            handleCrear($reservasModelo, $serviciosModelo, $requestData);
            break;
        case 'list':
            handleList($reservasModelo);
            break;
        case 'verificar-disponibilidad':
            handleVerificarDisponibilidad($reservasModelo, $requestData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("RESERVAS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleCrear($reservasModelo, $serviciosModelo, $data) {
    $servicio_id = (int)($data['servicio_id'] ?? 0);
    $nombre = sanitize($data['nombre'] ?? '');
    $email = sanitize($data['email'] ?? '');
    $telefono = sanitize($data['telefono'] ?? '');
    $nombre_mascota = sanitize($data['nombre_mascota'] ?? '');
    $tipo_mascota = sanitize($data['tipo_mascota'] ?? 'perro');
    $fecha_reserva = sanitize($data['fecha_reserva'] ?? '');
    $hora_reserva = sanitize($data['hora_reserva'] ?? '');
    $notas = sanitize($data['notas'] ?? '');
    
    // Validaciones
    if ($servicio_id == 0) {
        jsonResponse(['success' => false, 'message' => 'Servicio no seleccionado'], 400);
    }
    
    if (empty($nombre) || empty($email) || empty($telefono) || empty($nombre_mascota)) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    if (empty($fecha_reserva) || empty($hora_reserva)) {
        jsonResponse(['success' => false, 'message' => 'Fecha y hora requeridas'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Email inválido'], 400);
    }
    
    if (strtotime($fecha_reserva) < strtotime(date('Y-m-d'))) {
        jsonResponse(['success' => false, 'message' => 'No se pueden hacer reservas en el pasado'], 400);
    }
    
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        $servicio = $serviciosModelo->obtenerPorId($servicio_id);
        
        if (!$servicio) {
            jsonResponse(['success' => false, 'message' => 'Servicio no disponible'], 404);
        }
        
        $reservasSimultaneas = $reservasModelo->verificarDisponibilidad($servicio_id, $fecha_reserva, $hora_reserva);
        
        if ($reservasSimultaneas >= 3) {
            jsonResponse(['success' => false, 'message' => 'Este horario ya no está disponible'], 400);
        }
        
        $codigo_reserva = $reservasModelo->generarCodigoReserva();
        $precio = $servicio['precio'];
        
        $datos = [
            'codigo_reserva' => $codigo_reserva,
            'servicio_id' => $servicio_id,
            'nombre_cliente' => $nombre,
            'email_cliente' => $email,
            'telefono_cliente' => $telefono,
            'nombre_mascota' => $nombre_mascota,
            'tipo_mascota' => $tipo_mascota,
            'fecha_reserva' => $fecha_reserva,
            'hora_reserva' => $hora_reserva,
            'notas' => $notas,
            'subtotal' => $precio,
            'total' => $precio
        ];
        
        $result = $reservasModelo->crear($datos);
        
        $db->commit();
        
        if ($result) {
            error_log("RESERVA CREADA - Código: " . $codigo_reserva);
            
            jsonResponse([
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'codigo_reserva' => $codigo_reserva,
                'servicio' => $servicio['nombre'],
                'fecha' => $fecha_reserva,
                'hora' => $hora_reserva,
                'total' => $precio
            ]);
        } else {
            throw new Exception('Error al crear la reserva');
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("ERROR AL CREAR RESERVA: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Error al crear reserva: ' . $e->getMessage()], 500);
    }
}

function handleList($modelo) {
    $reservas = $modelo->listar();
    
    if ($reservas !== false) {
        jsonResponse(['success' => true, 'reservas' => $reservas]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar reservas'], 500);
    }
}

function handleVerificarDisponibilidad($modelo, $data) {
    $servicio_id = (int)($data['servicio_id'] ?? 0);
    $fecha = sanitize($data['fecha'] ?? '');
    $hora = sanitize($data['hora'] ?? '');
    
    if ($servicio_id == 0 || empty($fecha) || empty($hora)) {
        jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    $reservasSimultaneas = $modelo->verificarDisponibilidad($servicio_id, $fecha, $hora);
    $disponible = $reservasSimultaneas < 3;
    
    jsonResponse([
        'success' => true,
        'disponible' => $disponible,
        'reservas_actuales' => $reservasSimultaneas,
        'limite' => 3
    ]);
}