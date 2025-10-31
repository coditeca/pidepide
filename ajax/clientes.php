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
        case 'eliminar':
            $id = (int)$_POST['id'];
            
            $sql = "DELETE FROM clientes WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>