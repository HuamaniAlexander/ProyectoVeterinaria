/**
 * Checkout Page - PetZone
 * Sistema Completo de Pago con QR y Validaciones
 * Archivo: JS/checkout.js
 */

const CART_API = '../api/carrito.php';
const ENVIO_COSTO = 10.00;
const ENVIO_GRATIS_DESDE = 100.00;

// ================================================
// INICIALIZACIÓN
// ================================================
document.addEventListener('DOMContentLoaded', () => {
    loadCheckoutSummary();
    setupFormValidation();
    setupPaymentMethodListeners();
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
            const envio = subtotal >= ENVIO_GRATIS_DESDE ? 0 : ENVIO_COSTO;
            const total = subtotal + envio;
            
            // Actualizar totales en el DOM
            document.getElementById('summarySubtotal').textContent = `S/. ${subtotal.toFixed(2)}`;
            
            const envioElement = document.getElementById('summaryEnvio');
            if (envio === 0) {
                envioElement.innerHTML = '<span style="color: #28a745; font-weight: 700;">GRATIS</span>';
            } else {
                envioElement.textContent = `S/. ${envio.toFixed(2)}`;
            }
            
            document.getElementById('summaryTotal').textContent = `S/. ${total.toFixed(2)}`;
            
            // Mostrar mensaje de envío gratis si aplica
            if (subtotal >= ENVIO_GRATIS_DESDE) {
                showToast('🎉 ¡Tienes envío gratis!', 'success');
            }
            
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
            const btnOrder = document.querySelector('.btn-place-order');
            btnOrder.disabled = true;
            btnOrder.style.opacity = '0.5';
            btnOrder.style.cursor = 'not-allowed';
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
        
        input.addEventListener('input', () => {
            if (input.classList.contains('error')) {
                validateField(input);
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Limpiar error anterior
    removeFieldError(field);
    
    // Validar campo requerido
    if (field.hasAttribute('required') && value === '') {
        isValid = false;
        errorMessage = 'Este campo es obligatorio';
    }
    
    // Validaciones específicas
    if (isValid && value !== '') {
        switch(field.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Correo electrónico inválido';
                }
                break;
                
            case 'tel':
                // Validar teléfono peruano (9 dígitos)
                const phoneRegex = /^[9]\d{8}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Debe ser un celular válido (9 dígitos, empieza con 9)';
                }
                break;
        }
        
        // Validar nombre (solo letras y espacios)
        if (field.id === 'nombre') {
            const nameRegex = /^[a-záéíóúñA-ZÁÉÍÓÚÑ\s]+$/;
            if (!nameRegex.test(value)) {
                isValid = false;
                errorMessage = 'Solo se permiten letras y espacios';
            } else if (value.length < 3) {
                isValid = false;
                errorMessage = 'El nombre debe tener al menos 3 caracteres';
            }
        }
        
        // Validar dirección
        if (field.id === 'direccion' && value.length < 10) {
            isValid = false;
            errorMessage = 'La dirección debe ser más específica (mín. 10 caracteres)';
        }
        
        // Validar distrito
        if (field.id === 'distrito' && value.length < 3) {
            isValid = false;
            errorMessage = 'Ingresa un distrito válido';
        }
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    field.style.borderColor = '#e74c3c';
    
    // Remover mensaje de error anterior si existe
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Crear mensaje de error
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '0.3rem';
    errorDiv.textContent = message;
    
    field.parentElement.appendChild(errorDiv);
}

function removeFieldError(field) {
    field.classList.remove('error');
    field.style.borderColor = '#e0e0e0';
    
    const errorDiv = field.parentElement.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function validateForm() {
    const form = document.getElementById('checkoutForm');
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
            if (!firstInvalidField) {
                firstInvalidField = input;
            }
        }
    });
    
    if (!isValid) {
        showToast('Por favor completa correctamente todos los campos', 'warning');
        if (firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    return isValid;
}

// ================================================
// SISTEMA DE MÉTODOS DE PAGO
// ================================================
function setupPaymentMethodListeners() {
    const paymentOptions = document.querySelectorAll('input[name="metodo_pago"]');
    
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            const method = this.value;
            handlePaymentMethodChange(method);
        });
    });
}

