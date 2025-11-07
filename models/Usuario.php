<?php
require_once __DIR__ . '/../core/Model.php';

class Usuario extends Model {
    protected $table = 'usuarios';
    
    // Autenticar usuario
    public function authenticate($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }
        
        return false;
    }
    
    // Crear usuario con password hasheado
    public function createUser($username, $password, $email, $rol = 'editor') {
        $data = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'rol' => $rol
        ];
        
        return $this->create($data);
    }
    
    // Cambiar contraseña
    public function changePassword($userId, $newPassword) {
        $data = [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];
        
        return $this->update($userId, $data);
    }
}