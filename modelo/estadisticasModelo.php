<?php
/**
 * Modelo de Estadísticas - PetZone
 * Obtención de datos estadísticos del dashboard
 */

class EstadisticasModelo {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtener todas las estadísticas del dashboard
     */
    public function obtenerDashboard() {
        try {
            $stmt = $this->db->query("SELECT * FROM estadisticas_dashboard");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Contar total de productos
     */
    public function contarProductos() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar productos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar productos destacados
     */
    public function contarProductosDestacados() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE destacado = 1 AND activo = 1");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar productos destacados: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar productos con stock bajo (menos de 10)
     */
    public function contarStockBajo() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE stock < 10 AND activo = 1");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar stock bajo: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar sliders activos
     */
    public function contarSlidersActivos() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM sliders WHERE activo = 1");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar sliders: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar anuncios activos
     */
    public function contarAnunciosActivos() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM anuncios WHERE activo = 1");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar anuncios: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar pedidos por estado
     */
    public function contarPedidosPorEstado($estado = 'pendiente') {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM pedidos WHERE estado = ?");
            $stmt->execute([$estado]);
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar pedidos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar pedidos de hoy
     */
    public function contarPedidosHoy() {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as total 
                FROM pedidos 
                WHERE DATE(fecha_pedido) = CURDATE()
            ");
            return $stmt->fetch()['total'];
        } catch (PDOException $e) {
            error_log("Error al contar pedidos de hoy: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calcular ingresos totales
     */
    public function calcularIngresosTotales() {
        try {
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total), 0) as ingresos 
                FROM pedidos 
                WHERE estado NOT IN ('cancelado')
            ");
            return $stmt->fetch()['ingresos'];
        } catch (PDOException $e) {
            error_log("Error al calcular ingresos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calcular ingresos del mes actual
     */
    public function calcularIngresosMesActual() {
        try {
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total), 0) as ingresos 
                FROM pedidos 
                WHERE MONTH(fecha_pedido) = MONTH(CURDATE()) 
                AND YEAR(fecha_pedido) = YEAR(CURDATE())
                AND estado NOT IN ('cancelado')
            ");
            return $stmt->fetch()['ingresos'];
        } catch (PDOException $e) {
            error_log("Error al calcular ingresos del mes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener productos más vendidos
     */
    public function obtenerProductosMasVendidos($limite = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    dp.producto_id,
                    dp.nombre_producto,
                    SUM(dp.cantidad) as total_vendido,
                    SUM(dp.subtotal) as ingresos_generados
                FROM detalle_pedidos dp
                INNER JOIN pedidos p ON dp.pedido_id = p.id
                WHERE p.estado NOT IN ('cancelado')
                GROUP BY dp.producto_id, dp.nombre_producto
                ORDER BY total_vendido DESC
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener productos más vendidos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de citas
     */
    public function obtenerEstadisticasCitas() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN DATE(fecha_solicitud) = CURDATE() THEN 1 ELSE 0 END) as hoy
                FROM citas
            ");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas de citas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener ventas por mes (últimos 12 meses)
     */
    public function obtenerVentasPorMes($meses = 12) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(fecha_pedido, '%Y-%m') as mes,
                    COUNT(*) as total_pedidos,
                    SUM(total) as ingresos
                FROM pedidos
                WHERE fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                AND estado NOT IN ('cancelado')
                GROUP BY DATE_FORMAT(fecha_pedido, '%Y-%m')
                ORDER BY mes DESC
            ");
            $stmt->execute([$meses]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener ventas por mes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener categorías más vendidas
     */
    public function obtenerCategoriasMasVendidas() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    c.nombre as categoria,
                    COUNT(DISTINCT dp.id) as productos_vendidos,
                    SUM(dp.cantidad) as unidades_vendidas,
                    SUM(dp.subtotal) as ingresos
                FROM detalle_pedidos dp
                INNER JOIN productos p ON dp.producto_id = p.id
                INNER JOIN categorias c ON p.categoria_id = c.id
                INNER JOIN pedidos ped ON dp.pedido_id = ped.id
                WHERE ped.estado NOT IN ('cancelado')
                GROUP BY c.id, c.nombre
                ORDER BY ingresos DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener categorías más vendidas: " . $e->getMessage());
            return false;
        }
    }
}