/**
 * Funcionalidad Página Productos
 * Archivo: JS/productos.js
 * Solo maneja filtros y botón "mostrar más"
 * El carrito se maneja en carrito.js
 */

// Variables globales
const filterButtons = document.querySelectorAll('.filters__btn');
const products = document.querySelectorAll('.product');
const showMoreBtn = document.getElementById('showMoreBtn');

// ================================================
// FILTRADO DE PRODUCTOS
// ================================================
filterButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Remover clase activa de todos
        filterButtons.forEach(btn => btn.classList.remove('filters__btn--active'));
        
        // Activar botón clickeado
        this.classList.add('filters__btn--active');
        
        // Obtener valor del filtro
        const filterValue = this.getAttribute('data-filter');
        
        // Filtrar productos
        products.forEach(product => {
            const productCategory = product.getAttribute('data-category');
            
            if (filterValue === 'todos' || productCategory === filterValue) {
                product.style.display = 'flex';
            } else {
                product.style.display = 'none';
            }
        });
        
        // Resetear productos ocultos y actualizar botón
        resetHiddenProducts();
        updateShowMoreButton();
    });
});

// ================================================
// FUNCIONALIDAD "MOSTRAR MÁS"
// ================================================
showMoreBtn.addEventListener('click', function() {
    const hiddenProducts = Array.from(document.querySelectorAll('.product--hidden'))
        .filter(product => product.style.display !== 'none');
    
    // Mostrar los primeros 6 productos ocultos
    hiddenProducts.slice(0, 6).forEach(product => {
        product.classList.remove('product--hidden');
    });
    
    updateShowMoreButton();
});

// ================================================
// FUNCIÓN PARA ACTUALIZAR BOTÓN "MOSTRAR MÁS"
// ================================================
function updateShowMoreButton() {
    const hiddenProducts = Array.from(document.querySelectorAll('.product--hidden'))
        .filter(product => product.style.display !== 'none');
    
    if (hiddenProducts.length === 0) {
        showMoreBtn.classList.add('catalog__more-btn--hidden');
    } else {
        showMoreBtn.classList.remove('catalog__more-btn--hidden');
    }
}

// ================================================
// FUNCIÓN PARA REINICIAR PRODUCTOS OCULTOS
// ================================================
function resetHiddenProducts() {
    products.forEach((product, index) => {
        // Los primeros 6 siempre visibles
        if (index >= 6) {
            product.classList.add('product--hidden');
        }
    });
}

// ================================================
// ANIMACIÓN DE ENTRADA DE PRODUCTOS
// ================================================
function animateProducts() {
    const visibleProducts = Array.from(products).filter(product => 
        !product.classList.contains('product--hidden') && product.style.display !== 'none'
    );
    
    visibleProducts.forEach((product, index) => {
        setTimeout(() => {
            product.style.opacity = '0';
            product.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                product.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                product.style.opacity = '1';
                product.style.transform = 'translateY(0)';
            }, 50);
        }, index * 50);
    });
}

// ================================================
// INICIALIZACIÓN
// ================================================
document.addEventListener('DOMContentLoaded', function() {
    updateShowMoreButton();
    animateProducts();
    
    console.log('✅ Productos.js cargado correctamente');
    console.log('📦 Total de productos:', products.length);
});