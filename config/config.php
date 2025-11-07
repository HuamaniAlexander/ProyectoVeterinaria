<?php
// Configuración general
define('DB_HOST', 'localhost');
define('DB_NAME', 'petzone_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', 'http://localhost/ProyectoVeterinaria/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configuración de sesión
session_start();

// Zona horaria
date_default_timezone_set('America/Lima');

// Headers CORS para AJAX
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

