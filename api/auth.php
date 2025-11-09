<?php
/**
 * API de Autenticación - PetZone
 */

require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch($action) {
        case 'login':
            login();
            break;
        case 'logout':
            logout();
            break;
        case 'check':
            checkSession();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function login() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = sanitize($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Usuario y contraseña requeridos'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Actualizar último acceso
        $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // Guardar en sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        
        // Registrar actividad
        registrarActividad($db, $user['id'], 'Inicio de sesión', 'Autenticación', 'Login exitoso');
        
        unset($user['password']); // No enviar password
        
        jsonResponse([
            'success' => true,
            'message' => 'Login exitoso',
            'user' => $user
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Credenciales inválidas'], 401);
    }
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        registrarActividad($db, $_SESSION['user_id'], 'Cierre de sesión', 'Autenticación', 'Logout');
    }
    
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Sesión cerrada']);
}

function checkSession() {
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, nombre_completo, email, rol FROM usuarios WHERE id = ? AND activo = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            jsonResponse([
                'authenticated' => true,
                'user' => $user
            ]);
        }
    }
    
    jsonResponse(['authenticated' => false]);
}

function registrarActividad($db, $userId, $accion, $modulo, $detalle = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $accion,
            $modulo,
            $detalle,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}
?>