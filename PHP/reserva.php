<?php
// Configuración
$destinatario = "test@gmail.com"; // Cambia por tu correo real
$asunto = "Nueva Reserva de Cita - PetZone";

// Verificar que el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitizar y validar datos
    $nombre = filter_var(trim($_POST['nombre']), FILTER_SANITIZE_STRING);
    $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $telefono = filter_var(trim($_POST['telefono']), FILTER_SANITIZE_STRING);
    $servicio = filter_var(trim($_POST['servicio']), FILTER_SANITIZE_STRING);
    $mensaje = filter_var(trim($_POST['mensaje']), FILTER_SANITIZE_STRING);
    
    // Array de errores
    $errores = [];
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    if (empty($telefono)) {
        $errores[] = "El teléfono es obligatorio";
    }
    
    if (empty($servicio)) {
        $errores[] = "Debe seleccionar un servicio";
    }
    
    // Si no hay errores, procesar el formulario
    if (empty($errores)) {
        
        // Mapeo de servicios para mostrar nombres legibles
        $servicios_map = [
            'consulta-veterinaria' => 'Consulta Veterinaria',
            'peluqueria' => 'Peluquería y Estética',
            'vacunacion' => 'Vacunación',
            'desparasitacion' => 'Desparasitación',
            'bano' => 'Baño y Aseo',
            'guarderia' => 'Guardería'
        ];
        
        $servicio_nombre = $servicios_map[$servicio] ?? $servicio;
        
        // Construir el cuerpo del correo
        $cuerpo = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nueva Reserva de Cita - PetZone</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>Nombre:</div>
                        <div class='value'>{$nombre}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Correo Electrónico:</div>
                        <div class='value'>{$correo}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Teléfono:</div>
                        <div class='value'>{$telefono}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Servicio Solicitado:</div>
                        <div class='value'>{$servicio_nombre}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Mensaje:</div>
                        <div class='value'>" . nl2br($mensaje) . "</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Cabeceras para correo HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PetZone <noreply@petzone.com>" . "\r\n";
        $headers .= "Reply-To: {$correo}" . "\r\n";
        
        // Intentar enviar el correo
        if (mail($destinatario, $asunto, $cuerpo, $headers)) {
            // Guardar en base de datos (opcional)
            guardarEnBaseDatos($nombre, $correo, $telefono, $servicio, $mensaje);
            
            // Redirigir con mensaje de éxito
            header("Location: reserva.html?status=success");
            exit();
        } else {
            // Error al enviar correo
            header("Location: reserva.html?status=error");
            exit();
        }
        
    } else {
        // Hay errores de validación
        $errores_query = urlencode(implode(", ", $errores));
        header("Location: reserva.html?status=validation&errors={$errores_query}");
        exit();
    }
    
} else {
    // Acceso directo al archivo sin POST
    header("Location: reserva.html");
    exit();
}

// Función opcional para guardar en base de datos
function guardarEnBaseDatos($nombre, $correo, $telefono, $servicio, $mensaje) {
    // Configuración de la base de datos
    $servidor = "localhost";
    $usuario = "tu_usuario";
    $password = "tu_password";
    $basedatos = "petzone_db";
    
    try {
        // Crear conexión
        $conn = new mysqli($servidor, $usuario, $password, $basedatos);
        
        // Verificar conexión
        if ($conn->connect_error) {
            error_log("Error de conexión: " . $conn->connect_error);
            return false;
        }
        
        // Preparar la consulta
        $stmt = $conn->prepare("INSERT INTO reservas (nombre, correo, telefono, servicio, mensaje, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $nombre, $correo, $telefono, $servicio, $mensaje);
        
        // Ejecutar
        $resultado = $stmt->execute();
        
        // Cerrar
        $stmt->close();
        $conn->close();
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error en base de datos: " . $e->getMessage());
        return false;
    }
}
?>