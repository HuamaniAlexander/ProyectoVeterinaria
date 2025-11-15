<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/CarritoModel.php';
require_once __DIR__ . '/../model/ProductoModel.php';
require_once __DIR__ . '/../model/BaseModel.php'; // ✅ AGREGAR ESTA LÍNEA

class CarritoController {
    private $model;
    private $productoModel;
    
    public function __construct() {
        $this->model = new CarritoModel();
        $this->productoModel = new ProductoModel();
    }
    
    private function getSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = session_id();
        }
        
        return $_SESSION['cart_session_id'];
    }
    
    public function add() {
        try { // ✅ FALTABA ESTE TRY
            $data = json_decode(file_get_contents('php://input'), true);
            
            $productoId = (int)($data['producto_id'] ?? 0);
            $cantidad = (int)($data['cantidad'] ?? 1);
            
            if ($productoId <= 0 || $cantidad <= 0) {
                jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
            }
            
            // Verificar producto y stock
            $producto = $this->productoModel->getById($productoId);
            
            if (!$producto || $producto['activo'] != 1) {
                jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
            if ($producto['stock'] < $cantidad) {
                jsonResponse(['success' => false, 'message' => 'Stock insuficiente'], 400);
            }
            
            // Agregar al carrito
            $sessionId = $this->getSessionId();
            $result = $this->model->addItem($sessionId, $productoId, $cantidad);
            
            if ($result) {
                $totales = $this->model->getTotals($sessionId);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Producto agregado al carrito',
                    'cart' => $totales
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al agregar al carrito'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        try {
            $data = $this->getRequestData();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            $cantidad = (int)($data['cantidad'] ?? 0);
            
            if ($productoId <= 0 || $cantidad < 0) {
                jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
            }
            
            // Verificar stock si cantidad > 0
            if ($cantidad > 0) {
                $producto = $this->productoModel->getById($productoId);
                
                if ($producto['stock'] < $cantidad) {
                    jsonResponse(['success' => false, 'message' => 'Stock insuficiente'], 400);
                }
            }
            
            $sessionId = $this->getSessionId();
            $result = $this->model->updateQuantity($sessionId, $productoId, $cantidad);
            
            if ($result) {
                $totales = $this->model->getTotals($sessionId);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Carrito actualizado',
                    'cart' => $totales
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al actualizar carrito'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function remove() {
        try {
            $data = $this->getRequestData();
            $productoId = (int)($data['producto_id'] ?? 0);
            
            if ($productoId <= 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
            }
            
            $sessionId = $this->getSessionId();
            $result = $this->model->removeItem($sessionId, $productoId);
            
            if ($result) {
                $totales = $this->model->getTotals($sessionId);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Producto eliminado',
                    'cart' => $totales
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al eliminar producto'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $sessionId = $this->getSessionId();
            
            $items = $this->model->getBySessionId($sessionId);
            $totales = $this->model->getTotals($sessionId);
            
            jsonResponse([
                'success' => true,
                'items' => $items,
                'totales' => $totales
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function clear() {
        try {
            $sessionId = $this->getSessionId();
            $result = $this->model->clear($sessionId);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Carrito vaciado',
                    'cart' => ['count' => 0, 'subtotal' => 0, 'total' => 0]
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Error al vaciar carrito'], 500);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function checkout() {
        try {
            $data = $this->getRequestData();
            
            // Validar datos del cliente
            $required = ['nombre', 'email', 'telefono', 'direccion', 'metodo_pago'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    jsonResponse(['success' => false, 'message' => "Campo {$field} requerido"], 400);
                }
            }
            
            $datosCliente = [
                'nombre' => sanitize($data['nombre']),
                'email' => sanitize($data['email']),
                'telefono' => sanitize($data['telefono']),
                'direccion' => sanitize($data['direccion']),
                'metodo_pago' => sanitize($data['metodo_pago']),
                'notas' => sanitize($data['notas'] ?? '')
            ];
            
            $sessionId = $this->getSessionId();
            $result = $this->model->processCheckout($sessionId, $datosCliente);
            
            jsonResponse([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'codigo_pedido' => $result['codigo_pedido']
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}