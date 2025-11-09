/**
 * Checkout Page - PetZone
 * Archivo: JS/checkout.js
 */

const CART_API = '../api/carrito.php';
const ENVIO_COSTO = 10.00;

// ================================================
// INICIALIZACIÓN
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    loadCheckoutSummary();
    setupFormValidation();
});

// ================================================
// CARGAR RESUMEN DEL PEDIDO
// ================================================
async function loadCheckoutSummary() {
    try {
        const response = await fetch(`${CART_API}?action=get`);
        const data = await response.json();
        
        const summaryItemsDiv = document.getElementById('summaryItems');
        
        if (data.success && data.items.length > 0) {
            // Mostrar items
            summaryItemsDiv.innerHTML = data.items.map(item => `
                <div class="summary-item">
                    <img src="${item.imagen || '../IMG/no-image.png'}" alt="${item.nombre}">
                    <div class="summary-item-info">
                        <div class="summary-item-name">${item.nombre}</div>
                        <div class="summary-item-details">
                            Cantidad: ${item.cantidad} x S/. ${parseFloat(item.precio_unitario).toFixed(2)}
                        </div>
                    </div>
                    <div class="summary-item-price">
                        S/. ${parseFloat(item.subtotal).toFixed(2)}
                    </div>
                </div>
            `).join('');
            
            // Calcular totales
            const subtotal = parseFloat(data.totales.subtotal);
            const envio = ENVIO_COSTO;
            const total = subtotal + envio;
            
            // Actualizar totales en el DOM
            document.getElementById('summarySubtotal').textContent = `S/. ${subtotal.toFixed(2)}`;
            document.getElementById('summaryEnvio').textContent = `S/. ${envio.toFixed(2)}`;
            document.getElementById('summaryTotal').textContent = `S/. ${total.toFixed(2)}`;
            
        } else {
            // Carrito vacío
            summaryItemsDiv.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #999;">
                    <span class="material-icons" style="font-size: 3rem; display: block; margin-bottom: 1rem;">shopping_cart</span>
                    <p>Tu carrito está vacío</p>
                    <a href="productos.html" style="color: #23906F; text-decoration: none; margin-top: 1rem; display: inline-block;">
                        Ir a comprar
                    </a>
                </div>
            `;
            
            // Deshabilitar botón de pago
            document.querySelector('.btn-place-order').disabled = true;
            document.querySelector('.btn-place-order').style.opacity = '0.5';
            document.querySelector('.btn-place-order').style.cursor = 'not-allowed';
        }
    } catch (error) {
        console.error('Error al cargar resumen:', error);
        showToast('Error al cargar el carrito', 'error');
    }
}

// ================================================
// VALIDACIÓN DEL FORMULARIO
// ================================================
function setupFormValidation() {
    const form = document.getElementById('checkoutForm');
    
    form.addEventListener('submit', (e) => {
        e.preventDefault();
    });
    
    // Validación en tiempo real
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', () => {
            validateField(input);
        });
    });
}

function validateField(field) {
    if (field.hasAttribute('required') && field.value.trim() === '') {
        field.style.borderColor = '#e74c3c';
        return false;
    } else {
        field.style.borderColor = '#e0e0e0';
        return true;
    }
}

function validateForm() {
    const form = document.getElementById('checkoutForm');
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        showToast('Por favor completa todos los campos requeridos', 'warning');
    }
    
    return isValid;
}

// ================================================
// REALIZAR PEDIDO
// ================================================
async function placeOrder() {
    // Validar formulario
    if (!validateForm()) {
        return;
    }
    
    // Obtener datos del formulario
    const nombre = document.getElementById('nombre').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const email = document.getElementById('email').value.trim();
    const direccion = document.getElementById('direccion').value.trim();
    const distrito = document.getElementById('distrito').value.trim();
    const referencia = document.getElementById('referencia').value.trim();
    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;
    const notas = document.getElementById('notas').value.trim();
    
    // Construir dirección completa
    const direccionCompleta = `${direccion}, ${distrito}${referencia ? ', Ref: ' + referencia : ''}`;
    
    // Mostrar botón de carga
    const btnOrder = document.querySelector('.btn-place-order');
    const originalText = btnOrder.innerHTML;
    btnOrder.disabled = true;
    btnOrder.innerHTML = '<span class="material-icons rotating">sync</span> Procesando...';
    
    try {
        const response = await fetch(CART_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'checkout',
                nombre: nombre,
                email: email,
                telefono: telefono,
                direccion: direccionCompleta,
                metodo_pago: metodoPago,
                notas: notas
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar modal de éxito
            showSuccessModal(data.codigo_pedido);
            
            // Limpiar formulario
            document.getElementById('checkoutForm').reset();
            
            // Actualizar contador del carrito
            if (typeof updateCartCount === 'function') {
                updateCartCount(0);
            }
        } else {
            showToast(data.message || 'Error al procesar el pedido', 'error');
            btnOrder.disabled = false;
            btnOrder.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión al procesar el pedido', 'error');
        btnOrder.disabled = false;
        btnOrder.innerHTML = originalText;
    }
}

// ================================================
// MODAL DE ÉXITO
// ================================================
function showSuccessModal(codigoPedido) {
    const modal = document.getElementById('successModal');
    document.getElementById('orderCode').textContent = codigoPedido;
    modal.classList.add('active');
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('active');
    
    // Redirigir a la página principal después de 500ms
    setTimeout(() => {
        window.location.href = '../index.html';
    }, 500);
}

// ================================================
// TOAST NOTIFICATIONS
// ================================================
function showToast(message, type = 'info') {
    // Remover toast existente si hay
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

// ================================================
// VALIDACIÓN DE EMAIL
// ================================================
document.getElementById('email')?.addEventListener('blur', function() {
    const email = this.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.style.borderColor = '#e74c3c';
        showToast('Por favor ingresa un email válido', 'warning');
    } else {
        this.style.borderColor = '#e0e0e0';
    }
});

// ================================================
// VALIDACIÓN DE TELÉFONO
// ================================================
document.getElementById('telefono')?.addEventListener('input', function() {
    // Solo permitir números
    this.value = this.value.replace(/[^0-9]/g, '');
    
    // Limitar a 9 dígitos
    if (this.value.length > 9) {
        this.value = this.value.slice(0, 9);
    }
});

// Evitar envío del formulario con Enter
document.getElementById('checkoutForm')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});