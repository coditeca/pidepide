<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

// Obtener configuración actual
$sql = "SELECT * FROM configuracion LIMIT 1";
$stmt = $pdo->query($sql);
$config = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = sanitizar($_POST['usuario']);
    $email = sanitizar($_POST['email']);
    $nombre_servicio = sanitizar($_POST['nombre_servicio']);
    $condiciones_pedido = sanitizar($_POST['condiciones_pedido']);
    $password = $_POST['password'];
    
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE configuracion SET usuario = :usuario, email = :email, 
                nombre_servicio = :nombre_servicio, condiciones_pedido = :condiciones_pedido, 
                password = :password WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':email' => $email,
            ':nombre_servicio' => $nombre_servicio,
            ':condiciones_pedido' => $condiciones_pedido,
            ':password' => $password_hash,
            ':id' => $config['id']
        ]);
    } else {
        $sql = "UPDATE configuracion SET usuario = :usuario, email = :email, 
                nombre_servicio = :nombre_servicio, condiciones_pedido = :condiciones_pedido 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':email' => $email,
            ':nombre_servicio' => $nombre_servicio,
            ':condiciones_pedido' => $condiciones_pedido,
            ':id' => $config['id']
        ]);
    }
    
    $success = "Configuración actualizada correctamente";
    // Recargar datos
    $sql = "SELECT * FROM configuracion LIMIT 1";
    $stmt = $pdo->query($sql);
    $config = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="nav-container">
            <h1>Sistema de Menús</h1>
            <ul class="nav-menu">
                <li><a href="configuracion.php" class="active">Configuración</a></li>
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
            <h2>Configuración del Sistema</h2>
            <?php if(isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="usuario">Usuario Administrador:</label>
                    <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($config['usuario']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Administrador:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($config['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nombre_servicio">Nombre del Servicio (Frontend):</label>
                    <input type="text" id="nombre_servicio" name="nombre_servicio" 
                           value="<?= htmlspecialchars($config['nombre_servicio'] ?? 'Nuestros Menús') ?>" 
                           placeholder="Ej: Restaurante El Buen Sabor" required>
                    <small style="color: #6c757d;">Este nombre aparecerá en el header del frontend</small>
                </div>
                
                <div class="form-group">
                    <label for="condiciones_pedido">Condiciones del Pedido:</label>
                    <textarea id="condiciones_pedido" name="condiciones_pedido" rows="6" 
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                              placeholder="Ej: 
• Los pedidos deben realizarse dentro del horario establecido
• El pago se realiza al recoger el pedido
• Cancelaciones con 2 horas de antelación"><?= htmlspecialchars($config['condiciones_pedido'] ?? '') ?></textarea>
                    <small style="color: #6c757d;">Estas condiciones aparecerán al final del frontend</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    <a href="menus.php" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>