<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Obtener clientes
$sql = "SELECT * FROM clientes ORDER BY id DESC LIMIT :limite OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll();

// Contar total para paginación
$sql_count = "SELECT COUNT(*) as total FROM clientes";
$stmt_count = $pdo->query($sql_count);
$total_clientes = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_clientes / $limite);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <h1>Sistema de Menús</h1>
            <ul class="nav-menu">
                <li><a href="configuracion.php">Configuración</a></li>
                <li><a href="clientes.php" class="active">Clientes</a></li>
                <li><a href="elaboraciones.php">Elaboraciones</a></li>
                <li><a href="menus.php">Menús</a></li>
                <li><a href="pedidos.php">Pedidos</a></li>
                <li><a href="../includes/logout.php">Salir</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <div class="table-header">
                <h2>Clientes</h2>
                <a href="cliente_form.php" class="btn btn-success">Nuevo Cliente</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre y Apellidos</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clientes as $cliente): ?>
                    <tr>
                        <td><?= $cliente['id'] ?></td>
                        <td><?= htmlspecialchars($cliente['nombre_apellidos']) ?></td>
                        <td><?= htmlspecialchars($cliente['email']) ?></td>
                        <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                        <td><?= $cliente['activo'] ? '?' : '?' ?></td>
                        <td class="actions">
                            <a href="cliente_form.php?id=<?= $cliente['id'] ?>" class="btn">Editar</a>
                            <button class="btn btn-danger" onclick="eliminarCliente(<?= $cliente['id'] ?>)">Eliminar</button>
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