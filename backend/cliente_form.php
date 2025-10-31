<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$cliente = null;
$titulo = "Nuevo Cliente";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM clientes WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch();
    $titulo = "Editar Cliente";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitizar($_POST['nombre_apellidos']);
    $email = sanitizar($_POST['email']);
    $telefono = sanitizar($_POST['telefono']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    if ($cliente) {
        // Actualizar
        $sql = "UPDATE clientes SET nombre_apellidos = :nombre, email = :email, 
                telefono = :telefono, activo = :activo WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':telefono' => $telefono,
            ':activo' => $activo,
            ':id' => $cliente['id']
        ]);
    } else {
        // Insertar
        $sql = "INSERT INTO clientes (nombre_apellidos, email, telefono, activo) 
                VALUES (:nombre, :email, :telefono, :activo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':telefono' => $telefono,
            ':activo' => $activo
        ]);
    }
    
    header('Location: clientes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
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
            <h2><?= $titulo ?></h2>
            
            <form method="post">
                <div class="form-group">
                    <label for="nombre_apellidos">Nombre y Apellidos:</label>
                    <input type="text" id="nombre_apellidos" name="nombre_apellidos" 
                           value="<?= $cliente ? htmlspecialchars($cliente['nombre_apellidos']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" 
                           value="<?= $cliente ? htmlspecialchars($cliente['email']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono" 
                           value="<?= $cliente ? htmlspecialchars($cliente['telefono']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" <?= $cliente && $cliente['activo'] ? 'checked' : '' ?>>
                        Activo
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="clientes.php" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>