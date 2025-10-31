<?php
// Funciones auxiliares
function sanitizar($data) {
    if (!isset($data)) return '';
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatoFecha($fecha) {
    if (empty($fecha)) return '';
    return date('d/m/Y H:i', strtotime($fecha));
}

function formatoMoneda($cantidad) {
    if (!is_numeric($cantidad)) return '0,00 €';
    return number_format($cantidad, 2, ',', '.') . ' €';
}

// Obtener precio original de la elaboración
function obtenerPrecioElaboracion($id_elaboracion) {
    global $pdo;
    $sql = "SELECT precio FROM elaboraciones WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_elaboracion]);
    $result = $stmt->fetch();
    return $result ? $result['precio'] : 0;
}

// Calcular pedidos realizados para una línea
function calcularPedidosLinea($id_linea_menu) {
    global $pdo;
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total 
            FROM lineas_pedido 
            WHERE id_linea_menu = :id_linea_menu";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_linea_menu' => $id_linea_menu]);
    $result = $stmt->fetch();
    return $result['total'];
}


// Verificar si un menú está activo en este momento
function menuEstaActivo($id_menu) {
    global $pdo;
    
    $hora_actual = date('Y-m-d H:i:s', strtotime('+2 hours')); // Ajuste horario
    
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
    return $result['activo'] > 0;
}

?>