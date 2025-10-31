<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/funciones.php';
verificarAuthBackend();

if (!isset($_GET['id'])) {
    header('Location: menus.php');
    exit;
}

$menu_id = (int)$_GET['id'];

// Obtener información del menú
$sql_menu = "SELECT * FROM menus WHERE id = :id";
$stmt_menu = $pdo->prepare($sql_menu);
$stmt_menu->execute([':id' => $menu_id]);
$menu = $stmt_menu->fetch();

if (!$menu) {
    header('Location: menus.php');
    exit;
}

// Obtener líneas del menú
$sql_lineas = "SELECT lm.*, e.nombre as elaboracion_nombre 
              FROM lineas_menu lm 
              JOIN elaboraciones e ON lm.id_elaboracion = e.id 
              WHERE lm.id_menu = :id_menu 
              ORDER BY lm.id";
$stmt_lineas = $pdo->prepare($sql_lineas);
$stmt_lineas->execute([':id_menu' => $menu_id]);
$lineas_menu = $stmt_lineas->fetchAll();

// Obtener elaboraciones para el select
$sql_elaboraciones = "SELECT * FROM elaboraciones ORDER BY nombre";
$stmt_elaboraciones = $pdo->query($sql_elaboraciones);
$elaboraciones = $stmt_elaboraciones->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Menú - <?= htmlspecialchars($menu['descripcion']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <h1>Sistema de Menús</h1>
            <ul class="nav-menu">
                <li><a href="configuracion.php">Configuración</a></li>
                <li><a href="clientes.php">Clientes</a></li>
                <li><a href="elaboraciones.php">Elaboraciones</a></li>
                <li><a href="menus.php">Menús</a></li>
                <li><a href="pedidos.php">Pedidos</a></li>
                <li><a href="../includes/logout.php">Salir</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Editor Menú</h2>
                <a href="menu_form.php?id=<?= $menu_id ?>" class="btn">Editar Cabecera</a>
            </div>
            
            <!-- Información del Menú -->
            <div class="menu-info" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #2c3e50;"><?= htmlspecialchars($menu['descripcion']) ?></h3>
                <p><strong>Fecha del Menú:</strong> <?= date('d/m/Y', strtotime($menu['fecha'])) ?></p>
                <p><strong>Publicación:</strong> <?= date('d/m/Y H:i', strtotime($menu['fecha_hora_publicacion'])) ?></p>
                <p><strong>Fin Pedidos:</strong> <?= date('d/m/Y H:i', strtotime($menu['fecha_hora_finalpedidos'])) ?></p>
            </div>

            <!-- Formulario para añadir línea -->
            <div style="background: #e8f4fd; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #2c3e50;">Añadir Línea al Menú</h3>
                <form id="form-linea" method="post" action="../ajax/menus_lineas.php">
                    <input type="hidden" name="accion" value="crear_linea">
                    <input type="hidden" name="id_menu" value="<?= $menu_id ?>">
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Elaboración:</label>
<select name="id_elaboracion" id="select-elaboracion" required 
        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    <option value="">Seleccionar elaboración</option>
    <?php foreach($elaboraciones as $elaboracion): ?>
    <option value="<?= $elaboracion['id'] ?>" data-precio="<?= $elaboracion['precio'] ?>">
        <?= htmlspecialchars($elaboracion['nombre']) ?> 
        (<?= number_format($elaboracion['precio'], 2, ',', '.') ?> €)
        <?php if(!empty($elaboracion['alergenos'])): ?>
            - 🚨 <?= htmlspecialchars($elaboracion['alergenos']) ?>
        <?php endif; ?>
    </option>
    <?php endforeach; ?>
</select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Precio:</label>
                            <input type="number" name="precio" id="input-precio" step="0.01" min="0" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Cantidad Disponible:</label>
                            <input type="number" name="cantidad" min="1" value="10" required 
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                   title="Cantidad total disponible para este menú">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Máx. por Pedido:</label>
                            <input type="number" name="pedido_maximo" min="1" value="5" 
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                   title="Máxima cantidad que un cliente puede pedir de este artículo">
                        </div>
                        
                        <div>
                            <button type="submit" class="btn btn-success" style="white-space: nowrap;">Añadir</button>
                        </div>
                    </div>
                </form>
                <!--
                <div style="margin-top: 15px; text-align: center;">
                    <a href="elaboracion_form.php" class="btn" target="_blank" style="font-size: 14px; padding: 8px 15px;">
                        + Nueva Elaboración
                    </a>
                </div>
                -->
            </div>

            <!-- Lista de líneas existentes -->
            <div>
                <h3 style="color: #2c3e50; margin-bottom: 20px;">Líneas del Menú</h3>
                
                <?php if(empty($lineas_menu)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <p>No hay líneas añadidas a este menú.</p>
                        <p>Añade la primera línea usando el formulario superior.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Elaboración</th>
                                    <th>Precio</th>
                                    <th>Stock Disponible</th>
                                    <th>Máx. por Pedido</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($lineas_menu as $linea): 
                                    $pedidos_realizados = calcularPedidosLinea($linea['id']);
                                    $stock_actual = $linea['stock'] - $pedidos_realizados;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($linea['elaboracion_nombre']) ?></td>
                                    <td>
                                        <?= number_format($linea['precio'], 2, ',', '.') ?> €
                                        <?php if($linea['precio'] != obtenerPrecioElaboracion($linea['id_elaboracion'])): ?>
                                            <span style="color: #e74c3c; font-size: 12px;">(*)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= $stock_actual ?></strong>
                                        <small style="color: #6c757d; display: block;">
                                            Inicial: <?= $linea['stock'] ?> | Pedidos: <?= $pedidos_realizados ?>
                                        </small>
                                    </td>
                                    <td><?= $linea['pedido_maximo'] ?: 'Sin límite' ?></td>
                                    <td class="actions">
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="eliminarLinea(<?= $linea['id'] ?>)">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions" style="margin-top: 30px;">
                <a href="menus.php" class="btn">Volver a Menús</a>
            </div>
        </div>
    </div>

    <script>
    // Cargar precio automáticamente al seleccionar elaboración
    document.getElementById('select-elaboracion').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const precio = selectedOption.getAttribute('data-precio');
        if (precio) {
            document.getElementById('input-precio').value = parseFloat(precio).toFixed(2);
        }
    });

    // Manejar el envío del formulario de línea
    document.getElementById('form-linea').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
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
            alert('Error al añadir la línea');
        });
    });

    function eliminarLinea(id) {
        if (confirm('¿Estás seguro de que quieres eliminar esta línea?')) {
            const formData = new FormData();
            formData.append('accion', 'eliminar_linea');
            formData.append('id', id);
            
            fetch('../ajax/menus_lineas.php', {
                method: 'POST',
                body: formData
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
                alert('Error al eliminar la línea');
            });
        }
    }
    </script>
</body>
</html>