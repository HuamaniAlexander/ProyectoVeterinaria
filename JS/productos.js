// ============================================
// FUNCIONALIDAD PÁGINA PRODUCTOS
// ============================================

// Variables globales
const filterButtons = document.querySelectorAll('.filters__btn');
const products = document.querySelectorAll('.product');
const showMoreBtn = document.getElementById('showMoreBtn');

// ============================================
// FILTRADO DE PRODUCTOS
// ============================================
filterButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Remover clase activa de todos los botones
        filterButtons.forEach(btn => btn.classList.remove('filters__btn--active'));
        
        // Agregar clase activa al botón clickeado
        this.classList.add('filters__btn--active');
        
        // Obtener categoría seleccionada
        const filterValue = this.getAttribute('data-filter');
        
        // Mostrar/ocultar productos según filtro
        products.forEach(product => {
            const productCategory = product.getAttribute('data-category');
            
            if (filterValue === 'todos' || productCategory === filterValue) {
                product.style.display = 'flex';
            } else {
                product.style.display = 'none';
            }
        });
        
        // Reiniciar productos ocultos cuando se cambia de filtro
        resetHiddenProducts();
        updateShowMoreButton();
    });
});


// ============================================
// FUNCIONALIDAD "MOSTRAR MÁS"
// ============================================
showMoreBtn.addEventListener('click', function() {
    // Obtener todos los productos ocultos que están visibles según el filtro actual
    const hiddenProducts = Array.from(document.querySelectorAll('.product--hidden'))
        .filter(product => product.style.display !== 'none');
    
    // Mostrar los primeros 6 productos ocultos
    hiddenProducts.slice(0, 6).forEach(product => {
        product.classList.remove('product--hidden');
    });
    
    // Actualizar visibilidad del botón
    updateShowMoreButton();
});


// ============================================
// FUNCIÓN PARA ACTUALIZAR BOTÓN "MOSTRAR MÁS"
// ============================================
function updateShowMoreButton() {
    // Contar productos ocultos que están visibles según el filtro actual
    const hiddenProducts = Array.from(document.querySelectorAll('.product--hidden'))
        .filter(product => product.style.display !== 'none');
    
    // Ocultar botón si no hay más productos ocultos
    if (hiddenProducts.length === 0) {
        showMoreBtn.classList.add('catalog__more-btn--hidden');
    } else {
        showMoreBtn.classList.remove('catalog__more-btn--hidden');
    }
}


// ============================================
// FUNCIÓN PARA REINICIAR PRODUCTOS OCULTOS
// ============================================
function resetHiddenProducts() {
    // Volver a ocultar todos los productos que deberían estar ocultos
    products.forEach((product, index) => {
        if (index >= 6) { // Los primeros 6 siempre visibles
            product.classList.add('product--hidden');
        }
    });
}


// ============================================
// ANIMACIÓN DE ENTRADA DE PRODUCTOS
// ============================================
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


// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Verificar estado inicial del botón "Mostrar más"
    updateShowMoreButton();
    
    // Animar productos al cargar la página
    animateProducts();
});


// ============================================
// FUNCIONALIDAD BOTONES "COMPRAR"
// ============================================
const buyButtons = document.querySelectorAll('.product__btn');

buyButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Obtener información del producto
        const productCard = this.closest('.product');
        const productName = productCard.querySelector('.product__name').textContent;
        const productPrice = productCard.querySelector('.product__price').textContent;
        
        // Animación del botón
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
        
        // Aquí puedes agregar la funcionalidad de carrito de compras
        console.log(`Producto agregado: ${productName} - ${productPrice}`);
        
        // Opcional: Mostrar mensaje de confirmación
        alert(`¡${productName} agregado al carrito!`);
    });
});