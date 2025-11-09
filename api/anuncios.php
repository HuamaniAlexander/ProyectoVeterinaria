<?php
/**
 * API de Anuncios - PetZone
 */

require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !in_array($_GET['action'] ?? '', ['activos'])) {
    jsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch($action) {
        case 'list':
            listAnuncios();
            break;
        case 'get':
            getAnuncio();
            break;
        case 'create':
            createAnuncio();
            break;
        case 'update':
            updateAnuncio();
            break;
        case 'delete':
            deleteAnuncio();
            break;
        case 'activos':
            getAnunciosActivos();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function listAnuncios() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM anuncios ORDER BY prioridad DESC, id DESC");
    $stmt->execute();
    $anuncios = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'anuncios' => $anuncios]);
}

function getAnuncio() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID requerido'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM anuncios WHERE id = ?");
    $stmt->execute([$id]);
    $anuncio = $stmt->fetch();
    
    if ($anuncio) {
        jsonResponse(['success' => true, 'anuncio' => $anuncio]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Anuncio no encontrado'], 404);
    }
}

function createAnuncio() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $mensaje = sanitize($data['mensaje'] ?? '');
    $tipo = sanitize($data['tipo'] ?? 'aviso_general');
    $color_fondo = sanitize($data['color_fondo'] ?? '#23906F');
    $color_texto = sanitize($data['color_texto'] ?? '#FFFFFF');
    $icono = sanitize($data['icono'] ?? '');
    $velocidad = (int)($data['velocidad'] ?? 30);
    $prioridad = (int)($data['prioridad'] ?? 0);
    $activo = (int)($data['activo'] ?? 1);
    
    if (empty($mensaje)) {
        jsonResponse(['success' => false, 'message' => 'Mensaje requerido'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO anuncios 
        (mensaje, tipo, color_fondo, color_texto, icono, velocidad, prioridad, activo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $mensaje, $tipo, $color_fondo, $color_texto, 
        $icono, $velocidad, $prioridad, $activo
    ]);
    
    if ($result) {
        registrarActividad($db, $_SESSION['user_id'], 'Anuncio creado', 'Anuncios', substr($mensaje, 0, 50));
        jsonResponse(['success' => true, 'message' => 'Anuncio creado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al crear anuncio'], 500);
    }
}

function updateAnuncio() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = (int)($data['id'] ?? 0);
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $mensaje = sanitize($data['mensaje'] ?? '');
    $tipo = sanitize($data['tipo'] ?? 'aviso_general');
    $color_fondo = sanitize($data['color_fondo'] ?? '#23906F');
    $color_texto = sanitize($data['color_texto'] ?? '#FFFFFF');
    $icono = sanitize($data['icono'] ?? '');
    $velocidad = (int)($data['velocidad'] ?? 30);
    $prioridad = (int)($data['prioridad'] ?? 0);
    $activo = (int)($data['activo'] ?? 1);
    
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE anuncios 
        SET mensaje = ?, tipo = ?, color_fondo = ?, color_texto = ?, 
            icono = ?, velocidad = ?, prioridad = ?, activo = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $mensaje, $tipo, $color_fondo, $color_texto, 
        $icono, $velocidad, $prioridad, $activo, $id
    ]);
    
    if ($result) {
        registrarActividad($db, $_SESSION['user_id'], 'Anuncio actualizado', 'Anuncios', "ID: {$id}");
        jsonResponse(['success' => true, 'message' => 'Anuncio actualizado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al actualizar anuncio'], 500);
    }
}

function deleteAnuncio() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    if ($id == 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM anuncios WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        registrarActividad($db, $_SESSION['user_id'], 'Anuncio eliminado', 'Anuncios', "ID: {$id}");
        jsonResponse(['success' => true, 'message' => 'Anuncio eliminado exitosamente']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error al eliminar anuncio'], 500);
    }
}

function getAnunciosActivos() {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM anuncios 
        WHERE activo = 1 
        AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
        AND (fecha_fin IS NULL OR fecha_fin >= NOW())
        ORDER BY prioridad DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $anuncios = $stmt->fetchAll();
    
    // Incrementar visualizaciones
    foreach ($anuncios as $anuncio) {
        $updateStmt = $db->prepare("UPDATE anuncios SET visualizaciones = visualizaciones + 1 WHERE id = ?");
        $updateStmt->execute([$anuncio['id']]);
    }
    
    jsonResponse(['success' => true, 'anuncios' => $anuncios]);
}

function registrarActividad($db, $userId, $accion, $modulo, $detalle = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO actividad_admin (usuario_id, accion, modulo, detalle, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $accion, $modulo, $detalle, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}
?>