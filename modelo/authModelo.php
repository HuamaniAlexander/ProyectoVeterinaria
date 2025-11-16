<?php
/**
 * Modelo de Autenticación - PetZone
 * Maneja toda la lógica de negocio relacionada con usuarios
 * Archivo: modelo/authModelo.php
 */

class AuthModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Buscar usuario por username
     * @param string $username
     * @return array|false Datos del usuario o false si no existe
     */
    public function getUserByUsername($username) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM usuarios 
                WHERE username = ? AND activo = 1
            ");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al buscar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar usuario por ID
     * @param int $userId
     * @return array|false
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, nombre_completo, email, rol 
                FROM usuarios 
                WHERE id = ? AND activo = 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al buscar usuario por ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar contraseña
     * @param string $password Contraseña en texto plano
     * @param string $hash Hash almacenado en BD
     * @return bool
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Actualizar último acceso del usuario
     * @param int $userId
     * @return bool
     */
    public function updateLastAccess($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET ultimo_acceso = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error al actualizar último acceso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar actividad del usuario
     * @param int $userId
     * @param string $accion
     * @param string $modulo
     * @param string $detalle
     * @param string $ipAddress
     * @return bool
     */
    public function registrarActividad($userId, $accion, $modulo, $detalle = null, $ipAddress = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO actividad_admin 
                (usuario_id, accion, modulo, detalle, ip_address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $userId,
                $accion,
                $modulo,
                $detalle,
                $ipAddress
            ]);
        } catch (PDOException $e) {
            error_log("Error al registrar actividad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar datos de login
     * @param string $username
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateLoginData($username, $password) {
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'El usuario es requerido';
        }
        
        if (empty($password)) {
            $errors[] = 'La contraseña es requerida';
        }
        
        if (strlen($username) < 3) {
            $errors[] = 'El usuario debe tener al menos 3 caracteres';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Verificar si el usuario está bloqueado por intentos fallidos
     * (Opcional - para seguridad adicional)
     * @param string $username
     * @return bool
     */
    public function isUserBlocked($username) {
        // Implementar lógica de bloqueo si es necesario
        // Por ejemplo, verificar intentos fallidos en los últimos 15 minutos
        return false;
    }
    
    /**
     * Incrementar contador de intentos fallidos
     * (Opcional - para seguridad adicional)
     * @param string $username
     * @return bool
     */
    public function registerFailedAttempt($username) {
        // Implementar lógica para registrar intentos fallidos
        // Puede ser en una tabla separada: login_attempts
        return true;
    }
    
    /**
     * Limpiar datos del usuario antes de enviar al cliente
     * @param array $user
     * @return array
     */
    public function sanitizeUserData($user) {
        if (!$user) return null;
        
        // Remover datos sensibles
        unset($user['password']);
        
        return $user;
    }
}