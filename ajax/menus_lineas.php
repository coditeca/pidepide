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
        case 'crear_linea':
            $id_menu = (int)$_POST['id_menu'];
            $id_elaboracion = (int)$_POST['id_elaboracion'];
            $precio = (float)$_POST['precio'];
            $cantidad = (int)$_POST['cantidad'];
            $pedido_maximo = !empty($_POST['pedido_maximo']) ? (int)$_POST['pedido_maximo'] : null;
            
            // Verificar que no existe ya esta elaboración en el menú
            $sql_check = "SELECT COUNT(*) as count FROM lineas_menu 
                         WHERE id_menu = :id_menu AND id_elaboracion = :id_elaboracion";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([
                ':id_menu' => $id_menu,
                ':id_elaboracion' => $id_elaboracion
            ]);
            $result = $stmt_check->fetch();
            
            if ($result['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Esta elaboración ya está en el menú']);
                exit;
            }
            
            // Insertar línea - el stock es igual a la cantidad disponible
            $sql = "INSERT INTO lineas_menu (id_menu, id_elaboracion, precio, stock, cantidad, pedido_maximo) 
                    VALUES (:id_menu, :id_elaboracion, :precio, :cantidad, :cantidad, :pedido_maximo)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_menu' => $id_menu,
                ':id_elaboracion' => $id_elaboracion,
                ':precio' => $precio,
                ':cantidad' => $cantidad,
                ':pedido_maximo' => $pedido_maximo
            ]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'eliminar_linea':
            $id = (int)$_POST['id'];
            
            $sql = "DELETE FROM lineas_menu WHERE id = :id";
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