<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/response.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    Response::error('No autorizado', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

// Verificar que se haya subido un archivo
if (!isset($_FILES['file'])) {
    Response::error('No se recibió ningún archivo', 422);
}

$file = $_FILES['file'];
$tipo = $_POST['tipo'] ?? 'general'; // productos, sliders, contenido

// Validar tipo de archivo
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    Response::error('Tipo de archivo no permitido', 422);
}

// Validar tamaño (máximo 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    Response::error('El archivo es demasiado grande (máximo 5MB)', 422);
}

// Crear directorio si no existe
$uploadDir = UPLOAD_PATH . $tipo . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generar nombre único
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = uniqid() . '_' . time() . '.' . $extension;
$filePath = $uploadDir . $fileName;

// Mover archivo
if (move_uploaded_file($file['tmp_name'], $filePath)) {
    Response::success([
        'filename' => $fileName,
        'path' => '/ProyectoVeterinaria/uploads/' . $tipo . '/' . $fileName,
        'type' => $file['type'],
        'size' => $file['size']
    ], 'Archivo subido exitosamente');
} else {
    Response::error('Error al subir el archivo');
}