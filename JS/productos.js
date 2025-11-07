// FUNCIONALIDAD PÁGINA PRODUCTOS CON AJAX

document.addEventListener('DOMContentLoaded', async function() {
    const filterButtons = document.querySelectorAll('.filters__btn');
    const productGrid = document.getElementById('productGrid');
    const showMoreBtn = document.getElementById('showMoreBtn');
    
    let todosLosProductos = [];
    let productosMostrados = 6;
    
    // Cargar productos al iniciar
    await cargarProductos();
    
    // FILTRADO DE PRODUCTOS
    filterButtons.forEach(button => {
        button.addEventListener('click', async function() {
            // Actualizar botón activo
            filterButtons.forEach(btn => btn.classList.remove('filters__btn--active'));
            this.classList.add('filters__btn--active');
            
            const filterValue = this.getAttribute('data-filter');
            
            // Cargar productos filtrados por AJAX
            await cargarProductos(filterValue);
            productosMostrados = 6;
            actualizarBotonMostrarMas();
        });
    });
    
    // BOTÓN MOSTRAR MÁS
    showMoreBtn.addEventListener('click', function() {
        productosMostrados += 6;
        renderizarProductos();
        actualizarBotonMostrarMas();
    });
    
    // FUNCIÓN PARA CARGAR PRODUCTOS POR AJAX
    async function cargarProductos(categoria = 'todos') {
        try {
            // Mostrar loading
            productGrid.innerHTML = '<p style="text-align: center; width: 100%;">Cargando productos...</p>';
            
            // Petición AJAX
            const params = categoria !== 'todos' ? { categoria: categoria } : {};
            const response = await API.get('productos.php', params);
            
            todosLosProductos = response.data;
            renderizarProductos();
            
        } catch (error) {
            productGrid.innerHTML = '<p style="text-align: center; width: 100%; color: red;">Error al cargar productos</p>';
            console.error('Error:', error);
        }
    }
    
    // FUNCIÓN PARA RENDERIZAR PRODUCTOS
    function renderizarProductos() {
        productGrid.innerHTML = '';
        
        const productosAMostrar = todosLosProductos.slice(0, productosMostrados);
        
        productosAMostrar.forEach(producto => {
            const productoHTML = `
                <article class="product" data-category="${producto.categoria}">
                    <div class="product__image">
                        <img src="IMG/Productos/${producto.imagen}" alt="${producto.nombre}" class="product__img">
                    </div>
                    <div class="product__info">
                        <h3 class="product__name">${producto.nombre}</h3>
                        <p class="product__description">${producto.descripcion}</p>
                        <div class="product__footer">
                            <span class="product__price">S/${producto.precio}</span>
                            <button class="product__btn" data-id="${producto.id}">Comprar</button>
                        </div>
                    </div>
                </article>
            `;
            productGrid.insertAdjacentHTML('beforeend', productoHTML);
        });
        
        // Event listeners para botones de compra
        document.querySelectorAll('.product__btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productoId = this.getAttribute('data-id');
                agregarAlCarrito(productoId);
            });
        });
    }
    
    // ACTUALIZAR BOTÓN MOSTRAR MÁS
    function actualizarBotonMostrarMas() {
        if (productosMostrados >= todosLosProductos.length) {
            showMoreBtn.classList.add('catalog__more-btn--hidden');
        } else {
            showMoreBtn.classList.remove('catalog__more-btn--hidden');
        }
    }
    
    // AGREGAR AL CARRITO
    function agregarAlCarrito(productoId) {
        const producto = todosLosProductos.find(p => p.id == productoId);
        if (producto) {
            alert(`¡${producto.nombre} agregado al carrito!`);
            // Aquí puedes agregar lógica para guardar en localStorage o enviar al servidor
        }
    }
});