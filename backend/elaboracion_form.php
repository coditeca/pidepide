<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

$elaboracion = null;
$titulo = "Nueva Elaboración";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM elaboraciones WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $elaboracion = $stmt->fetch();
    $titulo = "Editar Elaboración";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitizar($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $alergenos = sanitizar($_POST['alergenos'] ?? '');
    
    if ($elaboracion) {
        // Actualizar
        $sql = "UPDATE elaboraciones SET nombre = :nombre, precio = :precio, alergenos = :alergenos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':alergenos' => $alergenos,
            ':id' => $elaboracion['id']
        ]);
    } else {
        // Insertar
        $sql = "INSERT INTO elaboraciones (nombre, precio, alergenos) VALUES (:nombre, :precio, :alergenos)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':precio' => $precio,
            ':alergenos' => $alergenos
        ]);
    }
    
    header('Location: elaboraciones.php');
    exit;
}

// Lista de alérgenos comunes para sugerencias
$alergenos_comunes = [
    'Gluten', 'Crustáceos', 'Huevos', 'Pescado', 'Cacahuetes', 'Soja',
    'Leche', 'Frutos de cáscara', 'Apio', 'Mostaza', 'Sésamo', 'Dióxido de azufre',
    'Altramuces', 'Moluscos', 'Ajo', 'Cebolla', 'Lactosa'
];
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
                <li><a href="elaboraciones.php" class="active">Elaboraciones</a></li>
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
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?= $elaboracion ? htmlspecialchars($elaboracion['nombre']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio (€):</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0"
                           value="<?= $elaboracion ? $elaboracion['precio'] : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="alergenos">Alérgenos:</label>
                    <textarea id="alergenos" name="alergenos" rows="3" 
                              placeholder="Ej: gluten, leche, huevos. Separar por comas."
                              style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?= $elaboracion ? htmlspecialchars($elaboracion['alergenos']) : '' ?></textarea>
                    <small style="color: #666;">
                        Alérgenos comunes: <?= implode(', ', $alergenos_comunes) ?>
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="elaboraciones.php" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>