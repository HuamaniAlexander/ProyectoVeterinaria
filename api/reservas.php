<?php
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];
$reserva = new Reserva();

if ($method === 'POST') {
    // Recibir datos del formulario
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos
    if (empty($input['nombre']) || empty($input['correo']) || empty($input['telefono'])) {
        Response::error('Todos los campos son obligatorios', 422);
    }
    
    // Validar email
    if (!filter_var($input['correo'], FILTER_VALIDATE_EMAIL)) {
        Response::error('El correo electrónico no es válido', 422);
    }
    
    // Crear reserva
    $data = [
        'nombre' => htmlspecialchars($input['nombre']),
        'correo' => htmlspecialchars($input['correo']),
        'telefono' => htmlspecialchars($input['telefono']),
        'servicio' => htmlspecialchars($input['servicio']),
        'mensaje' => htmlspecialchars($input['mensaje'] ?? ''),
        'fecha_creacion' => date('Y-m-d H:i:s')
    ];
    
    if ($reserva->create($data)) {
        // Opcional: Enviar email de confirmación
        // mail($input['correo'], 'Reserva Confirmada', '...');
        
        Response::success(['id' => $reserva->getLastInsertId()], 'Reserva creada exitosamente');
    } else {
        Response::error('Error al crear la reserva');
    }
} else {
    Response::error('Método no permitido', 405);
}