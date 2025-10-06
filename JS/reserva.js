// Manejar mensajes de respuesta del formulario
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const mensajeAlerta = document.getElementById('mensajeAlerta');
    
    if (status && mensajeAlerta) {
        let mensaje = '';
        let clase = '';
        
        if (status === 'success') {
            mensaje = '¡Reserva enviada con éxito! Nos pondremos en contacto contigo pronto.';
            clase = 'exito';
        } else if (status === 'error') {
            mensaje = 'Hubo un error al enviar tu reserva. Por favor, intenta nuevamente.';
            clase = 'error';
        } else if (status === 'validation') {
            const errors = urlParams.get('errors');
            mensaje = 'Error: ' + decodeURIComponent(errors);
            clase = 'error';
        }
        
        if (mensaje) {
            mensajeAlerta.textContent = mensaje;
            mensajeAlerta.className = 'mensaje-alerta ' + clase;
            mensajeAlerta.style.display = 'block';
            
            // Scroll hacia el mensaje
            mensajeAlerta.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Limpiar la URL después de 5 segundos
            setTimeout(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 5000);
        }
    }
    
    // Mostrar loader al enviar formulario
    const formulario = document.getElementById('formularioReserva');
    if (formulario) {
        formulario.addEventListener('submit', function() {
            const botonTexto = this.querySelector('.boton-texto');
            const botonLoader = this.querySelector('.boton-loader');
            
            if (botonTexto && botonLoader) {
                botonTexto.style.display = 'none';
                botonLoader.style.display = 'inline-block';
            }
        });
    }
});