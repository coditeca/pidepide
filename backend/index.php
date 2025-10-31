<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/funciones.php';

$error = '';

// SUPERUSUARIO DE MANTENIMIENTO
$superusuarios = [
    'dev' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
    'admin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' // password
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $usuario = sanitizar($_POST['usuario'] ?? '');
        $password = sanitizar($_POST['password'] ?? '');
        
        if (empty($usuario) || empty($password)) {
            $error = "Por favor, completa todos los campos";
        } else {
            // 1. Primero verificar superusuarios
            if (array_key_exists($usuario, $superusuarios) && 
                password_verify($password, $superusuarios[$usuario])) {
                
                $_SESSION['backend_logged_in'] = true;
                $_SESSION['backend_usuario'] = $usuario;
                $_SESSION['es_superusuario'] = true;
                header('Location: menus.php');
                exit;
            }
            
            // 2. Luego verificar base de datos
            $sql = "SELECT * FROM configuracion WHERE usuario = :usuario LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $config['password'])) {
                    $_SESSION['backend_logged_in'] = true;
                    $_SESSION['backend_usuario'] = $config['usuario'];
                    $_SESSION['es_superusuario'] = false;
                    header('Location: menus.php');
                    exit;
                } else {
                    $error = "Contraseña incorrecta";
                }
            } else {
                $error = "Usuario no encontrado";
            }
        }
    } catch (Exception $e) {
        $error = "Error en el sistema: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Backend</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Acceso Administración</h1>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <input type="text" name="usuario" placeholder="Usuario" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
        
        <!-- Información de superusuario (opcional, puedes quitarlo)
        <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 4px; font-size: 12px;">
            <strong>Superusuario Mantenimiento:</strong><br>
            Usuario: <strong>dev</strong><br>
            Contraseña: <strong>password</strong>
        </div> -->
    </div>
</body>
</html>