// Funciones para el backend
function eliminarCliente(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
        fetch('../ajax/clientes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=eliminar&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el cliente');
        });
    }
}

function eliminarElaboracion(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta elaboración?')) {
        fetch('../ajax/elaboraciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=eliminar&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la elaboración');
        });
    }
}

function eliminarMenu(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este menú?')) {
        fetch('../ajax/menus.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=eliminar&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el menú');
        });
    }
}

function eliminarPedido(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este pedido?')) {
        fetch('../ajax/backend/pedidos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=eliminar&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el pedido');
        });
    }
}

function marcarPagado(id, estado) {
    if (confirm('¿Estás seguro de que quieres cambiar el estado de pago?')) {
        fetch('../ajax/backend/pedidos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=marcar_pagado&id=${id}&pagado=${estado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el estado del pedido');
        });
    }
}

function toggleLineas(pedidoId) {
    const lineasRow = document.getElementById(`lineas-${pedidoId}`);
    const icon = document.getElementById(`icon-${pedidoId}`);
    
    if (lineasRow.style.display === 'none') {
        lineasRow.style.display = 'table-row';
        icon.classList.add('rotated');
    } else {
        lineasRow.style.display = 'none';
        icon.classList.remove('rotated');
    }
}

// Prevenir que el clic en acciones expanda/contraiga
document.addEventListener('click', function(e) {
    if (e.target.closest('.actions')) {
        e.stopPropagation();
    }
});

function imprimirPedidos() {
    // Abrir en una nueva pestaña
    window.open('generar_pdf_pedidos.php', '_blank');
}

// También puedes añadir esta función si quieres una confirmación
function confirmarImpresion() {
    if (confirm('¿Generar PDF con los pedidos del último menú?')) {
        imprimirPedidos();
    }
}