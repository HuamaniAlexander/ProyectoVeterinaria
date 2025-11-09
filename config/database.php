<?php
/**
 * Configuración de Base de Datos - PetZone
 * Conexión a MySQL usando PDO
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'petzone_db';
    private $username = 'root';  // Cambiar según tu configuración
    private $password = '';      // Cambiar según tu configuración
    private $conn;
    
    /**
     * Obtener conexión a la base de datos
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos'
            ]));
        }
        
        return $this->conn;
    }
    
    /**
     * Cerrar conexión
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

/**
 * Función helper para obtener conexión rápida
 */
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * Función para responder JSON
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Función para sanitizar datos
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Iniciar sesión si no está iniciada
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generar o obtener session_id para carrito
 */
function getCartSessionId() {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id();
    }
    return $_SESSION['cart_session_id'];
}
?>