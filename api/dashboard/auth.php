<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        Response::error('Usuario y contraseña son requeridos', 422);
    }
    
    $usuarioModel = new Usuario();
    $usuario = $usuarioModel->authenticate($username, $password);
    
    if ($usuario) {
        // Crear sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['rol'] = $usuario['rol'];
        
        // Generar token simple (en producción usar JWT)
        $token = bin2hex(random_bytes(32));
        $_SESSION['token'] = $token;
        
        Response::success([
            'token' => $token,
            'user' => [
                'id' => $usuario['id'],
                'username' => $usuario['username'],
                'rol' => $usuario['rol']
            ]
        ], 'Login exitoso');
    } else {
        Response::error('Credenciales inválidas', 401);
    }
    
} elseif ($method === 'DELETE') {
    // Logout
    session_destroy();
    Response::success([], 'Sesión cerrada exitosamente');
    
} else {
    Response::error('Método no permitido', 405);
}