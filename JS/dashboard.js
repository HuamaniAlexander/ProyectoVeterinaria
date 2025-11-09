// ================================================
// DASHBOARD PETZONE - JAVASCRIPT COMPLETO
// ================================================

const API_URL = '../../api/';
let currentUser = null;

// ================================================
// AUTENTICACIÓN
// ================================================
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    try {
        const response = await fetch(`${API_URL}auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            document.getElementById('userName').textContent = data.user.nombre_completo;
            document.getElementById('loginScreen').style.display = 'none';
            document.getElementById('dashboard').classList.add('active');
            loadDashboardData();
        } else {
            showError('loginError', data.message);
        }
    } catch (error) {
        showError('loginError', 'Error al conectar con el servidor');
    }
});

function logout() {
    if (confirm('¿Está seguro que desea cerrar sesión?')) {
        fetch(`${API_URL}auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        }).then(() => {
            location.reload();
        });
    }
}

function showError(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.style.display = 'block';
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

// ================================================
// NAVEGACIÓN
// ================================================
function showSection(sectionName) {
    document.querySelectorAll('.panel-section').forEach(section => {
        section.classList.remove('active');
    });
    
    document.getElementById(`section-${sectionName}`).classList.add('active');
    
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Cargar datos específicos de la sección
    switch(sectionName) {
        case 'productos':
            loadProductos();
            loadCategorias();
            break;
        case 'sliders':
            loadSliders();
            break;
        case 'anuncios':
            loadAnuncios();
            break;
        case 'contenido':
            loadContenido();
            break;
        case 'pedidos':
            loadPedidos();
            break;
    }
}

// ================================================
// CARGAR DATOS DEL DASHBOARD
// ================================================
async function loadDashboardData() {
    updateDate();
    loadEstadisticas();
    loadActividad();
}

function updateDate() {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const date = new Date().toLocaleDateString('es-ES', options);
    document.getElementById('currentDate').textContent = date;
}

async function loadEstadisticas() {
    try {
        const response = await fetch(`${API_URL}estadisticas.php`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalProductos').textContent = data.stats.total_productos || 0;
            document.getElementById('totalSliders').textContent = data.stats.total_sliders_activos || 0;
            document.getElementById('totalAnuncios').textContent = data.stats.total_anuncios_activos || 0;
            document.getElementById('pedidosHoy').textContent = data.stats.pedidos_hoy || 0;
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

async function loadActividad() {
    try {
        const response = await fetch(`${API_URL}actividad.php`);
        const data = await response.json();
        
        const activityList = document.getElementById('activityList');
        
        if (data.success && data.actividades.length > 0) {
            activityList.innerHTML = data.actividades.map(act => `
                <div class="activity-item">
                    <div class="activity-time">${act.fecha}</div>
                    <div><strong>${act.accion}</strong> en ${act.modulo}</div>
                    <div class="activity-detail">${act.detalle || ''}</div>
                </div>
            `).join('');
        } else {
            activityList.innerHTML = '<div class="loading">No hay actividad reciente</div>';
        }
    } catch (error) {
        console.error('Error al cargar actividad:', error);
    }
}

// ================================================
// GESTIÓN DE PRODUCTOS
// ================================================
let filtroCategoria = '';
let busquedaProducto = '';

async function loadProductos() {
    try {
        const response = await fetch(
            `${API_URL}productos.php?action=list&categoria=${filtroCategoria}&busqueda=${busquedaProducto}`
        );
        const data = await response.json();
        
        const tbody = document.getElementById('productosTableBody');
        
        if (data.success && data.productos.length > 0) {
            tbody.innerHTML = data.productos.map(p => `
                <tr>
                    <td><img src="${p.imagen || '../../IMG/no-image.png'}" alt="${p.nombre}"></td>
                    <td>${p.id}</td>
                    <td>${p.nombre}</td>
                    <td>${p.categoria_nombre}</td>
                    <td>S/. ${parseFloat(p.precio).toFixed(2)}</td>
                    <td>${p.stock}</td>
                    <td><span class="status-badge ${p.activo == 1 ? 'active' : 'inactive'}">
                        ${p.activo == 1 ? 'Activo' : 'Inactivo'}
                    </span></td>
                    <td class="action-btns">
                        <button class="btn-edit" onclick="editProducto(${p.id})">
                            <span class="material-icons">edit</span> Editar
                        </button>
                        <button class="btn-delete" onclick="deleteProducto(${p.id})">
                            <span class="material-icons">delete</span> Eliminar
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="loading">No hay productos</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
}

async function loadCategorias() {
    try {
        const response = await fetch(`${API_URL}categorias.php`);
        const data = await response.json();
        
        if (data.success) {
            const selects = ['productCategoria', 'filterCategoria'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    const currentValue = select.value;
                    select.innerHTML = selectId === 'filterCategoria' 
                        ? '<option value="">Todas las categorías</option>'
                        : '<option value="">Seleccionar...</option>';
                    
                    data.categorias.forEach(cat => {
                        select.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                    });
                    
                    select.value = currentValue;
                }
            });
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

// Event Listeners para filtros
document.getElementById('searchProductos')?.addEventListener('input', (e) => {
    busquedaProducto = e.target.value;
    loadProductos();
});

document.getElementById('filterCategoria')?.addEventListener('change', (e) => {
    filtroCategoria = e.target.value;
    loadProductos();
});

function showProductForm(productId = null) {
    document.getElementById('productFormModal').classList.add('active');
    document.getElementById('productFormTitle').textContent = productId ? 'Editar Producto' : 'Nuevo Producto';
    
    if (productId) {
        loadProductoData(productId);
    } else {
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
    }
}

function closeProductForm() {
    document.getElementById('productFormModal').classList.remove('active');
    document.getElementById('productForm').reset();
}

async function loadProductoData(id) {
    try {
        const response = await fetch(`${API_URL}productos.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const p = data.producto;
            document.getElementById('productId').value = p.id;
            document.getElementById('productNombre').value = p.nombre;
            document.getElementById('productCategoria').value = p.categoria_id;
            document.getElementById('productPrecio').value = p.precio;
            document.getElementById('productStock').value = p.stock;
            document.getElementById('productSku').value = p.codigo_sku || '';
            document.getElementById('productDescripcion').value = p.descripcion || '';
            document.getElementById('productDestacado').checked = p.destacado == 1;
            
            if (p.imagen) {
                document.getElementById('imagePreview').innerHTML = `<img src="${p.imagen}" alt="Preview">`;
                document.getElementById('imagePreview').classList.add('active');
            }
        }
    } catch (error) {
        console.error('Error al cargar producto:', error);
    }
}

document.getElementById('productForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', document.getElementById('productId').value ? 'update' : 'create');
    
    try {
        const response = await fetch(`${API_URL}productos.php`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Producto guardado exitosamente', 'success');
            closeProductForm();
            loadProductos();
            loadEstadisticas();
        } else {
            showToast(data.message || 'Error al guardar producto', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
});

async function deleteProducto(id) {
    if (!confirm('¿Está seguro de eliminar este producto?')) return;
    
    try {
        const response = await fetch(`${API_URL}productos.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Producto eliminado exitosamente', 'success');
            loadProductos();
            loadEstadisticas();
        } else {
            showToast(data.message || 'Error al eliminar producto', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
}

function editProducto(id) {
    showProductForm(id);
}

// Preview de imagen
document.getElementById('productImagen')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById('imagePreview').innerHTML = `<img src="${event.target.result}" alt="Preview">`;
            document.getElementById('imagePreview').classList.add('active');
        };
        reader.readAsDataURL(file);
    }
});

// ================================================
// GESTIÓN DE SLIDERS
// ================================================
async function loadSliders() {
    try {
        const response = await fetch(`${API_URL}sliders.php?action=list`);
        const data = await response.json();
        
        const grid = document.getElementById('slidersGrid');
        
        if (data.success && data.sliders.length > 0) {
            grid.innerHTML = data.sliders.map(s => `
                <div class="slider-card">
                    <div class="slider-image">
                        <img src="${s.imagen}" alt="${s.titulo}">
                    </div>
                    <div class="slider-info">
                        <div class="slider-title">${s.titulo}</div>
                        <div class="slider-description">${s.descripcion || ''}</div>
                        <span class="status-badge ${s.activo == 1 ? 'active' : 'inactive'}">
                            ${s.activo == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                        <div class="action-btns" style="margin-top: 1rem;">
                            <button class="btn-edit" onclick="editSlider(${s.id})">
                                <span class="material-icons">edit</span> Editar
                            </button>
                            <button class="btn-delete" onclick="deleteSlider(${s.id})">
                                <span class="material-icons">delete</span> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            grid.innerHTML = '<div class="loading">No hay sliders</div>';
        }
    } catch (error) {
        console.error('Error al cargar sliders:', error);
    }
}

function showSliderForm(sliderId = null) {
    document.getElementById('sliderFormModal').classList.add('active');
    document.getElementById('sliderFormTitle').textContent = sliderId ? 'Editar Slider' : 'Nuevo Slider';
    
    if (sliderId) {
        loadSliderData(sliderId);
    } else {
        document.getElementById('sliderForm').reset();
        document.getElementById('sliderId').value = '';
    }
}

function closeSliderForm() {
    document.getElementById('sliderFormModal').classList.remove('active');
}

async function loadSliderData(id) {
    try {
        const response = await fetch(`${API_URL}sliders.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const s = data.slider;
            document.getElementById('sliderId').value = s.id;
            document.getElementById('sliderTitulo').value = s.titulo;
            document.getElementById('sliderPosicion').value = s.posicion;
            document.getElementById('sliderEnlace').value = s.enlace || '';
            document.getElementById('sliderOrden').value = s.orden;
            document.getElementById('sliderDescripcion').value = s.descripcion || '';
            document.getElementById('sliderActivo').checked = s.activo == 1;
            
            if (s.imagen) {
                document.getElementById('sliderImagePreview').innerHTML = `<img src="${s.imagen}" alt="Preview">`;
                document.getElementById('sliderImagePreview').classList.add('active');
            }
        }
    } catch (error) {
        console.error('Error al cargar slider:', error);
    }
}

document.getElementById('sliderForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', document.getElementById('sliderId').value ? 'update' : 'create');
    
    try {
        const response = await fetch(`${API_URL}sliders.php`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Slider guardado exitosamente', 'success');
            closeSliderForm();
            loadSliders();
            loadEstadisticas();
        } else {
            showToast(data.message || 'Error al guardar slider', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
});

async function deleteSlider(id) {
    if (!confirm('¿Está seguro de eliminar este slider?')) return;
    
    try {
        const response = await fetch(`${API_URL}sliders.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Slider eliminado exitosamente', 'success');
            loadSliders();
            loadEstadisticas();
        } else {
            showToast(data.message || 'Error al eliminar slider', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
}

function editSlider(id) {
    showSliderForm(id);
}

// ================================================
// GESTIÓN DE ANUNCIOS
// ================================================
async function loadAnuncios() {
    try {
        const response = await fetch(`${API_URL}anuncios.php?action=list`);
        const data = await response.json();
        
        const tbody = document.getElementById('anunciosTableBody');
        
        if (data.success && data.anuncios.length > 0) {
            tbody.innerHTML = data.anuncios.map(a => `
                <tr>
                    <td>${a.id}</td>
                    <td>${a.mensaje.substring(0, 60)}...</td>
                    <td>${a.tipo}</td>
                    <td>${a.prioridad}</td>
                    <td><span class="status-badge ${a.activo == 1 ? 'active' : 'inactive'}">
                        ${a.activo == 1 ? 'Activo' : 'Inactivo'}
                    </span></td>
                    <td class="action-btns">
                        <button class="btn-edit" onclick="editAnuncio(${a.id})">
                            <span class="material-icons">edit</span>
                        </button>
                        <button class="btn-delete" onclick="deleteAnuncio(${a.id})">
                            <span class="material-icons">delete</span>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="loading">No hay anuncios</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar anuncios:', error);
    }
}

function showAnuncioForm(anuncioId = null) {
    document.getElementById('anuncioFormModal').classList.add('active');
    document.getElementById('anuncioFormTitle').textContent = anuncioId ? 'Editar Anuncio' : 'Nuevo Anuncio';
    
    if (anuncioId) {
        loadAnuncioData(anuncioId);
    } else {
        document.getElementById('anuncioForm').reset();
        document.getElementById('anuncioId').value = '';
    }
}

function closeAnuncioForm() {
    document.getElementById('anuncioFormModal').classList.remove('active');
}

async function loadAnuncioData(id) {
    try {
        const response = await fetch(`${API_URL}anuncios.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const a = data.anuncio;
            document.getElementById('anuncioId').value = a.id;
            document.getElementById('anuncioTipo').value = a.tipo;
            document.getElementById('anuncioColorFondo').value = a.color_fondo;
            document.getElementById('anuncioColorTexto').value = a.color_texto;
            document.getElementById('anuncioVelocidad').value = a.velocidad;
            document.getElementById('anuncioIcono').value = a.icono || '';
            document.getElementById('anuncioPrioridad').value = a.prioridad;
            document.getElementById('anuncioMensaje').value = a.mensaje;
            document.getElementById('anuncioActivo').checked = a.activo == 1;
        }
    } catch (error) {
        console.error('Error al cargar anuncio:', error);
    }
}

document.getElementById('anuncioForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.action = document.getElementById('anuncioId').value ? 'update' : 'create';
    data.activo = document.getElementById('anuncioActivo').checked ? 1 : 0;
    
    try {
        const response = await fetch(`${API_URL}anuncios.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Anuncio guardado exitosamente', 'success');
            closeAnuncioForm();
            loadAnuncios();
            loadEstadisticas();
        } else {
            showToast(result.message || 'Error al guardar anuncio', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
});

async function deleteAnuncio(id) {
    if (!confirm('¿Está seguro de eliminar este anuncio?')) return;
    
    try {
        const response = await fetch(`${API_URL}anuncios.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Anuncio eliminado exitosamente', 'success');
            loadAnuncios();
            loadEstadisticas();
        } else {
            showToast(data.message || 'Error al eliminar anuncio', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
}

function editAnuncio(id) {
    showAnuncioForm(id);
}

// ================================================
// GESTIÓN DE CONTENIDO
// ================================================
async function loadContenido() {
    try {
        const response = await fetch(`${API_URL}contenido.php?seccion=contacto`);
        const data = await response.json();
        
        if (data.success) {
            data.contenidos.forEach(c => {
                const input = document.getElementById(`cont${c.clave.charAt(0).toUpperCase() + c.clave.slice(1)}`);
                if (input) input.value = c.valor;
            });
        }
    } catch (error) {
        console.error('Error al cargar contenido:', error);
    }
}

document.getElementById('contactoForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        action: 'update',
        seccion: 'contacto',
        contenidos: Object.fromEntries(formData)
    };
    
    try {
        const response = await fetch(`${API_URL}contenido.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Contenido actualizado exitosamente', 'success');
        } else {
            showToast(result.message || 'Error al actualizar contenido', 'error');
        }
    } catch (error) {
        showToast('Error al conectar con el servidor', 'error');
    }
});

// ================================================
// GESTIÓN DE PEDIDOS
// ================================================
async function loadPedidos() {
    try {
        const response = await fetch(`${API_URL}pedidos.php?action=list`);
        const data = await response.json();
        
        const tbody = document.getElementById('pedidosTableBody');
        
        if (data.success && data.pedidos.length > 0) {
            tbody.innerHTML = data.pedidos.map(p => `
                <tr>
                    <td>${p.codigo_pedido}</td>
                    <td>${p.nombre_cliente}</td>
                    <td>${new Date(p.fecha_pedido).toLocaleDateString('es-ES')}</td>
                    <td>S/. ${parseFloat(p.total).toFixed(2)}</td>
                    <td><span class="status-badge ${p.estado}">${p.estado}</span></td>
                    <td class="action-btns">
                        <button class="btn-view" onclick="viewPedido(${p.id})">
                            <span class="material-icons">visibility</span>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="loading">No hay pedidos</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar pedidos:', error);
    }
}

// ================================================
// TOAST NOTIFICATIONS
// ================================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} active`;
    
    setTimeout(() => {
        toast.classList.remove('active');
    }, 3000);
}

// ================================================
// INICIALIZACIÓN
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    // Verificar sesión existente
    fetch(`${API_URL}auth.php?action=check`).then(res => res.json()).then(data => {
        if (data.authenticated) {
            currentUser = data.user;
            document.getElementById('userName').textContent = data.user.nombre_completo;
            document.getElementById('loginScreen').style.display = 'none';
            document.getElementById('dashboard').classList.add('active');
            loadDashboardData();
        }
    });
});