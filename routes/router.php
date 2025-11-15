<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/conexion.php';

// Obtener recurso y acción
$recurso = $_GET['recurso'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'index';

// Mapear recurso a controller
$controllerMap = [
    'productos' => 'ProductoController',
    'servicios' => 'ServicioController',
    'reservas' => 'ReservaController',
    'citas' => 'CitaController',
    'carrito' => 'CarritoController',
    'sliders' => 'SliderController',
    'anuncios' => 'AnuncioController',
    'auth' => 'AuthController',
    'categorias' => 'CategoriaController',
    'estadisticas' => 'DashboardController'
];

if (!isset($controllerMap[$recurso])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Recurso no encontrado']);
    exit;
}

// Cargar controller
$controllerName = $controllerMap[$recurso];
$controllerFile = __DIR__ . "/../controller/{$controllerName}.php";

if (!file_exists($controllerFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Controller no existe']);
    exit;
}

require_once $controllerFile;

// Instanciar y ejecutar
$controller = new $controllerName();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
    exit;
}

$controller->$action();