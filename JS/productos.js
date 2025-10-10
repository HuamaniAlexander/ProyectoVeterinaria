
// FUNCIONALIDAD PÁGINA PRODUCTOS

// Variables globales
const filterButtons = document.querySelectorAll('.filters__btn');
const products = document.querySelectorAll('.product');
const showMoreBtn = document.getElementById('showMoreBtn');

// FILTRADO DE PRODUCTOS
filterButtons.forEach(button => {
    button.addEventListener('click', function() {

        filterButtons.forEach(btn => btn.classList.remove('filters__btn--active'));
        

        this.classList.add('filters__btn--active');
        

        const filterValue = this.getAttribute('data-filter');
        
        products.forEach(product => {
            
            const productCategory = product.getAttribute('data-category');
            
            if (filterValue === 'todos' || productCategory === filterValue) {
                product.style.display = 'flex';
            } else {
                product.style.display = 'none';
            }
        });
        
        resetHiddenProducts();
        updateShowMoreButton();
    });
});

// FUNCIONALIDAD "MOSTRAR MÁS"
showMoreBtn.addEventListener('click', function() {
    const hiddenProducts = Array.from(document.querySelectorAll('.product--hidden'))
        .filter(product => product.style.display !== 'none');
    
    hiddenProducts.slice(0, 6).forEach(product => {
        product.classList.remove('product--hidden');
    });
    
    updateShowMoreButton();
});

// FUNCIÓN PARA ACTUALIZAR BOTÓN "MOSTRAR MÁS"
function updateShowMoreButton() {

    const hiddenProducts = Array.from(document.querySelectorAll('.product--hidden'))
        .filter(product => product.style.display !== 'none');
    

    if (hiddenProducts.length === 0) {
        showMoreBtn.classList.add('catalog__more-btn--hidden');
    } else {
        showMoreBtn.classList.remove('catalog__more-btn--hidden');
    }
}

// FUNCIÓN PARA REINICIAR PRODUCTOS OCULTOS
function resetHiddenProducts() {
    products.forEach((product, index) => {
        if (index >= 6) { // Los primeros 6 siempre visibles
            product.classList.add('product--hidden');
        }
    });
}

// ANIMACIÓN DE ENTRADA DE PRODUCTOS
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

// INICIALIZACIÓN
document.addEventListener('DOMContentLoaded', function() {
    updateShowMoreButton();
    
    animateProducts();
});

// FUNCIONALIDAD BOTONES "COMPRAR"
const buyButtons = document.querySelectorAll('.product__btn');

buyButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const productCard = this.closest('.product');
        const productName = productCard.querySelector('.product__name').textContent;
        const productPrice = productCard.querySelector('.product__price').textContent;
        
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
        
        console.log(`Producto agregado: ${productName} - ${productPrice}`);

        alert(`¡${productName} agregado al carrito!`);
    });
});