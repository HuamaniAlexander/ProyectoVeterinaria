<?php
/**
 * Controlador de Autenticación - PetZone
 * Maneja las peticiones HTTP relacionadas con autenticación
 * Archivo: controlador/auth.php
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Output buffering
ob_start();

// Incluir dependencias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/authModelo.php';

// Limpiar cualquier salida previa
ob_end_clean();

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos de la petición
$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $requestData['action'] ?? $_POST['action'] ?? '';

// Log para debug
error_log("AUTH CONTROLLER - Action: " . $action);

// Instanciar modelo
$authModelo = new AuthModelo();

// Enrutamiento de acciones
try {
    switch($action) {
        case 'login':
            handleLogin($authModelo, $requestData);
            break;
            
        case 'logout':
            handleLogout($authModelo);
            break;
            
        case 'check':
            handleCheckSession($authModelo);
            break;
            
        default:
            jsonResponse([
                'success' => false, 
                'message' => 'Acción no válida: ' . $action
            ], 400);
    }
} catch (Exception $e) {
    error_log("AUTH CONTROLLER - Exception: " . $e->getMessage());
    jsonResponse([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ], 500);
}

// ================================================
// FUNCIONES DEL CONTROLADOR
// ================================================

/**
 * Manejar proceso de login
 */
function handleLogin($authModelo, $requestData) {
    $username = sanitize($requestData['username'] ?? '');
    $password = $requestData['password'] ?? '';
    
    error_log("LOGIN - Username: " . $username);
    
    // Validar datos de entrada
    $validation = $authModelo->validateLoginData($username, $password);
    
    if (!$validation['valid']) {
        jsonResponse([
            'success' => false,
            'message' => implode(', ', $validation['errors'])
        ], 400);
        return;
    }
    
    // Verificar si el usuario está bloqueado (opcional)
    if ($authModelo->isUserBlocked($username)) {
        jsonResponse([
            'success' => false,
            'message' => 'Usuario temporalmente bloqueado. Intente más tarde.'
        ], 403);
        return;
    }
    
    // Buscar usuario en la base de datos
    $user = $authModelo->getUserByUsername($username);
    
    if (!$user) {
        error_log("LOGIN - Usuario no encontrado: " . $username);
        
        // Registrar intento fallido (opcional)
        $authModelo->registerFailedAttempt($username);
        
        jsonResponse([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos'
        ], 401);
        return;
    }
    
    // Verificar contraseña
    if (!$authModelo->verifyPassword($password, $user['password'])) {
        error_log("LOGIN - Contraseña incorrecta para: " . $username);
        
        // Registrar intento fallido (opcional)
        $authModelo->registerFailedAttempt($username);
        
        jsonResponse([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos'
        ], 401);
        return;
    }
    
    // ✅ LOGIN EXITOSO
    error_log("LOGIN - Login exitoso para: " . $username);
    
    // Actualizar último acceso
    $authModelo->updateLastAccess($user['id']);
    
    // Guardar datos en sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['rol'] = $user['rol'];
    
    // Registrar actividad
    $authModelo->registrarActividad(
        $user['id'],
        'Inicio de sesión',
        'Autenticación',
        'Login exitoso',
        $_SERVER['REMOTE_ADDR'] ?? null
    );
    
    // Limpiar datos sensibles antes de enviar
    $userData = $authModelo->sanitizeUserData($user);
    
    jsonResponse([
        'success' => true,
        'message' => 'Login exitoso',
        'user' => $userData
    ]);
}

/**
 * Manejar cierre de sesión
 */
function handleLogout($authModelo) {
    if (isset($_SESSION['user_id'])) {
        // Registrar actividad antes de cerrar sesión
        $authModelo->registrarActividad(
            $_SESSION['user_id'],
            'Cierre de sesión',
            'Autenticación',
            'Logout',
            $_SERVER['REMOTE_ADDR'] ?? null
        );
    }
    
    // Destruir sesión
    session_destroy();
    
    jsonResponse([
        'success' => true,
        'message' => 'Sesión cerrada exitosamente'
    ]);
}

/**
 * Verificar si hay sesión activa
 */
function handleCheckSession($authModelo) {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse([
            'authenticated' => false
        ]);
        return;
    }
    
    // Verificar que el usuario aún existe y está activo
    $user = $authModelo->getUserById($_SESSION['user_id']);
    
    if (!$user) {
        // Usuario no existe o está inactivo - destruir sesión
        session_destroy();
        
        jsonResponse([
            'authenticated' => false,
            'message' => 'Sesión inválida'
        ]);
        return;
    }
    
    // Limpiar datos sensibles
    $userData = $authModelo->sanitizeUserData($user);
    
    jsonResponse([
        'authenticated' => true,
        'user' => $userData
    ]);
}