function handlePaymentMethodChange(method) {
    // Remover modal de pago anterior si existe
    const existingModal = document.getElementById('paymentModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    console.log(`Método de pago seleccionado: ${method}`);
}

// ================================================
// REALIZAR PEDIDO CON VALIDACIONES
// ================================================
async function placeOrder() {
    // 1. Validar formulario
    if (!validateForm()) {
        return;
    }
    
    // 2. Validar método de pago
    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
    if (!metodoPago) {
        showToast('Por favor selecciona un método de pago', 'warning');
        return;
    }
    
    // 3. Obtener datos del formulario
    const nombre = document.getElementById('nombre').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const email = document.getElementById('email').value.trim();
    const direccion = document.getElementById('direccion').value.trim();
    const distrito = document.getElementById('distrito').value.trim();
    const referencia = document.getElementById('referencia').value.trim();
    const notas = document.getElementById('notas').value.trim();
    
    const direccionCompleta = `${direccion}, ${distrito}${referencia ? ', Ref: ' + referencia : ''}`;
    
    // 4. Verificar carrito no vacío
    try {
        const cartResponse = await fetch(`${CART_API}?action=get`);
        const cartData = await cartResponse.json();
        
        if (!cartData.success || cartData.items.length === 0) {
            showToast('Tu carrito está vacío', 'error');
            setTimeout(() => {
                window.location.href = 'productos.html';
            }, 2000);
            return;
        }
        
        // 5. Procesar según método de pago
        if (metodoPago.value === 'yape' || metodoPago.value === 'plin') {
            // Mostrar modal de QR
            showQRPaymentModal(metodoPago.value, {
                nombre,
                telefono,
                email,
                direccion: direccionCompleta,
                metodo_pago: metodoPago.value,
                notas,
                total: cartData.totales.subtotal + (cartData.totales.subtotal >= ENVIO_GRATIS_DESDE ? 0 : ENVIO_COSTO)
            });
        } else {
            // Procesar pago directamente (tarjeta, efectivo, transferencia)
            await processPayment({
                nombre,
                telefono,
                email,
                direccion: direccionCompleta,
                metodo_pago: metodoPago.value,
                notas
            });
        }
        
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al procesar el pedido', 'error');
    }
}

// ================================================
// MODAL DE PAGO QR (YAPE / PLIN)
// ================================================
function showQRPaymentModal(method, orderData) {
    const methodName = method === 'yape' ? 'Yape' : 'Plin';
    const qrImage = method === 'yape' 
        ? '../IMG/qr-yape.png' 
        : '../IMG/qr-plin.png';
    
    const modal = document.createElement('div');
    modal.id = 'paymentModal';
    modal.className = 'payment-modal active';
    modal.innerHTML = `
        <div class="payment-modal-overlay"></div>
        <div class="payment-modal-content">
            <div class="payment-modal-header">
                <h2>
                    <span class="material-icons">phone_android</span>
                    Pagar con ${methodName}
                </h2>
                <button class="close-payment-btn" onclick="closePaymentModal()">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <div class="payment-modal-body">
                <div class="qr-instructions">
                    <h3>Instrucciones de pago:</h3>
                    <ol>
                        <li>Abre tu app de ${methodName}</li>
                        <li>Escanea el código QR</li>
                        <li>Verifica el monto: <strong>S/. ${orderData.total.toFixed(2)}</strong></li>
                        <li>Completa el pago</li>
                        <li>Confirma tu pedido abajo</li>
                    </ol>
                </div>
                
                <div class="qr-code-container">
                    <div class="qr-code">
                        <img src="${qrImage}" alt="QR ${methodName}" onerror="this.src='../IMG/qr-placeholder.png'">
                    </div>
                    <div class="qr-amount">
                        <span class="material-icons">account_balance_wallet</span>
                        Monto a pagar: <strong>S/. ${orderData.total.toFixed(2)}</strong>
                    </div>
                </div>
                
                <div class="payment-verification">
                    <label class="checkbox-label">
                        <input type="checkbox" id="paymentConfirmation">
                        <span>He realizado el pago correctamente</span>
                    </label>
                </div>
                
                <div class="payment-note">
                    <span class="material-icons">info</span>
                    <p>Una vez confirmado, tu pedido será procesado. Recibirás un correo con los detalles.</p>
                </div>
            </div>
            
            <div class="payment-modal-footer">
                <button class="btn-cancel-payment" onclick="closePaymentModal()">
                    Cancelar
                </button>
                <button class="btn-confirm-payment" onclick="confirmQRPayment(${JSON.stringify(orderData).replace(/"/g, '&quot;')})">
                    <span class="material-icons">check_circle</span>
                    Confirmar Pedido
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Agregar event listener al overlay
    modal.querySelector('.payment-modal-overlay').addEventListener('click', closePaymentModal);
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

async function confirmQRPayment(orderData) {
    const confirmation = document.getElementById('paymentConfirmation');
    
    if (!confirmation.checked) {
        showToast('Por favor confirma que has realizado el pago', 'warning');
        return;
    }
    
    // Deshabilitar botón
    const btnConfirm = document.querySelector('.btn-confirm-payment');
    btnConfirm.disabled = true;
    btnConfirm.innerHTML = '<span class="material-icons rotating">sync</span> Procesando...';
    
    await processPayment(orderData);
}

// ================================================
// PROCESAR PAGO
// ================================================
async function processPayment(orderData) {
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
                ...orderData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Cerrar modal de pago si existe
            closePaymentModal();
            
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
    
    // Confetti animation (opcional)
    if (typeof confetti !== 'undefined') {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    }
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('active');
    
    setTimeout(() => {
        window.location.href = '../index.html';
    }, 500);
}

// ================================================
// VALIDACIONES ESPECÍFICAS
// ================================================
// Validación de email en tiempo real
document.getElementById('email')?.addEventListener('blur', function() {
    validateField(this);
});

// Validación de teléfono - solo números
document.getElementById('telefono')?.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    
    if (this.value.length > 9) {
        this.value = this.value.slice(0, 9);
    }
});

// Validación de nombre - solo letras
document.getElementById('nombre')?.addEventListener('input', function() {
    this.value = this.value.replace(/[^a-záéíóúñA-ZÁÉÍÓÚÑ\s]/g, '');
});

// Evitar envío del formulario con Enter (excepto en textarea)
document.getElementById('checkoutForm')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});

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
    }, 4000);
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

console.log('✅ Checkout.js cargado - Sistema completo de pago');