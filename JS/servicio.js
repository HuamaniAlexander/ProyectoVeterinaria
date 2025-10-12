
// FUNCIONALIDAD PÁGINA SERVICIOS

// Variables globales
const tabButtons = document.querySelectorAll('.packages__tab');
const packages = document.querySelectorAll('.package');

// SISTEMA DE PESTAÑAS (TABS)
tabButtons.forEach(button => {
    button.addEventListener('click', function() {
        const selectedPackage = this.getAttribute('data-package');
        

        tabButtons.forEach(btn => {
            btn.classList.remove('packages__tab--active');
        });
        

        this.classList.add('packages__tab--active');
        

        packages.forEach(pkg => {
            pkg.classList.remove('package--active');
        });
        

        setTimeout(() => {
            const targetPackage = document.querySelector(`[data-package-content="${selectedPackage}"]`);
            if (targetPackage) {
                targetPackage.classList.add('package--active');
            }
        }, 100);
    });
});


// FUNCIONALIDAD BOTONES "RESERVA AHORA"
const reserveButtons = document.querySelectorAll('.package__btn');

reserveButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        

        const packageCard = this.closest('.package');
        const packageName = packageCard.querySelector('.package__name').textContent;
        const packagePrice = packageCard.querySelector('.package__price').textContent;
        

        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-3px) scale(1)';
        }, 150);
        
        console.log(`Reserva solicitada: ${packageName} - ${packagePrice}`);
        
        
        alert(`¡Excelente elección!\n\nPaquete: ${packageName}\nPrecio: ${packagePrice}\n\nSerás redirigido a la página de reservas. \n(Próximamente / En Desarrollo).`);
    });
});


const showMoreBtn = document.querySelector('.packages__more-btn');

if (showMoreBtn) {
    showMoreBtn.addEventListener('click', function() {

        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-3px) scale(1)';
        }, 150);
        

        console.log('Mostrar más paquetes');
        

        alert('Próximamente más paquetes disponibles');
    });
}


// ANIMACIÓN DE ENTRADA AL CARGAR LA PÁGINA
document.addEventListener('DOMContentLoaded', function() {

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

// SMOOTH SCROLL AL CAMBIAR DE PAQUETE
tabButtons.forEach(button => {
    button.addEventListener('click', function() {

        const packagesContent = document.querySelector('.packages__content');
        if (packagesContent && window.innerWidth <= 768) {
            packagesContent.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
        }
    });

});
