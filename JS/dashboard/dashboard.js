// Sistema principal del dashboard
let currentSection = 'inicio';

// Verificar autenticación al cargar
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('dashboard_token');
    
    if (!token) {
        // Mostrar pantalla de login
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('dashboard').classList.remove('active');
    } else {
        // Verificar si el token es válido
        verificarSesion();
    }
});

// Verificar sesión activa
async function verificarSesion() {
    try {
        const response = await fetch('/ProyectoVeterinaria/api/dashboard/auth.php', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('dashboard_token')
            }
        });
        
        if (response.ok) {
            document.getElementById('loginScreen').style.display = 'none';
            document.getElementById('dashboard').classList.add('active');
            cargarDashboard();
        } else {
            logout();
        }
    } catch (error) {
        logout();
    }
}

// Cargar datos iniciales del dashboard
async function cargarDashboard() {
    try {
        // Cargar estadísticas
        await cargarEstadisticas();
        
        // Cargar actividad reciente
        await cargarActividadReciente();
        
    } catch (error) {
        console.error('Error al cargar dashboard:', error);
    }
}

// Cargar estadísticas
async function cargarEstadisticas() {
    try {
        const productos = await API.get('dashboard/productos.php');
        const sliders = await API.get('dashboard/sliders.php');
        const reservas = await API.get('dashboard/reservas.php');
        
        // Actualizar contadores en el DOM
        document.querySelector('.stat-card:nth-child(1) h3').textContent = productos.data.length;
        document.querySelector('.stat-card:nth-child(2) h3').textContent = sliders.data.filter(s => s.activo).length;
        document.querySelector('.stat-card:nth-child(4) h3').textContent = reservas.data.filter(r => r.estado === 'pendiente').length;
        
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// Cargar actividad reciente
async function cargarActividadReciente() {
    // Implementar según necesidades
    console.log('Cargando actividad reciente...');
}

// Navegación entre secciones
function showSection(sectionName) {
    // Ocultar todas las secciones
    const sections = document.querySelectorAll('.panel-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Mostrar la sección seleccionada
    const targetSection = document.getElementById('section-' + sectionName);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Actualizar sidebar activo
    const menuItems = document.querySelectorAll('.sidebar-item');
    menuItems.forEach(item => {
        item.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Cargar datos según la sección
    currentSection = sectionName;
    cargarDatosSeccion(sectionName);
    
    // Ocultar formularios
    const forms = document.querySelectorAll('.form-container');
    forms.forEach(form => {
        form.classList.remove('active');
    });
}

// Cargar datos según sección
async function cargarDatosSeccion(seccion) {
    switch (seccion) {
        case 'productos':
            await cargarProductosDashboard();
            break;
        case 'sliders':
            await cargarSlidersDashboard();
            break;
        case 'anuncios':
            await cargarAnunciosDashboard();
            break;
        case 'contenido':
            await cargarContenidoDashboard();
            break;
    }
}

// Mostrar/Ocultar formularios
function toggleForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.classList.toggle('active');
        
        if (form.classList.contains('active')) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

// Logout
async function logout() {
    try {
        await API.delete('dashboard/auth.php', {});
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    } finally {
        localStorage.removeItem('dashboard_token');
        document.getElementById('dashboard').classList.remove('active');
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('loginForm').reset();
    }
}