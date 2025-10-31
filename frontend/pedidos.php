<?php
session_start();
include '../includes/config.php';
include '../includes/funciones.php';
include '../includes/auth.php';
verificarAuthFrontend();

// Obtener configuración del sistema
$sql_config = "SELECT nombre_servicio FROM configuracion LIMIT 1";
$stmt_config = $pdo->query($sql_config);
$config = $stmt_config->fetch();

// Obtener pedidos del cliente
$sql = "SELECT p.* FROM pedidos p 
        WHERE p.id_cliente = :cliente 
        ORDER BY p.fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':cliente' => $_SESSION['cliente_id']]);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - <?= htmlspecialchars($config['nombre_servicio'] ?? 'Sistema de Menús') ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <h1><?= htmlspecialchars($config['nombre_servicio'] ?? 'Sistema de Menús') ?></h1>
            <div class="user-info">
                <span>Hola, <?= htmlspecialchars($_SESSION['cliente_nombre']) ?></span>
                <a href="index.php" class="btn">Volver a Menús</a>
                <a href="../includes/logout.php" class="btn btn-danger">Salir</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 style="margin-bottom: 20px; color: #2c3e50;">Mis Pedidos</h2>
        
        <?php if(empty($pedidos)): ?>
            <div class="no-menus">
                <h2>No tienes pedidos realizados</h2>
                <p>Realiza tu primer pedido desde nuestra carta de menús.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Ver Menús</a>
            </div>
        <?php else: ?>
            <div class="pedidos-list">
                <?php foreach($pedidos as $pedido): ?>
                <div class="pedido-item">
                    <div class="pedido-info">
                        <h3>Pedido #<?= $pedido['id'] ?></h3>
                        <p class="pedido-fecha"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></p>
                        <span class="pedido-total"><?= number_format($pedido['total_pedido'], 2, ',', '.') ?> €</span>
                    </div>
                    <div class="pedido-estado <?= $pedido['pagado'] == 'si' ? 'pedido-pagado' : 'pedido-pendiente' ?>">
                        <?= $pedido['pagado'] == 'si' ? 'Pagado' : 'Pendiente' ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>