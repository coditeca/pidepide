<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Obtener elaboraciones
$sql = "SELECT * FROM elaboraciones ORDER BY id DESC LIMIT :limite OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$elaboraciones = $stmt->fetchAll();

// Contar total para paginación
$sql_count = "SELECT COUNT(*) as total FROM elaboraciones";
$stmt_count = $pdo->query($sql_count);
$total_elaboraciones = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_elaboraciones / $limite);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Elaboraciones</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <h1>Sistema de Menús</h1>
            <ul class="nav-menu">
                <li><a href="configuracion.php">Configuración</a></li>
                <li><a href="clientes.php">Clientes</a></li>
                <li><a href="elaboraciones.php" class="active">Elaboraciones</a></li>
                <li><a href="menus.php">Menús</a></li>
                <li><a href="pedidos.php">Pedidos</a></li>
                <li><a href="../includes/logout.php">Salir</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <div class="table-header">
                <h2>Elaboraciones</h2>
                <a href="elaboracion_form.php" class="btn btn-success">Nueva Elaboración</a>
            </div>
            
<!-- En la tabla, añadir la columna de alérgenos -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Alérgenos</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($elaboraciones as $elaboracion): ?>
        <tr>
            <td><?= $elaboracion['id'] ?></td>
            <td><?= htmlspecialchars($elaboracion['nombre']) ?></td>
            <td><?= number_format($elaboracion['precio'], 2, ',', '.') ?> €</td>
            <td>
                <?php if(!empty($elaboracion['alergenos'])): ?>
                    <span style="font-size: 12px; color: #666;"><?= htmlspecialchars($elaboracion['alergenos']) ?></span>
                <?php else: ?>
                    <span style="color: #999;">-</span>
                <?php endif; ?>
            </td>
            <td class="actions">
                <a href="elaboracion_form.php?id=<?= $elaboracion['id'] ?>" class="btn">Editar</a>
                <button class="btn btn-danger" onclick="eliminarElaboracion(<?= $elaboracion['id'] ?>)">Eliminar</button>
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