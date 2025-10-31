<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Obtener pedidos
$sql = "SELECT DISTINCT p.*, c.nombre_apellidos 
FROM pedidos p 
JOIN clientes c ON p.id_cliente = c.id
JOIN lineas_pedido lp ON p.id = lp.id_pedido
JOIN lineas_menu lm ON lm.id = lp.id_linea_menu
ORDER BY lm.id_menu DESC, p.pagado DESC, p.fecha DESC
        LIMIT :limite OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT); // CORREGIDO: solo 3 parámetros
$stmt->execute();
$pedidos = $stmt->fetchAll();

// Obtener líneas para cada pedido
foreach($pedidos as &$pedido) {
    // Consulta que obtiene el nombre de la elaboración
    $sql_lineas = "SELECT lp.cantidad, lp.precio, e.nombre as producto_nombre
                   FROM lineas_pedido lp 
                   JOIN lineas_menu lm ON lp.id_linea_menu = lm.id
                   JOIN elaboraciones e ON lm.id_elaboracion = e.id
                   WHERE lp.id_pedido = ?";
    
    $stmt_lineas = $pdo->prepare($sql_lineas);
    $stmt_lineas->execute([$pedido['id']]);
    $pedido['lineas'] = $stmt_lineas->fetchAll();
}
unset($pedido);

// Contar total para paginación
$sql_count = "SELECT COUNT(*) as total FROM pedidos";
$stmt_count = $pdo->query($sql_count);
$total_pedidos = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_pedidos / $limite);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos</title>
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
                <li><a href="pedidos.php" class="active">Pedidos</a></li>
                <li><a href="../includes/logout.php">Salir</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <div class="table-header">
                <h2>Pedidos</h2>
                <!-- Botón para imprimir pedidos -->
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="imprimirPedidos()">Imprimir Pedidos</button>
                </div>
            </div>
            
<table>
    <thead>
        <tr>
            <th style="width: 30px;"></th> <!-- Columna para expandir -->
            <th>ID</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Pagado</th>
            <th>Forma Pago</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($pedidos as $pedido): ?>
        <!-- Fila principal del pedido -->
        <tr class="pedido-header" onclick="toggleLineas(<?= $pedido['id'] ?>)">
            <td>
                <span class="toggle-icon" id="icon-<?= $pedido['id'] ?>">▶</span>
            </td>
            <td><?= $pedido['id'] ?></td>
            <td><?= htmlspecialchars($pedido['nombre_apellidos']) ?></td>
            <td><?= formatoFecha($pedido['fecha']) ?></td>
            <td><?= formatoMoneda($pedido['total_pedido']) ?></td>
            <td>
                <span class="estado <?= $pedido['pagado'] == 'si' ? 'pagado-si' : 'pagado-no' ?>">
                    <?= $pedido['pagado'] == 'si' ? 'Sí' : 'No' ?>
                </span>
            </td>
            <td><?= htmlspecialchars($pedido['forma_pago']) ?></td>
            <td class="actions">
                <a href="pedido_detalle.php?id=<?= $pedido['id'] ?>" class="btn">Ver</a>
                <button class="btn btn-danger" onclick="eliminarPedido(<?= $pedido['id'] ?>)">Eliminar</button>
            </td>
        </tr>
        
        <!-- Fila expandible con las líneas del pedido -->
        <tr class="lineas-pedido" id="lineas-<?= $pedido['id'] ?>" style="display: none;">
            <td colspan="8">
                <div class="lineas-container">
                    <h4>Líneas del Pedido:</h4>
                    <?php if(!empty($pedido['lineas'])): ?>
                        <table class="lineas-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach($pedido['lineas'] as $linea): ?>
<tr>
    <td><?= htmlspecialchars($linea['producto_nombre']) ?></td>
    <td><?= $linea['cantidad'] ?></td>
    <td><?= formatoMoneda($linea['precio']) ?></td>
    <td><?= formatoMoneda($linea['cantidad'] * $linea['precio']) ?></td>
</tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay líneas en este pedido.</p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            
            <?php if($total_paginas > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if($i == $pagina): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/backend.js"></script>
</body>
</html>