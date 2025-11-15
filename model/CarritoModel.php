<?php
require_once __DIR__ . '/BaseModel.php';

class CarritoModel extends BaseModel {
    protected $table = 'carrito';
    
    public function getBySessionId($sessionId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.nombre, p.imagen, p.descripcion, p.stock
            FROM {$this->table} c
            INNER JOIN productos p ON c.producto_id = p.id
            WHERE c.session_id = ?
            ORDER BY c.fecha_agregado DESC
        ");
        $stmt->execute([$sessionId]);
        $items = $stmt->fetchAll();
        
        // Calcular subtotal por item
        foreach ($items as &$item) {
            $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
        }
        
        return $items;
    }
    
    public function getTotals($sessionId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as count,
                SUM(cantidad) as total_items,
                SUM(cantidad * precio_unitario) as subtotal
            FROM {$this->table}
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        $totales = $stmt->fetch();
        
        return [
            'count' => (int)$totales['count'],
            'total_items' => (int)$totales['total_items'],
            'subtotal' => (float)$totales['subtotal'],
            'total' => (float)$totales['subtotal']
        ];
    }
    
    public function addItem($sessionId, $productoId, $cantidad) {
        // Verificar si ya existe
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE session_id = ? AND producto_id = ?
        ");
        $stmt->execute([$sessionId, $productoId]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Actualizar cantidad
            $nuevaCantidad = $existente['cantidad'] + $cantidad;
            $stmt = $this->db->prepare("
                UPDATE {$this->table} 
                SET cantidad = ? 
                WHERE session_id = ? AND producto_id = ?
            ");
            return $stmt->execute([$nuevaCantidad, $sessionId, $productoId]);
        } else {
            // Insertar nuevo
            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (session_id, producto_id, cantidad, precio_unitario) 
                SELECT ?, ?, ?, precio 
                FROM productos 
                WHERE id = ?
            ");
            return $stmt->execute([$sessionId, $productoId, $cantidad, $productoId]);
        }
    }
    
    public function updateQuantity($sessionId, $productoId, $cantidad) {
        if ($cantidad <= 0) {
            return $this->removeItem($sessionId, $productoId);
        }
        
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET cantidad = ? 
            WHERE session_id = ? AND producto_id = ?
        ");
        return $stmt->execute([$cantidad, $sessionId, $productoId]);
    }
    
    public function removeItem($sessionId, $productoId) {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table} 
            WHERE session_id = ? AND producto_id = ?
        ");
        return $stmt->execute([$sessionId, $productoId]);
    }
    
    public function clear($sessionId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE session_id = ?");
        return $stmt->execute([$sessionId]);
    }
    
    public function processCheckout($sessionId, $datosCliente) {
        try {
            $this->db->beginTransaction();
            
            // Obtener items del carrito
            $items = $this->getBySessionId($sessionId);
            
            if (empty($items)) {
                throw new Exception('Carrito vacío');
            }
            
            // Verificar stock
            foreach ($items as $item) {
                if ($item['stock'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para: " . $item['nombre']);
                }
            }
            
            // Calcular subtotal
            $subtotal = array_sum(array_column($items, 'subtotal'));
            
            // Crear pedido
            $codigoPedido = 'PZ-' . date('Ymd') . '-' . rand(1000, 9999);
            
            $stmt = $this->db->prepare("
                INSERT INTO pedidos 
                (codigo_pedido, nombre_cliente, email_cliente, telefono_cliente, 
                 direccion_envio, subtotal, total, metodo_pago, notas, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $stmt->execute([
                $codigoPedido,
                $datosCliente['nombre'],
                $datosCliente['email'],
                $datosCliente['telefono'],
                $datosCliente['direccion'],
                $subtotal,
                $subtotal,
                $datosCliente['metodo_pago'],
                $datosCliente['notas'] ?? null
            ]);
            
            $pedidoId = $this->db->lastInsertId();
            
            // Crear detalles del pedido y actualizar stock
            $stmtDetalle = $this->db->prepare("
                INSERT INTO detalle_pedidos 
                (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmtStock = $this->db->prepare("
                UPDATE productos 
                SET stock = stock - ?, ventas = ventas + ? 
                WHERE id = ?
            ");
            
            foreach ($items as $item) {
                $stmtDetalle->execute([
                    $pedidoId,
                    $item['producto_id'],
                    $item['nombre'],
                    $item['cantidad'],
                    $item['precio_unitario'],
                    $item['subtotal']
                ]);
                
                $stmtStock->execute([
                    $item['cantidad'],
                    $item['cantidad'],
                    $item['producto_id']
                ]);
            }
            
            // Vaciar carrito
            $this->clear($sessionId);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'codigo_pedido' => $codigoPedido
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}