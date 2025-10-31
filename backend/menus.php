<?php

session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Obtener menús
$sql = "SELECT * FROM menus ORDER BY fecha DESC LIMIT :limite OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$menus = $stmt->fetchAll();

// Contar total para paginación
$sql_count = "SELECT COUNT(*) as total FROM menus";
$stmt_count = $pdo->query($sql_count);
$total_menus = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_menus / $limite);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Menús</title>
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
                <li><a href="menus.php" class="active">Menús</a></li>
                <li><a href="pedidos.php">Pedidos</a></li>
                <li><a href="../includes/logout.php">Salir</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <div class="table-header">
                <h2>Menús</h2>
                <a href="menu_form.php" class="btn btn-success">Nuevo Menú</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Publicación</th>
                        <th>Fin Pedidos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($menus as $menu): ?>
                    <tr>
                        <td><?= $menu['id'] ?></td>
                        <td><?= htmlspecialchars($menu['descripcion']) ?></td>
                        <td><?= formatoFecha($menu['fecha']) ?></td>
                        <td><?= formatoFecha($menu['fecha_hora_publicacion']) ?></td>
                        <td><?= formatoFecha($menu['fecha_hora_finalpedidos']) ?></td>
                        <td class="actions">
                            <a href="menu_detalle.php?id=<?= $menu['id'] ?>" class="btn">Gestionar</a>
                            <button class="btn btn-danger" onclick="eliminarMenu(<?= $menu['id'] ?>)">Eliminar</button>
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