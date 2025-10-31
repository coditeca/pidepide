<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$menu = null;
$lineas_menu = [];
$titulo = "Nuevo Menú";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM menus WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $menu = $stmt->fetch();
    $titulo = "Editor Menú";
    
    // Obtener líneas del menú
    $sql_lineas = "SELECT lm.*, e.nombre as elaboracion_nombre 
                  FROM lineas_menu lm 
                  JOIN elaboraciones e ON lm.id_elaboracion = e.id 
                  WHERE lm.id_menu = :id_menu 
                  ORDER BY lm.id";
    $stmt_lineas = $pdo->prepare($sql_lineas);
    $stmt_lineas->execute([':id_menu' => $id]);
    $lineas_menu = $stmt_lineas->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descripcion = sanitizar($_POST['descripcion']);
    $fecha = sanitizar($_POST['fecha']);
    $fecha_publicacion = sanitizar($_POST['fecha_publicacion']);
    $fecha_final = sanitizar($_POST['fecha_final']);
    
    if ($menu) {
        // Actualizar menú
        $sql = "UPDATE menus SET descripcion = :descripcion, fecha = :fecha, 
                fecha_hora_publicacion = :publicacion, fecha_hora_finalpedidos = :final 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descripcion' => $descripcion,
            ':fecha' => $fecha,
            ':publicacion' => $fecha_publicacion,
            ':final' => $fecha_final,
            ':id' => $menu['id']
        ]);
        $menu_id = $menu['id'];
    } else {
        // Insertar nuevo menú
        $sql = "INSERT INTO menus (descripcion, fecha, fecha_hora_publicacion, fecha_hora_finalpedidos) 
                VALUES (:descripcion, :fecha, :publicacion, :final)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descripcion' => $descripcion,
            ':fecha' => $fecha,
            ':publicacion' => $fecha_publicacion,
            ':final' => $fecha_final
        ]);
        $menu_id = $pdo->lastInsertId();
    }
    
    header('Location: menu_detalle.php?id=' . $menu_id);
    exit;
}

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
                    <label for="descripcion">Descripción:</label>
                    <input type="text" id="descripcion" name="descripcion" 
                           value="<?= $menu ? htmlspecialchars($menu['descripcion']) : '' ?>" 
                           placeholder="MENU SEMANAL" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha">Fecha del Menú:</label>
                    <input type="date" id="fecha" name="fecha" 
                           value="<?= $menu ? $menu['fecha'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_publicacion">Fecha y Hora de Publicación:</label>
                    <input type="datetime-local" id="fecha_publicacion" name="fecha_publicacion" 
                           value="<?= $menu ? date('Y-m-d\TH:i', strtotime($menu['fecha_hora_publicacion'])) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_final">Fecha y Hora Final de Pedidos:</label>
                    <input type="datetime-local" id="fecha_final" name="fecha_final" 
                           value="<?= $menu ? date('Y-m-d\TH:i', strtotime($menu['fecha_hora_finalpedidos'])) : '' ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="menus.php" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>