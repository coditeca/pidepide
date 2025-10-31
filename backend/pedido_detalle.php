<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$id_pedido = (int)$_GET['id'];

// Obtener información del pedido
$sql_pedido = "SELECT p.*, c.nombre_apellidos, c.email, c.telefono 
               FROM pedidos p 
               JOIN clientes c ON p.id_cliente = c.id 
               WHERE p.id = :id";
$stmt_pedido = $pdo->prepare($sql_pedido);
$stmt_pedido->execute([':id' => $id_pedido]);
$pedido = $stmt_pedido->fetch();

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Obtener líneas del pedido
$sql_lineas = "SELECT lp.*, lm.id_elaboracion, e.nombre as elaboracion_nombre
               FROM lineas_pedido lp
               JOIN lineas_menu lm ON lp.id_linea_menu = lm.id
               JOIN elaboraciones e ON lm.id_elaboracion = e.id
               WHERE lp.id_pedido = :id_pedido";
$stmt_lineas = $pdo->prepare($sql_lineas);
$stmt_lineas->execute([':id_pedido' => $id_pedido]);
$lineas = $stmt_lineas->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido</title>
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
            <h2>Detalle del Pedido #<?= $pedido['id'] ?></h2>
            
            <div class="pedido-info" style="margin-bottom: 30px;">
                <h3>Información del Cliente</h3>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($pedido['nombre_apellidos']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono']) ?></p>
                <p><strong>Fecha:</strong> <?= formatoFecha($pedido['fecha']) ?></p>
                <p><strong>Total:</strong> <?= formatoMoneda($pedido['total_pedido']) ?></p>
                <p><strong>Pagado:</strong> <?= $pedido['pagado'] == 'si' ? 'Sí' : 'No' ?></p>
                <p><strong>Forma de Pago:</strong> <?= htmlspecialchars($pedido['forma_pago']) ?></p>
            </div>
            
            <h3>Artículos del Pedido</h3>
            <table>
                <thead>
                    <tr>
                        <th>Elaboración</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lineas as $linea): ?>
                    <tr>
                        <td><?= htmlspecialchars($linea['elaboracion_nombre']) ?></td>
                        <td><?= $linea['cantidad'] ?></td>
                        <td><?= formatoMoneda($linea['precio']) ?></td>
                        <td><?= formatoMoneda($linea['cantidad'] * $linea['precio']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background: #f8f9fa;">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td><?= formatoMoneda($pedido['total_pedido']) ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="form-actions" style="margin-top: 30px;">
                <button onclick="marcarPagado(<?= $pedido['id'] ?>, '<?= $pedido['pagado'] == 'si' ? 'no' : 'si' ?>')" 
                        class="btn <?= $pedido['pagado'] == 'si' ? 'btn-danger' : 'btn-success' ?>">
                    <?= $pedido['pagado'] == 'si' ? 'Marcar como No Pagado' : 'Marcar como Pagado' ?>
                </button>
                <a href="pedidos.php" class="btn">Volver</a>
            </div>
        </div>
    </div>

    <script src="../js/backend.js"></script>
</body>
</html>