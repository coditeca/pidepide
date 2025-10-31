// Funcionalidad para el frontend - Versión completa
document.addEventListener('DOMContentLoaded', function() {
    // Usar delegación de eventos para evitar duplicación
    document.body.addEventListener('click', function(e) {
        // Manejar botones de cantidad
        if (e.target.classList.contains('btn-quantity')) {
            e.preventDefault();
            const btn = e.target;
            const menuId = btn.dataset.menu;
            const lineaId = btn.dataset.linea;
            const input = document.getElementById(`qty_${menuId}_${lineaId}`);
            
            if (!input) return;
            
            let value = parseInt(input.value) || 0;
            const max = parseInt(input.max) || 99;
            const min = parseInt(input.min) || 0;
            
            if (btn.classList.contains('plus')) {
                value = Math.min(value + 1, max);
            } else {
                value = Math.max(value - 1, min);
            }
            
            input.value = value;
            actualizarEstadosBotones(menuId, lineaId);
        }
        
        // Manejar botón realizar pedido
        if (e.target.classList.contains('btn-pedir')) {
            e.preventDefault();
            const menuId = e.target.dataset.menu;
            realizarPedido(menuId);
        }
        
        // Manejar botón modificar pedido
        if (e.target.classList.contains('btn-modificar')) {
            e.preventDefault();
            const menuId = e.target.dataset.menu;
            const pedidoId = e.target.dataset.pedido;
            modificarPedido(menuId, pedidoId);
        }
    });
    
    // Inicializar estados de botones
    inicializarEstadosBotones();
    
    // Validación en tiempo real para inputs
    document.querySelectorAll('.quantity').forEach(input => {
        input.addEventListener('input', function() {
            validarCantidad(this);
        });
        
        input.addEventListener('blur', function() {
            validarCantidad(this);
        });
    });
});

function inicializarEstadosBotones() {
    document.querySelectorAll('.quantity').forEach(input => {
        const idParts = input.id.replace('qty_', '').split('_');
        if (idParts.length === 2) {
            actualizarEstadosBotones(idParts[0], idParts[1]);
        }
    });
}

function actualizarEstadosBotones(menuId, lineaId) {
    const input = document.getElementById(`qty_${menuId}_${lineaId}`);
    const btnMinus = document.querySelector(`.btn-quantity.minus[data-menu="${menuId}"][data-linea="${lineaId}"]`);
    const btnPlus = document.querySelector(`.btn-quantity.plus[data-menu="${menuId}"][data-linea="${lineaId}"]`);
    
    if (!input || !btnMinus || !btnPlus) return;
    
    const value = parseInt(input.value) || 0;
    const max = parseInt(input.max) || 99;
    const min = parseInt(input.min) || 0;
    
    btnMinus.disabled = value <= min;
    btnPlus.disabled = value >= max;
}

function validarCantidad(input) {
    let value = parseInt(input.value) || 0;
    const max = parseInt(input.max) || 99;
    const min = parseInt(input.min) || 0;
    
    if (isNaN(value) || value < min) {
        value = min;
    } else if (value > max) {
        value = max;
        mostrarAlerta(`La cantidad máxima permitida es ${max}`, 'warning');
    }
    
    input.value = value;
    
    const idParts = input.id.replace('qty_', '').split('_');
    if (idParts.length === 2) {
        actualizarEstadosBotones(idParts[0], idParts[1]);
    }
}

function obtenerLineasDelMenu(menuId) {
    const lineas = [];
    let total = 0;
    let hayArticulos = false;
    
    console.log('Buscando menú:', menuId);
    
    // Recoger solo las líneas del menú específico
    const menuCard = document.querySelector(`.menu-card[data-menu-id="${menuId}"]`);
    if (!menuCard) {
        console.error('Menú no encontrado:', menuId);
        return { lineas: [], total: 0, hayArticulos: false };
    }
    
    const items = menuCard.querySelectorAll('.menu-item');
    console.log('Encontrados items:', items.length);
    
    items.forEach(item => {
        const lineaId = item.dataset.lineaId;
        const input = item.querySelector('.quantity');
        
        if (!input) {
            console.error('Input no encontrado en item:', item);
            return;
        }
        
        const cantidad = parseInt(input.value) || 0;
        console.log('Línea:', lineaId, 'Cantidad:', cantidad);
        
        if (cantidad > 0) {
            hayArticulos = true;
            const precioText = item.querySelector('.price').textContent;
            const precio = parseFloat(precioText.replace(' €', '').replace(',', '.'));
            
            console.log('Precio:', precio, 'de texto:', precioText);
            
            lineas.push({
                id_linea_menu: lineaId,
                cantidad: cantidad,
                precio: precio
            });
            
            total += cantidad * precio;
        }
    });
    
    console.log('Resultado:', { lineas, total, hayArticulos });
    return { lineas, total, hayArticulos };
}

