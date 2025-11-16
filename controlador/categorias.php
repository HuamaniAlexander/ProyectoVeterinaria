<?php
/**
 * Controlador de Categorías - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/categoriasModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$categoriasModelo = new CategoriasModelo();

try {
    $categorias = $categoriasModelo->obtenerActivas();
    
    error_log("CATEGORIAS.PHP - Total categorías: " . count($categorias));
    
    if (empty($categorias)) {
        error_log("CATEGORIAS.PHP - ADVERTENCIA: No hay categorías activas");
    }
    
    jsonResponse([
        'success' => true,
        'categorias' => $categorias,
        'total' => count($categorias)
    ]);
    
} catch (Exception $e) {
    error_log("CATEGORIAS.PHP - Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Error al cargar categorías',
        'error' => $e->getMessage()
    ], 500);
}