// Manejo del formulario de reservas con AJAX
document.addEventListener('DOMContentLoaded', function() {
    const formulario = document.getElementById('formularioReserva');
    
    if (!formulario) return;
    
    formulario.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Deshabilitar botón mientras se procesa
        const btnSubmit = formulario.querySelector('button[type="submit"]');
        const textoOriginal = btnSubmit.textContent;
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Enviando...';
        
        // Recopilar datos del formulario
        const formData = {
            nombre: document.getElementById('nombre').value,
            correo: document.getElementById('correo').value,
            telefono: document.getElementById('telefono').value,
            servicio: document.getElementById('servicio').value,
            mensaje: document.getElementById('mensaje').value
        };
        
        try {
            // Enviar datos por AJAX
            const response = await API.post('reservas.php', formData);
            
            // Mostrar mensaje de éxito
            alert('¡Reserva enviada exitosamente! Te contactaremos pronto.');
            
            // Limpiar formulario
            formulario.reset();
            
        } catch (error) {
            // Mostrar mensaje de error
            alert('Error al enviar la reserva: ' + error.message);
        } finally {
            // Rehabilitar botón
            btnSubmit.disabled = false;
            btnSubmit.textContent = textoOriginal;
        }
    });
});