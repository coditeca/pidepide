<?php
session_start();
include '../../includes/config.php';
include '../../includes/funciones.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['activo' => false, 'message' => 'No autorizado']);
    exit;
}

$id_menu = (int)($_POST['id_menu'] ?? 0);

if ($id_menu <= 0) {
    echo json_encode(['activo' => false, 'message' => 'Menú no válido']);
    exit;
}

// Verificar horario con ajuste de +2 horas
$hora_actual = date('Y-m-d H:i:s', strtotime('+2 hours'));

try {
    $sql = "SELECT COUNT(*) as activo 
            FROM menus 
            WHERE id = :id_menu 
            AND fecha_hora_publicacion <= :hora_actual 
            AND fecha_hora_finalpedidos >= :hora_actual2";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_menu' => $id_menu,
        ':hora_actual' => $hora_actual,
        ':hora_actual2' => $hora_actual
    ]);
    
    $result = $stmt->fetch();
    $activo = $result['activo'] > 0;
    
    echo json_encode([
        'activo' => $activo,
        'hora_servidor' => date('Y-m-d H:i:s'),
        'hora_ajustada' => $hora_actual
    ]);
    
} catch (Exception $e) {
    echo json_encode(['activo' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>