// ============================================
// FUNCIONALIDAD PÁGINA SERVICIOS
// ============================================

// Variables globales
const tabButtons = document.querySelectorAll('.packages__tab');
const packages = document.querySelectorAll('.package');

// ============================================
// SISTEMA DE PESTAÑAS (TABS)
// ============================================
tabButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Obtener el paquete seleccionado
        const selectedPackage = this.getAttribute('data-package');
        
        // Remover clase activa de todos los botones
        tabButtons.forEach(btn => {
            btn.classList.remove('packages__tab--active');
        });
        
        // Agregar clase activa al botón clickeado
        this.classList.add('packages__tab--active');
        
        // Ocultar todos los paquetes
        packages.forEach(pkg => {
            pkg.classList.remove('package--active');
        });
        
        // Mostrar el paquete seleccionado con animación
        setTimeout(() => {
            const targetPackage = document.querySelector(`[data-package-content="${selectedPackage}"]`);
            if (targetPackage) {
                targetPackage.classList.add('package--active');
            }
        }, 100);
    });
});


// ============================================
// FUNCIONALIDAD BOTONES "RESERVA AHORA"
// ============================================
const reserveButtons = document.querySelectorAll('.package__btn');

reserveButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Obtener información del paquete
        const packageCard = this.closest('.package');
        const packageName = packageCard.querySelector('.package__name').textContent;
        const packagePrice = packageCard.querySelector('.package__price').textContent;
        
        // Animación del botón
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-3px) scale(1)';
        }, 150);
        
        // Aquí puedes redirigir a la página de reservas o abrir un modal
        console.log(`Reserva solicitada: ${packageName} - ${packagePrice}`);
        
        // Opcional: Redirigir a página de reservas
        // window.location.href = 'reserva.html?paquete=' + encodeURIComponent(packageName);
        
        // Opcional: Mostrar mensaje de confirmación
        alert(`¡Excelente elección!\n\nPaquete: ${packageName}\nPrecio: ${packagePrice}\n\nSerás redirigido a la página de reservas.`);
    });
});


// ============================================
// FUNCIONALIDAD BOTÓN "MOSTRAR MÁS"
// ============================================
const showMoreBtn = document.querySelector('.packages__more-btn');

if (showMoreBtn) {
    showMoreBtn.addEventListener('click', function() {
        // Animación del botón
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-3px) scale(1)';
        }, 150);
        
        // Aquí puedes agregar la funcionalidad para mostrar más paquetes
        // Por ejemplo, mostrar paquetes ocultos o redirigir a otra página
        console.log('Mostrar más paquetes');
        
        // Opcional: Mostrar mensaje
        alert('Próximamente más paquetes disponibles');
    });
}


// ============================================
// ANIMACIÓN DE ENTRADA AL CARGAR LA PÁGINA
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Animar tabs al cargar
    tabButtons.forEach((button, index) => {
        button.style.opacity = '0';
        button.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            button.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            button.style.opacity = '1';
            button.style.transform = 'translateY(0)';
        }, index * 100);
    });
});


// ============================================
// NAVEGACIÓN CON TECLADO (ACCESIBILIDAD)
// ============================================
tabButtons.forEach((button, index) => {
    button.addEventListener('keydown', function(e) {
        let newIndex;
        
        // Flecha derecha o abajo
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            newIndex = (index + 1) % tabButtons.length;
            tabButtons[newIndex].focus();
            tabButtons[newIndex].click();
        }
        
        // Flecha izquierda o arriba
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            newIndex = (index - 1 + tabButtons.length) % tabButtons.length;
            tabButtons[newIndex].focus();
            tabButtons[newIndex].click();
        }
        
        // Enter o Espacio
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
});


// ============================================
// SMOOTH SCROLL AL CAMBIAR DE PAQUETE
// ============================================
tabButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Hacer scroll suave hacia el contenido del paquete
        const packagesContent = document.querySelector('.packages__content');
        if (packagesContent && window.innerWidth <= 768) {
            packagesContent.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
        }
    });
});