<?php
/**
 * Controlador de Estadísticas - PetZone
 * Maneja las solicitudes HTTP y usa EstadisticasModelo
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/estadisticasModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Verificar autenticación (solo admin puede ver estadísticas)
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

$action = $_GET['action'] ?? 'dashboard';
$estadisticasModelo = new EstadisticasModelo();

try {
    switch($action) {
        case 'dashboard':
            handleDashboard($estadisticasModelo);
            break;
            
        case 'productos':
            handleProductos($estadisticasModelo);
            break;
            
        case 'ventas':
            handleVentas($estadisticasModelo);
            break;
            
        case 'ventas_mes':
            handleVentasPorMes($estadisticasModelo);
            break;
            
        case 'productos_top':
            handleProductosTop($estadisticasModelo);
            break;
            
        case 'categorias':
            handleCategorias($estadisticasModelo);
            break;
            
        case 'citas':
            handleCitas($estadisticasModelo);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("ESTADISTICAS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

/**
 * Obtener estadísticas completas del dashboard
 */
function handleDashboard($modelo) {
    $stats = $modelo->obtenerDashboard();
    
    if ($stats !== false) {
        jsonResponse(['success' => true, 'stats' => $stats]);
    } else {
        // Si la vista no existe, construir estadísticas manualmente
        $stats = [
            'total_productos' => $modelo->contarProductos(),
            'productos_destacados' => $modelo->contarProductosDestacados(),
            'stock_bajo' => $modelo->contarStockBajo(),
            'total_sliders_activos' => $modelo->contarSlidersActivos(),
            'total_anuncios_activos' => $modelo->contarAnunciosActivos(),
            'pedidos_pendientes' => $modelo->contarPedidosPorEstado('pendiente'),
            'pedidos_hoy' => $modelo->contarPedidosHoy(),
            'ingresos_totales' => $modelo->calcularIngresosTotales(),
            'ingresos_mes_actual' => $modelo->calcularIngresosMesActual()
        ];
        
        jsonResponse(['success' => true, 'stats' => $stats]);
    }
}

/**
 * Estadísticas específicas de productos
 */
function handleProductos($modelo) {
    $stats = [
        'total' => $modelo->contarProductos(),
        'destacados' => $modelo->contarProductosDestacados(),
        'stock_bajo' => $modelo->contarStockBajo()
    ];
    
    jsonResponse(['success' => true, 'stats' => $stats]);
}

/**
 * Estadísticas de ventas generales
 */
function handleVentas($modelo) {
    $stats = [
        'ingresos_totales' => $modelo->calcularIngresosTotales(),
        'ingresos_mes_actual' => $modelo->calcularIngresosMesActual(),
        'pedidos_pendientes' => $modelo->contarPedidosPorEstado('pendiente'),
        'pedidos_procesando' => $modelo->contarPedidosPorEstado('procesando'),
        'pedidos_enviado' => $modelo->contarPedidosPorEstado('enviado'),
        'pedidos_entregado' => $modelo->contarPedidosPorEstado('entregado'),
        'pedidos_hoy' => $modelo->contarPedidosHoy()
    ];
    
    jsonResponse(['success' => true, 'stats' => $stats]);
}

/**
 * Ventas por mes (últimos 12 meses)
 */
function handleVentasPorMes($modelo) {
    $meses = (int)($_GET['meses'] ?? 12);
    
    $ventas = $modelo->obtenerVentasPorMes($meses);
    
    if ($ventas !== false) {
        jsonResponse(['success' => true, 'ventas' => $ventas]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener ventas por mes'], 500);
    }
}

/**
 * Productos más vendidos
 */
function handleProductosTop($modelo) {
    $limite = (int)($_GET['limite'] ?? 10);
    
    $productos = $modelo->obtenerProductosMasVendidos($limite);
    
    if ($productos !== false) {
        jsonResponse(['success' => true, 'productos' => $productos]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener productos más vendidos'], 500);
    }
}

/**
 * Estadísticas de categorías más vendidas
 */
function handleCategorias($modelo) {
    $categorias = $modelo->obtenerCategoriasMasVendidas();
    
    if ($categorias !== false) {
        jsonResponse(['success' => true, 'categorias' => $categorias]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener categorías'], 500);
    }
}

/**
 * Estadísticas de citas
 */
function handleCitas($modelo) {
    $stats = $modelo->obtenerEstadisticasCitas();
    
    if ($stats !== false) {
        jsonResponse(['success' => true, 'stats' => $stats]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al obtener estadísticas de citas'], 500);
    }
}