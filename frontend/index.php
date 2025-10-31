<?php
session_start();
include '../includes/config.php';
include '../includes/funciones.php';

// Redirigir a login si no está autenticado
if (!isset($_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener configuración del sistema
$sql_config = "SELECT nombre_servicio, condiciones_pedido FROM configuracion LIMIT 1";
$stmt_config = $pdo->query($sql_config);
$config = $stmt_config->fetch();

// Obtener menús activos - AJUSTAR HORA +2 HORAS
$hora_servidor = date('Y-m-d H:i:s');
$hora_espana = date('Y-m-d H:i:s', strtotime('+2 hours')); // Ajuste para España

$sql = "SELECT m.* FROM menus m 
        WHERE m.fecha_hora_publicacion <= :hora_actual 
        AND m.fecha_hora_finalpedidos >= :hora_actual2
        ORDER BY m.fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':hora_actual' => $hora_espana,
    ':hora_actual2' => $hora_espana
]);
$menus = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['nombre_servicio'] ?? 'Nuestros Menús') ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <h1><?= htmlspecialchars($config['nombre_servicio'] ?? 'Nuestros Menús') ?></h1>
            <div class="user-info">
                <span>Hola, <?= htmlspecialchars($_SESSION['cliente_nombre']) ?></span>
                <a href="pedidos.php" class="btn">Mis Pedidos</a>
                <a href="../includes/logout.php" class="btn btn-danger">Salir</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if(empty($menus)): ?>
            <div class="no-menus">
                <h2>No hay menús disponibles en este momento</h2>
                <p>Vuelve más tarde para ver nuestros menús del día.</p>
            </div>
        <?php else: ?>
            <?php foreach($menus as $menu): 
                // Verificar si el cliente ya tiene un pedido para este menú
                $sql_pedido = "SELECT p.id, p.total_pedido, p.pagado 
                              FROM pedidos p 
                              WHERE p.id_cliente = :cliente_id 
                              AND EXISTS (
                                  SELECT 1 FROM lineas_pedido lp 
                                  JOIN lineas_menu lm ON lp.id_linea_menu = lm.id 
                                  WHERE lp.id_pedido = p.id AND lm.id_menu = :menu_id
                              )";
                $stmt_pedido = $pdo->prepare($sql_pedido);
                $stmt_pedido->execute([
                    ':cliente_id' => $_SESSION['cliente_id'],
                    ':menu_id' => $menu['id']
                ]);
                $pedido_existente = $stmt_pedido->fetch();
                
                // Obtener líneas del pedido existente si hay
                $lineas_pedido = [];
                if ($pedido_existente) {
                    $sql_lineas_pedido = "SELECT lp.*, lm.id as id_linea_menu, e.nombre as elaboracion_nombre 
                                         FROM lineas_pedido lp 
                                         JOIN lineas_menu lm ON lp.id_linea_menu = lm.id 
                                         JOIN elaboraciones e ON lm.id_elaboracion = e.id 
                                         WHERE lp.id_pedido = :pedido_id";
                    $stmt_lineas_pedido = $pdo->prepare($sql_lineas_pedido);
                    $stmt_lineas_pedido->execute([':pedido_id' => $pedido_existente['id']]);
                    $lineas_pedido = $stmt_lineas_pedido->fetchAll();
                }
            ?>
            <div class="menu-card" data-menu-id="<?= $menu['id'] ?>">
                <div class="menu-header">
                    <h2><?= htmlspecialchars($menu['descripcion']) ?></h2>
                    <span class="menu-date"><?= date('d/m/Y', strtotime($menu['fecha'])) ?></span>
                    <?php if($pedido_existente): ?>
                        <span class="pedido-badge" style="background: #28a745; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                            Pedido Realizado
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php
                // Obtener líneas del menú
                $sql_lineas = "SELECT lm.*, e.nombre as elaboracion_nombre, e.alergenos 
              FROM lineas_menu lm 
              JOIN elaboraciones e ON lm.id_elaboracion = e.id 
              WHERE lm.id_menu = :id_menu 
              AND (lm.stock - COALESCE((SELECT SUM(lp.cantidad) FROM lineas_pedido lp WHERE lp.id_linea_menu = lm.id), 0)) > 0
              ORDER BY e.nombre";
                $stmt_lineas = $pdo->prepare($sql_lineas);
                $stmt_lineas->execute([':id_menu' => $menu['id']]);
                $lineas = $stmt_lineas->fetchAll();
                ?>
                
<!-- En la sección de items del menú, modificar: -->
<div class="menu-items">
    <?php foreach($lineas as $linea): 
        $stock_actual = $linea['stock'] - calcularPedidosLinea($linea['id']);
        $cantidad_pedido = 0;
        
        // Buscar cantidad en pedido existente
        if ($pedido_existente) {
            foreach($lineas_pedido as $linea_pedido) {
                if ($linea_pedido['id_linea_menu'] == $linea['id']) {
                    $cantidad_pedido = $linea_pedido['cantidad'];
                    break;
                }
            }
        }
    ?>
    <div class="menu-item" data-linea-id="<?= $linea['id'] ?>">
        <div class="item-info">
            <h3><?= htmlspecialchars($linea['elaboracion_nombre']) ?></h3>
            <span class="price"><?= number_format($linea['precio'], 2, ',', '.') ?> €</span>
            <span class="stock">Disponible: <?= $stock_actual ?></span>
            
            <!-- MOSTRAR ALÉRGENOS -->
            <?php if(!empty($linea['alergenos'])): ?>
            <div class="alergenos" style="margin-top: 5px;">
                <small style="color: #e74c3c; font-size: 12px;">
                    🚨 Alérgenos: <?= htmlspecialchars($linea['alergenos']) ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
        <div class="item-controls">
            <button class="btn-quantity minus" data-menu="<?= $menu['id'] ?>" data-linea="<?= $linea['id'] ?>">-</button>
            <input type="number" class="quantity" id="qty_<?= $menu['id'] ?>_<?= $linea['id'] ?>" 
                   value="<?= $cantidad_pedido ?>" min="0" 
                   max="<?= min($stock_actual, $linea['pedido_maximo'] ?? 99) ?>">
            <button class="btn-quantity plus" data-menu="<?= $menu['id'] ?>" data-linea="<?= $linea['id'] ?>">+</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
                
                <div class="menu-footer">
                    <?php if($pedido_existente): ?>
                        <button class="btn btn-primary btn-modificar" data-menu="<?= $menu['id'] ?>" data-pedido="<?= $pedido_existente['id'] ?>">
                            Modificar Pedido
                        </button>
                        <a href="cancelar_pedido.php?id=<?= $pedido_existente['id'] ?>" class="btn btn-danger" 
                           onclick="return confirm('¿Estás seguro de que quieres cancelar este pedido?')">
                            Cancelar Pedido
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary btn-pedir" data-menu="<?= $menu['id'] ?>">Realizar Pedido</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Mostrar condiciones del pedido si existen -->
            <?php if(!empty($config['condiciones_pedido'])): ?>
            <div class="condiciones-pedido" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3 style="color: #2c3e50; margin-bottom: 15px;">Condiciones del Pedido</h3>
                <div style="white-space: pre-line; line-height: 1.6; color: #555;">
                    <?= htmlspecialchars($config['condiciones_pedido']) ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="../js/frontend.js"></script>
</body>
</html>