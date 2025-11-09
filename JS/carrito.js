/**
 * Sistema de Carrito de Compras - PetZone
 * Incluir en todas las páginas donde se necesite el carrito
 */

const CART_API = 'api/carrito.php';

// ================================================
// INICIALIZACIÓN
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    loadCartCount();
    setupCartModal();
});

// ================================================
// CARGAR CONTADOR DEL CARRITO
// ================================================
async function loadCartCount() {
    try {
        const response = await fetch(`${CART_API}?action=get`);
        const data = await response.json();
        
        if (data.success) {
            updateCartCount(data.totales.count);
        }
    } catch (error) {
        console.error('Error al cargar carrito:', error);
    }
}

function updateCartCount(count) {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// ================================================
// CONTROLES DE CANTIDAD EN PRODUCTOS
// ================================================
function increaseQuantity(button) {
    const input = button.parentElement.querySelector('.quantity-input');
    const max = parseInt(input.getAttribute('max'));
    let value = parseInt(input.value);
    
    if (value < max) {
        input.value = value + 1;
    } else {
        showToast('Stock máximo alcanzado', 'warning');
    }
}

function decreaseQuantity(button) {
    const input = button.parentElement.querySelector('.quantity-input');
    let value = parseInt(input.value);
    
    if (value > 1) {
        input.value = value - 1;
    }
}

// ================================================
// AGREGAR AL CARRITO
// ================================================
async function addToCart(button) {
    const productCard = button.closest('.product');
    const productId = parseInt(productCard.dataset.id);
    const quantityInput = productCard.querySelector('.quantity-input');
    const quantity = parseInt(quantityInput.value);
    
    if (!productId || !quantity) {
        showToast('Error: Datos del producto inválidos', 'error');
        return;
    }
    
    // Animación del botón
    button.disabled = true;
    button.innerHTML = '<span class="material-icons rotating">sync</span> Agregando...';
    
    try {
        const response = await fetch(CART_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                producto_id: productId,
                cantidad: quantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Producto agregado al carrito', 'success');
            updateCartCount(data.cart.count);
            quantityInput.value = 1; // Resetear cantidad
            
            // Animación de éxito
            button.innerHTML = '<span class="material-icons">check_circle</span> Agregado';
            setTimeout(() => {
                button.innerHTML = '<span class="material-icons">shopping_cart</span> Agregar';
                button.disabled = false;
            }, 2000);
        } else {
            showToast(data.message || 'Error al agregar al carrito', 'error');
            button.innerHTML = '<span class="material-icons">shopping_cart</span> Agregar';
            button.disabled = false;
        }
    } catch (error) {
        showToast('Error de conexión', 'error');
        button.innerHTML = '<span class="material-icons">shopping_cart</span> Agregar';
        button.disabled = false;
    }
}

// ================================================
// MODAL DEL CARRITO
// ================================================
function setupCartModal() {
    // Crear modal del carrito si no existe
    if (!document.getElementById('cartModal')) {
        const modalHTML = `
            <div id="cartModal" class="cart-modal">
                <div class="cart-modal-content">
                    <div class="cart-modal-header">
                        <h2>Mi Carrito</h2>
                        <button class="close-cart-btn" onclick="closeCartModal()">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                    <div class="cart-modal-body" id="cartItems">
                        <div class="loading">Cargando carrito...</div>
                    </div>
                    <div class="cart-modal-footer">
                        <div class="cart-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="cartSubtotal">S/. 0.00</span>
                            </div>
                            <div class="total-row total-final">
                                <span>Total:</span>
                                <span id="cartTotal">S/. 0.00</span>
                            </div>
                        </div>
                        <div class="cart-actions">
                            <button class="btn-continue" onclick="closeCartModal()">Seguir Comprando</button>
                            <button class="btn-checkout" onclick="goToCheckout()">Proceder al Pago</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

function toggleCarrito() {
    const modal = document.getElementById('cartModal');
    modal.classList.add('active');
    loadCartItems();
}

function closeCartModal() {
    document.getElementById('cartModal').classList.remove('active');
}

async function loadCartItems() {
    try {
        const response = await fetch(`${CART_API}?action=get`);
        const data = await response.json();
        
        const cartItemsDiv = document.getElementById('cartItems');
        
        if (data.success && data.items.length > 0) {
            cartItemsDiv.innerHTML = data.items.map(item => `
                <div class="cart-item" data-id="${item.producto_id}">
                    <img src="${item.imagen || '../IMG/no-image.png'}" alt="${item.nombre}">
                    <div class="cart-item-info">
                        <h3>${item.nombre}</h3>
                        <p class="cart-item-price">S/. ${parseFloat(item.precio_unitario).toFixed(2)}</p>
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn-cart" onclick="updateCartQuantity(${item.producto_id}, ${item.cantidad - 1})">
                            <span class="material-icons">remove</span>
                        </button>
                        <span class="quantity-display">${item.cantidad}</span>
                        <button class="quantity-btn-cart" onclick="updateCartQuantity(${item.producto_id}, ${item.cantidad + 1})">
                            <span class="material-icons">add</span>
                        </button>
                    </div>
                    <div class="cart-item-subtotal">
                        S/. ${parseFloat(item.subtotal).toFixed(2)}
                    </div>
                    <button class="cart-item-remove" onclick="removeCartItem(${item.producto_id})">
                        <span class="material-icons">delete</span>
                    </button>
                </div>
            `).join('');
            
            // Actualizar totales
            document.getElementById('cartSubtotal').textContent = `S/. ${parseFloat(data.totales.subtotal).toFixed(2)}`;
            document.getElementById('cartTotal').textContent = `S/. ${parseFloat(data.totales.total).toFixed(2)}`;
        } else {
            cartItemsDiv.innerHTML = '<div class="empty-cart"><span class="material-icons">shopping_cart</span><p>Tu carrito está vacío</p></div>';
        }
    } catch (error) {
        console.error('Error al cargar items del carrito:', error);
    }
}

async function updateCartQuantity(productId, newQuantity) {
    if (newQuantity < 0) return;
    
    try {
        const response = await fetch(CART_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update',
                producto_id: productId,
                cantidad: newQuantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCartItems();
            updateCartCount(data.cart.count);
        } else {
            showToast(data.message || 'Error al actualizar cantidad', 'error');
        }
    } catch (error) {
        showToast('Error de conexión', 'error');
    }
}

async function removeCartItem(productId) {
    if (!confirm('¿Eliminar este producto del carrito?')) return;
    
    try {
        const response = await fetch(CART_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'remove',
                producto_id: productId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Producto eliminado', 'success');
            loadCartItems();
            updateCartCount(data.cart.count);
        }
    } catch (error) {
        showToast('Error al eliminar producto', 'error');
    }
}

function goToCheckout() {
    window.location.href = 'checkout.html';
}

// ================================================
// TOAST NOTIFICATIONS
// ================================================
function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <span class="material-icons">${getToastIcon(type)}</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getToastIcon(type) {
    const icons = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    return icons[type] || 'info';
}

// Estilos CSS para el carrito (agregar a style.css)
const cartStyles = `
<style>
/* Modal del Carrito */
.cart-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    overflow-y: auto;
}

.cart-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.cart-modal-content {
    background: white;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.cart-modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.close-cart-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
    transition: color 0.3s;
}

.close-cart-btn:hover {
    color: #e74c3c;
}

.cart-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    min-height: 200px;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.3s;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.cart-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item-info {
    flex: 1;
}

.cart-item-info h3 {
    font-size: 1rem;
    margin: 0 0 0.3rem 0;
}

.cart-item-price {
    color: #666;
    font-size: 0.9rem;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn-cart {
    width: 30px;
    height: 30px;
    border: 2px solid #23906F;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.quantity-btn-cart:hover {
    background-color: #23906F;
    color: white;
}

.quantity-display {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
}

.cart-item-subtotal {
    font-weight: 600;
    color: #23906F;
}

.cart-item-remove {
    background: none;
    border: none;
    color: #e74c3c;
    cursor: pointer;
    padding: 0.5rem;
}

.cart-item-remove:hover {
    color: #c0392b;
}

.cart-modal-footer {
    padding: 1.5rem 2rem;
    border-top: 2px solid #f0f0f0;
}

.cart-totals {
    margin-bottom: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 1rem;
}

.total-final {
    font-size: 1.3rem;
    font-weight: 700;
    color: #23906F;
    border-top: 2px solid #f0f0f0;
    padding-top: 1rem;
    margin-top: 0.5rem;
}

.cart-actions {
    display: flex;
    gap: 1rem;
}

.btn-continue,
.btn-checkout {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-continue {
    background-color: #95a5a6;
    color: white;
}

.btn-continue:hover {
    background-color: #7f8c8d;
}

.btn-checkout {
    background-color: #23906F;
    color: white;
}

.btn-checkout:hover {
    background-color: #1d7559;
    transform: translateY(-2px);
}

.empty-cart {
    text-align: center;
    padding: 3rem 1rem;
    color: #999;
}

.empty-cart .material-icons {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* Toast Notifications */
.toast-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 0.8rem;
    opacity: 0;
    transform: translateX(400px);
    transition: all 0.3s ease;
    z-index: 10001;
}

.toast-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.toast-success { border-left: 4px solid #28a745; }
.toast-error { border-left: 4px solid #dc3545; }
.toast-warning { border-left: 4px solid #ffc107; }
.toast-info { border-left: 4px solid #17a2b8; }

.rotating {
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .cart-modal-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .cart-item {
        flex-wrap: wrap;
    }
    
    .cart-actions {
        flex-direction: column;
    }
}
</style>
`;

// Inyectar estilos
if (!document.getElementById('cart-styles')) {
    document.head.insertAdjacentHTML('beforeend', cartStyles);
}