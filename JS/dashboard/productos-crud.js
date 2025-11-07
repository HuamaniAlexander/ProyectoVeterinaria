// CRUD de productos en el dashboard
let productosData = [];

// Cargar productos
async function cargarProductosDashboard() {
    try {
        const response = await API.get('dashboard/productos.php');
        productosData = response.data;
        renderizarTablaProductos();
    } catch (error) {
        console.error('Error al cargar productos:', error);
    }
}

// Renderizar tabla
function renderizarTablaProductos() {
    const tbody = document.querySelector('#tablaProductos tbody');
    tbody.innerHTML = '';
    
    productosData.forEach(producto => {
        const row = `
            <tr>
                <td>${producto.id}</td>
                <td>${producto.nombre}</td>
                <td>${producto.categoria}</td>
                <td>S/. ${producto.precio}</td>
                <td>${producto.stock}</td>
                <td class="action-btns">
                    <button class="btn-edit" onclick="editarProducto(${producto.id})">✏️ Editar</button>
                    <button class="btn-delete" onclick="eliminarProducto(${producto.id})">🗑️ Eliminar</button>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

// Crear producto
async function crearProducto(formData) {
    try {
        const response = await API.post('dashboard/productos.php', formData);
        alert(response.message);
        cargarProductosDashboard();
        cerrarFormulario();
    } catch (error) {
        alert('Error al crear producto: ' + error.message);
    }
}

// Actualizar producto
async function actualizarProducto(id, formData) {
    try {
        const response = await API.put('dashboard/productos.php', { id, ...formData });
        alert(response.message);
        cargarProductosDashboard();
        cerrarFormulario();
    } catch (error) {
        alert('Error al actualizar producto: ' + error.message);
    }
}

// Eliminar producto
async function eliminarProducto(id) {
    if (!confirm('¿Está seguro que desea eliminar este producto?')) return;
    
    try {
        const response = await API.delete('dashboard/productos.php', { id });
        alert(response.message);
        cargarProductosDashboard();
    } catch (error) {
        alert('Error al eliminar producto: ' + error.message);
    }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarProductosDashboard();
});