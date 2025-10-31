<?php
session_start();
include '../includes/config.php';
include '../includes/funciones.php';

header('Content-Type: application/json');

if (!isset($_SESSION['backend_logged_in']) || $_SESSION['backend_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch($accion) {
        case 'actualizar':
            $usuario = sanitizar($_POST['usuario']);
            $email = sanitizar($_POST['email']);
            $password = $_POST['password'];
            
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE configuracion SET usuario = :usuario, email = :email, password = :password WHERE id = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':usuario' => $usuario,
                    ':email' => $email,
                    ':password' => $password_hash
                ]);
            } else {
                $sql = "UPDATE configuracion SET usuario = :usuario, email = :email WHERE id = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':usuario' => $usuario,
                    ':email' => $email
                ]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>