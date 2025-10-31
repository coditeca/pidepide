<?php
session_start();
include '../includes/config.php';
include '../includes/funciones.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$pedido_id = (int)$_GET['id'];

// Verificar que el pedido pertenece al cliente
$sql_check = "SELECT id FROM pedidos WHERE id = :pedido_id AND id_cliente = :cliente_id";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute([
    ':pedido_id' => $pedido_id,
    ':cliente_id' => $_SESSION['cliente_id']
]);

if ($stmt_check->rowCount() > 0) {
    // Eliminar pedido
    $sql_delete = "DELETE FROM pedidos WHERE id = :pedido_id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([':pedido_id' => $pedido_id]);
    
    $_SESSION['mensaje'] = "Pedido cancelado correctamente";
} else {
    $_SESSION['error'] = "No se pudo cancelar el pedido";
}

header('Location: index.php');
exit;
?>