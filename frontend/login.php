<?php
session_start();
include '../includes/config.php';
include '../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizar($_POST['email']);
    
    $sql = "SELECT * FROM clientes WHERE email = :email AND activo = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    
    if ($stmt->rowCount() == 1) {
        $cliente = $stmt->fetch();
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nombre'] = $cliente['nombre_apellidos'];
        $_SESSION['cliente_email'] = $cliente['email'];
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Email no encontrado o cliente inactivo";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Clientes</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Acceso Clientes</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Entrar</button>
        </form>
        <p style="text-align: center; margin-top: 20px; color: #7f8c8d;">
            Solo necesitas tu email para acceder
        </p>
    </div>
</body>
</html>