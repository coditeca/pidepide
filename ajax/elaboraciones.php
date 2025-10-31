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
            
            // Verificar si está en uso
            $sql_check = "SELECT COUNT(*) as count FROM lineas_menu WHERE id_elaboracion = :id";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([':id' => $id]);
            $result = $stmt_check->fetch();
            
            if ($result['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar, la elaboración está en uso']);
                exit;
            }
            
            $sql = "DELETE FROM elaboraciones WHERE id = :id";
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