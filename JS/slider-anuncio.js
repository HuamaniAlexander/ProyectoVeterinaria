/**
 * Slider Superior y Anuncio Inferior - PetZone
 * Carga dinámica desde la base de datos
 */

const API_URL = 'api/';

// ================================================
// SLIDER SUPERIOR
// ================================================
async function loadTopSlider() {
    try {
        const response = await fetch(`${API_URL}sliders.php?action=activos`);
        const data = await response.json();
        
        if (data.success && data.sliders.length > 0) {
            const slider = data.sliders[0]; // Tomar el primer slider activo
            const sliderDiv = document.getElementById('topSlider');
            
            sliderDiv.innerHTML = `
                <a href="${slider.enlace || '#'}" class="slider-content" style="background-color: var(--primary-color);">
                    ${slider.descripcion ? `<span>${slider.descripcion}</span>` : ''}
                </a>
            `;
            
            sliderDiv.classList.add('active');
        }
    } catch (error) {
        console.error('Error al cargar slider:', error);
    }
}

// ================================================
// ANUNCIO INFERIOR ANIMADO
// ================================================
async function loadBottomAnnouncement() {
    try {
        const response = await fetch(`${API_URL}anuncios.php?action=activos`);
        const data = await response.json();
        
        if (data.success && data.anuncios.length > 0) {
            const anuncioDiv = document.getElementById('bottomAnnouncement');
            
            // Tomar el anuncio de mayor prioridad
            const anuncio = data.anuncios[0];
            
            anuncioDiv.style.backgroundColor = anuncio.color_fondo;
            anuncioDiv.style.color = anuncio.color_texto;
            
            // Crear contenido duplicado para efecto infinito
            const mensaje = `
                <div class="announcement-item">
                    ${anuncio.icono ? `<span class="material-icons">${anuncio.icono}</span>` : ''}
                    <span>${anuncio.mensaje}</span>
                </div>
            `;
            
            anuncioDiv.innerHTML = `
                <div class="announcement-content" style="animation-duration: ${anuncio.velocidad}s;">
                    ${mensaje.repeat(10)}
                </div>
            `;
            
            anuncioDiv.classList.add('active');
        }
    } catch (error) {
        console.error('Error al cargar anuncio:', error);
    }
}

// ================================================
// INICIALIZACIÓN
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    loadTopSlider();
    loadBottomAnnouncement();
    
    // Recargar cada 5 minutos
    setInterval(() => {
        loadTopSlider();
        loadBottomAnnouncement();
    }, 300000);
});