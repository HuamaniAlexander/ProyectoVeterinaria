// Sistema de autenticación del dashboard
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            try {
                const response = await API.post('dashboard/auth.php', {
                    username: username,
                    password: password
                });
                
                if (response.success) {
                    // Guardar sesión
                    localStorage.setItem('dashboard_token', response.data.token);
                    
                    // Ocultar login y mostrar dashboard
                    document.getElementById('loginScreen').style.display = 'none';
                    document.getElementById('dashboard').classList.add('active');
                    
                    // Cargar datos iniciales
                    cargarDashboard();
                }
            } catch (error) {
                alert('Usuario o contraseña incorrectos');
            }
        });
    }
});

// Cerrar sesión
function logout() {
    if (confirm('¿Está seguro que desea cerrar sesión?')) {
        localStorage.removeItem('dashboard_token');
        location.reload();
    }
}