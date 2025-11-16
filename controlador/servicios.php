<?php
/**
 * Controlador de Servicios - PetZone
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modelo/serviciosModelo.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'list';
$serviciosModelo = new ServiciosModelo();

try {
    switch($action) {
        case 'list':
            handleList($serviciosModelo);
            break;
        case 'get':
            handleGet($serviciosModelo);
            break;
        case 'disponibles':
            handleDisponibles($serviciosModelo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    error_log("SERVICIOS.PHP - Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function handleList($modelo) {
    $servicios = $modelo->listar();
    
    if ($servicios !== false) {
        // Decodificar características JSON
        foreach ($servicios as &$servicio) {
            if ($servicio['caracteristicas']) {
                $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
            }
        }
        
        jsonResponse(['success' => true, 'servicios' => $servicios]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar servicios'], 500);
    }
}

function handleGet($modelo) {
    $id = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;
    
    if (!$id && !$slug) {
        jsonResponse(['success' => false, 'message' => 'ID o slug requerido'], 400);
    }
    
    if ($id) {
        $servicio = $modelo->obtenerPorId($id);
    } else {
        $servicio = $modelo->obtenerPorSlug($slug);
    }
    
    if ($servicio) {
        if ($servicio['caracteristicas']) {
            $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
        }
        
        jsonResponse(['success' => true, 'servicio' => $servicio]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Servicio no encontrado'], 404);
    }
}

function handleDisponibles($modelo) {
    $servicios = $modelo->obtenerDisponibles();
    
    if ($servicios !== false) {
        foreach ($servicios as &$servicio) {
            if ($servicio['caracteristicas']) {
                $servicio['caracteristicas'] = json_decode($servicio['caracteristicas'], true);
            }
        }
        
        jsonResponse(['success' => true, 'servicios' => $servicios]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al cargar servicios disponibles'], 500);
    }
}