function verificarHorarioMenu(menuId) {
    // Hacer una petición AJAX para verificar el horario en tiempo real
    return fetch('../ajax/frontend/verificar_horario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id_menu=${menuId}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.activo) {
            throw new Error('El período de pedidos para este menú ha finalizado. Por favor, actualiza la página.');
        }
        return true;
    });
}

function realizarPedido(menuId) {
    // 1. Primero verificar horario en tiempo real
    verificarHorarioMenu(menuId)
    .then(() => {
        // 2. Si está activo, continuar con el pedido
        const { lineas, total, hayArticulos } = obtenerLineasDelMenu(menuId);
        
        if (!hayArticulos) {
            mostrarAlerta('Por favor, selecciona al menos un artículo de este menú', 'warning');
            return;
        }
        
        if (confirm(`¿Confirmar pedido del menú por ${total.toFixed(2)} €?`)) {
            mostrarAlerta('Procesando pedido...', 'info');
            
            fetch('../ajax/frontend/pedidos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=crear&id_menu=${menuId}&lineas=${JSON.stringify(lineas)}&total=${total}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta('Pedido realizado con éxito!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarAlerta('Error: ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al realizar el pedido', 'warning');
            });
        }
    })
    .catch(error => {
        mostrarAlerta(error.message, 'warning');
        // Opcional: recargar la página para mostrar menús actualizados
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    });
}

function modificarPedido(menuId, pedidoId) {
    // 1. Primero verificar horario en tiempo real
    verificarHorarioMenu(menuId)
    .then(() => {
        // 2. Si está activo, continuar con la modificación
        const { lineas, total, hayArticulos } = obtenerLineasDelMenu(menuId);
        
        if (!hayArticulos) {
            if (!confirm('¿Quieres cancelar el pedido eliminando todos los artículos?')) {
                return;
            }
        }
        
        if (confirm(`¿Confirmar modificación del pedido? Nuevo total: ${total.toFixed(2)} €`)) {
            mostrarAlerta('Modificando pedido...', 'info');
            
            fetch('../ajax/frontend/pedidos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=modificar&id_pedido=${pedidoId}&id_menu=${menuId}&lineas=${JSON.stringify(lineas)}&total=${total}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta('Pedido modificado con éxito!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarAlerta('Error: ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al modificar el pedido', 'warning');
            });
        }
    })
    .catch(error => {
        mostrarAlerta(error.message, 'warning');
        // Opcional: recargar la página
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    });
}

function mostrarAlerta(mensaje, tipo = 'info') {
    // Crear elemento de alerta
    const alerta = document.createElement('div');
    alerta.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease-out;
    `;
    
    if (tipo === 'warning') {
        alerta.style.background = '#e74c3c';
    } else if (tipo === 'success') {
        alerta.style.background = '#27ae60';
    } else {
        alerta.style.background = '#3498db';
    }
    
    alerta.textContent = mensaje;
    
    // Añadir al documento
    document.body.appendChild(alerta);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        alerta.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.parentNode.removeChild(alerta);
            }
        }, 300);
    }, 3000);
}

// Añadir estilos CSS para las animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .btn-quantity.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #bdc3c7 !important;
        border-color: #95a5a6 !important;
        color: #7f8c8d !important;
    }
    
    .btn-quantity.disabled:hover {
        transform: none !important;
        background-color: #bdc3c7 !important;
    }
    
    /* Para botones nativamente deshabilitados */
    .btn-quantity:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #bdc3c7;
        border-color: #95a5a6;
        color: #7f8c8d;
    }
    
    .btn-quantity:disabled:hover {
        background-color: #bdc3c7;
        color: #7f8c8d;
        transform: none;
    }
`;
document.head.appendChild(style);


// Verificar periodicamente si los menús siguen activos
setInterval(() => {
    const menusActivos = document.querySelectorAll('.menu-card[data-menu-id]');
    menusActivos.forEach(menuCard => {
        const menuId = menuCard.dataset.menuId;
        verificarHorarioMenu(menuId)
        .catch(error => {
            // Si el menú ya no está activo, deshabilitar botones
            const btnPedir = menuCard.querySelector('.btn-pedir');
            const btnModificar = menuCard.querySelector('.btn-modificar');
            
            if (btnPedir) btnPedir.disabled = true;
            if (btnModificar) btnModificar.disabled = true;
            
            // Mostrar badge de inactivo
            let badge = menuCard.querySelector('.horario-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'horario-badge';
                badge.style.cssText = 'background: #dc3545; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; margin-left: 10px;';
                badge.textContent = 'Fuera de horario';
                menuCard.querySelector('.menu-header').appendChild(badge);
            }
        });
    });
}, 60000); // Verificar cada minuto