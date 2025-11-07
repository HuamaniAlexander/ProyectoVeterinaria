// CRUD de sliders
let slidersData = [];

// Cargar sliders
async function cargarSlidersDashboard() {
    try {
        const response = await API.get('dashboard/sliders.php');
        slidersData = response.data;
        renderizarGaleriaSliders();
    } catch (error) {
        console.error('Error al cargar sliders:', error);
        alert('Error al cargar sliders');
    }
}

// Renderizar galería de sliders
function renderizarGaleriaSliders() {
    const galeria = document.querySelector('.slider-gallery');
    if (!galeria) return;
    
    galeria.innerHTML = '';
    
    slidersData.forEach(slider => {
        const sliderHTML = `
            <div class="slider-item">
                <div class="slider-preview">
                    ${slider.imagen ? `<img src="/ProyectoVeterinaria/uploads/sliders/${slider.imagen}" alt="${slider.titulo}">` : '🖼️ Sin imagen'}
                </div>
                <div class="slider-info">
                    <div class="slider-title">${slider.titulo}</div>
                    <span class="slider-status ${slider.activo ? 'active' : 'inactive'}">
                        ${slider.activo ? 'Activo' : 'Inactivo'}
                    </span>
                    <div class="action-btns">
                        <button class="btn-edit" onclick="editarSlider(${slider.id})">✏️ Editar</button>
                        <button class="btn-delete" onclick="eliminarSlider(${slider.id})">🗑️ Eliminar</button>
                    </div>
                </div>
            </div>
        `;
        galeria.insertAdjacentHTML('beforeend', sliderHTML);
    });
}

// Crear slider
async function crearSlider(formData) {
    try {
        const response = await API.post('dashboard/sliders.php', formData);
        alert(response.message);
        await cargarSlidersDashboard();
        toggleForm('formSliders');
    } catch (error) {
        alert('Error al crear slider: ' + error.message);
    }
}

// Editar slider
function editarSlider(id) {
    const slider = slidersData.find(s => s.id === id);
    if (!slider) return;
    
    // Llenar formulario con datos
    document.getElementById('sliderTitulo').value = slider.titulo;
    document.getElementById('sliderPosicion').value = slider.posicion;
    document.getElementById('sliderEstado').value = slider.activo ? '1' : '0';
    document.getElementById('sliderOrden').value = slider.orden;
    document.getElementById('sliderEnlace').value = slider.enlace || '';
    
    // Mostrar formulario en modo edición
    toggleForm('formSliders');
    
    // Guardar ID para actualización
    document.getElementById('formSliders').dataset.editId = id;
}

// Actualizar slider
async function actualizarSlider(id, formData) {
    try {
        const response = await API.put('dashboard/sliders.php', { id, ...formData });
        alert(response.message);
        await cargarSlidersDashboard();
        toggleForm('formSliders');
    } catch (error) {
        alert('Error al actualizar slider: ' + error.message);
    }
}

// Eliminar slider
async function eliminarSlider(id) {
    if (!confirm('¿Está seguro que desea eliminar este slider?')) return;
    
    try {
        const response = await API.delete('dashboard/sliders.php', { id });
        alert(response.message);
        await cargarSlidersDashboard();
    } catch (error) {
        alert('Error al eliminar slider: ' + error.message);
    }
}

// Event listener para formulario de sliders
document.addEventListener('DOMContentLoaded', function() {
    const formSliders = document.getElementById('formSliders');
    if (formSliders) {
        formSliders.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                titulo: document.getElementById('sliderTitulo').value,
                posicion: document.getElementById('sliderPosicion').value,
                activo: document.getElementById('sliderEstado').value === '1',
                orden: document.getElementById('sliderOrden').value,
                enlace: document.getElementById('sliderEnlace').value,
                imagen: 'temp-imagen.jpg' // Aquí iría la lógica de upload
            };
            
            const editId = this.dataset.editId;
            
            if (editId) {
                await actualizarSlider(editId, formData);
                delete this.dataset.editId;
            } else {
                await crearSlider(formData);
            }
            
            this.reset();
        });
    }
});