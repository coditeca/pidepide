<?php
session_start();
include '../../includes/config.php';
include '../../includes/funciones.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

// Función para verificar horario (reutilizable)
function verificarHorarioMenu($id_menu) {
    global $pdo;
    $hora_actual = date('Y-m-d H:i:s', strtotime('+2 hours')); // Ajuste horario España
    
    $sql = "SELECT m.descripcion, m.fecha_hora_publicacion, m.fecha_hora_finalpedidos 
            FROM menus m 
            WHERE m.id = :id_menu 
            AND m.fecha_hora_publicacion <= :hora_actual 
            AND m.fecha_hora_finalpedidos >= :hora_actual2";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_menu' => $id_menu,
        ':hora_actual' => $hora_actual,
        ':hora_actual2' => $hora_actual
    ]);
    
    $menu = $stmt->fetch();
    
    if ($menu) {
        return [
            'activo' => true,
            'menu' => $menu
        ];
    } else {
        // Obtener información del menú para el mensaje de error
        $sql_info = "SELECT descripcion, fecha_hora_publicacion, fecha_hora_finalpedidos 
                     FROM menus WHERE id = :id_menu";
        $stmt_info = $pdo->prepare($sql_info);
        $stmt_info->execute([':id_menu' => $id_menu]);
        $menu_info = $stmt_info->fetch();
        
        return [
            'activo' => false,
            'menu' => $menu_info
        ];
    }
}

// Función auxiliar para obtener stock actual
function obtenerStockActual($id_linea_menu) {
    global $pdo;
    $sql = "SELECT lm.stock - COALESCE(SUM(lp.cantidad), 0) as stock_actual 
            FROM lineas_menu lm 
            LEFT JOIN lineas_pedido lp ON lm.id = lp.id_linea_menu 
            WHERE lm.id = :id_linea_menu 
            GROUP BY lm.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_linea_menu' => $id_linea_menu]);
    $result = $stmt->fetch();
    return $result ? $result['stock_actual'] : 0;
}

// Función auxiliar para obtener nombre de elaboración
function obtenerNombreElaboracion($id_linea_menu) {
    global $pdo;
    $sql = "SELECT e.nombre 
            FROM lineas_menu lm 
            JOIN elaboraciones e ON lm.id_elaboracion = e.id 
            WHERE lm.id = :id_linea_menu";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_linea_menu' => $id_linea_menu]);
    $result = $stmt->fetch();
    return $result ? $result['nombre'] : 'Elaboración';
}

// Función para formatear fecha para mensajes
function formatoFechaMensaje($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

try {
    switch($accion) {
        case 'crear':
            $id_menu = (int)$_POST['id_menu'];
            $lineas = json_decode($_POST['lineas'], true);
            $total = (float)$_POST['total'];
            $id_cliente = $_SESSION['cliente_id'];
            
            // 1. Verificar horario en tiempo real
            $verificacion_horario = verificarHorarioMenu($id_menu);
            if (!$verificacion_horario['activo']) {
                $menu_info = $verificacion_horario['menu'];
                if ($menu_info) {
                    $mensaje_error = "❌ EL PEDIDO NO SE HA REALIZADO\n\n";
                    $mensaje_error .= "El período de pedidos para '{$menu_info['descripcion']}' ha finalizado.\n\n";
                    $mensaje_error .= "📅 Horario de pedidos:\n";
                    $mensaje_error .= "• Inicio: " . formatoFechaMensaje($menu_info['fecha_hora_publicacion']) . "\n";
                    $mensaje_error .= "• Fin: " . formatoFechaMensaje($menu_info['fecha_hora_finalpedidos']) . "\n\n";
                    $mensaje_error .= "Por favor, actualiza la página para ver los menús disponibles actualmente.";
                } else {
                    $mensaje_error = "❌ EL PEDIDO NO SE HA REALIZADO\n\nEl menú seleccionado ya no está disponible.";
                }
                echo json_encode(['success' => false, 'message' => $mensaje_error]);
                exit;
            }
            
            // 2. Verificar que no existe ya un pedido para este menú
            $sql_check = "SELECT p.id FROM pedidos p 
                         JOIN lineas_pedido lp ON p.id = lp.id_pedido 
                         JOIN lineas_menu lm ON lp.id_linea_menu = lm.id 
                         WHERE p.id_cliente = :cliente_id AND lm.id_menu = :menu_id";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([
                ':cliente_id' => $id_cliente,
                ':menu_id' => $id_menu
            ]);
            
            if ($stmt_check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => '❌ EL PEDIDO NO SE HA REALIZADO\n\nYa tienes un pedido activo para este menú.']);
                exit;
            }
            
            // 3. Verificar que el menú existe (doble verificación)
            $sql_menu = "SELECT * FROM menus WHERE id = :id_menu";
            $stmt_menu = $pdo->prepare($sql_menu);
            $stmt_menu->execute([':id_menu' => $id_menu]);
            
            if ($stmt_menu->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => '❌ EL PEDIDO NO SE HA REALIZADO\n\nEl menú seleccionado ya no existe.']);
                exit;
            }
            
            // 4. Verificar stock y crear líneas de pedido
            $pdo->beginTransaction();
            
            // Crear pedido
            $sql_pedido = "INSERT INTO pedidos (id_cliente, total_pedido, forma_pago) 
                          VALUES (:cliente, :total, 'Online')";
            $stmt_pedido = $pdo->prepare($sql_pedido);
            $stmt_pedido->execute([
                ':cliente' => $id_cliente,
                ':total' => $total
            ]);
            
            $id_pedido = $pdo->lastInsertId();
            
            // Crear líneas de pedido
            foreach ($lineas as $linea) {
                $id_linea_menu = (int)$linea['id_linea_menu'];
                $cantidad = (int)$linea['cantidad'];
                $precio = (float)$linea['precio'];
                
                // Verificar stock disponible
                $stock_actual = obtenerStockActual($id_linea_menu);
                
                if ($stock_actual < $cantidad) {
                    $nombre_elaboracion = obtenerNombreElaboracion($id_linea_menu);
                    throw new Exception("❌ EL PEDIDO NO SE HA REALIZADO\n\nStock insuficiente para: {$nombre_elaboracion}\nDisponible: {$stock_actual} unidades\nSolicitado: {$cantidad} unidades");
                }
                
                // Verificar que la línea pertenece al menú
                $sql_check_linea = "SELECT id_menu FROM lineas_menu WHERE id = :id_linea_menu";
                $stmt_check_linea = $pdo->prepare($sql_check_linea);
                $stmt_check_linea->execute([':id_linea_menu' => $id_linea_menu]);
                $linea_menu = $stmt_check_linea->fetch();
                
                if (!$linea_menu || $linea_menu['id_menu'] != $id_menu) {
                    throw new Exception("❌ EL PEDIDO NO SE HA REALIZADO\n\nError: La elaboración seleccionada ya no está disponible en este menú.");
                }
                
                // Insertar línea de pedido
                $sql_linea = "INSERT INTO lineas_pedido (id_pedido, id_linea_menu, cantidad, precio) 
                             VALUES (:pedido, :linea_menu, :cantidad, :precio)";
                $stmt_linea = $pdo->prepare($sql_linea);
                $stmt_linea->execute([
                    ':pedido' => $id_pedido,
                    ':linea_menu' => $id_linea_menu,
                    ':cantidad' => $cantidad,
                    ':precio' => $precio
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'id_pedido' => $id_pedido,
                'message' => '✅ PEDIDO REALIZADO CON ÉXITO\n\nTu pedido ha sido registrado correctamente.\nNúmero de pedido: ' . $id_pedido
            ]);
            break;
            
        case 'modificar':
            $id_pedido = (int)$_POST['id_pedido'];
            $id_menu = (int)$_POST['id_menu'];
            $lineas = json_decode($_POST['lineas'], true);
            $total = (float)$_POST['total'];
            $id_cliente = $_SESSION['cliente_id'];
            
            // 1. Verificar horario en tiempo real
            $verificacion_horario = verificarHorarioMenu($id_menu);
            if (!$verificacion_horario['activo']) {
                $menu_info = $verificacion_horario['menu'];
                if ($menu_info) {
                    $mensaje_error = "❌ EL PEDIDO NO SE HA MODIFICADO\n\n";
                    $mensaje_error .= "El período de modificación para '{$menu_info['descripcion']}' ha finalizado.\n\n";
                    $mensaje_error .= "📅 Horario de modificación:\n";
                    $mensaje_error .= "• Inicio: " . formatoFechaMensaje($menu_info['fecha_hora_publicacion']) . "\n";
                    $mensaje_error .= "• Fin: " . formatoFechaMensaje($menu_info['fecha_hora_finalpedidos']) . "\n\n";
                    $mensaje_error .= "Ya no puedes modificar este pedido.";
                } else {
                    $mensaje_error = "❌ EL PEDIDO NO SE HA MODIFICADO\n\nEl período de modificación ha finalizado.";
                }
                echo json_encode(['success' => false, 'message' => $mensaje_error]);
                exit;
            }
            
            // 2. Verificar que el pedido pertenece al cliente
            $sql_check_pedido = "SELECT id FROM pedidos WHERE id = :pedido_id AND id_cliente = :cliente_id";
            $stmt_check_pedido = $pdo->prepare($sql_check_pedido);
            $stmt_check_pedido->execute([
                ':pedido_id' => $id_pedido,
                ':cliente_id' => $id_cliente
            ]);
            
            if ($stmt_check_pedido->rowCount() == 0) {
                echo json_encode(['success' => false, 'message' => '❌ EL PEDIDO NO SE HA MODIFICADO\n\nPedido no encontrado.']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            // 3. Eliminar líneas antiguas del pedido
            $sql_delete_lineas = "DELETE FROM lineas_pedido WHERE id_pedido = :pedido_id";
            $stmt_delete_lineas = $pdo->prepare($sql_delete_lineas);
            $stmt_delete_lineas->execute([':pedido_id' => $id_pedido]);
            
            // 4. Si no hay líneas nuevas, eliminar el pedido
            if (empty($lineas)) {
                $sql_delete_pedido = "DELETE FROM pedidos WHERE id = :pedido_id";
                $stmt_delete_pedido = $pdo->prepare($sql_delete_pedido);
                $stmt_delete_pedido->execute([':pedido_id' => $id_pedido]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => '✅ PEDIDO CANCELADO\n\nTu pedido ha sido cancelado correctamente.']);
                exit;
            }
            
            // 5. Crear nuevas líneas de pedido
            foreach ($lineas as $linea) {
                $id_linea_menu = (int)$linea['id_linea_menu'];
                $cantidad = (int)$linea['cantidad'];
                $precio = (float)$linea['precio'];
                
                // Verificar stock disponible
                $stock_actual = obtenerStockActual($id_linea_menu);
                
                if ($stock_actual < $cantidad) {
                    $nombre_elaboracion = obtenerNombreElaboracion($id_linea_menu);
                    throw new Exception("❌ EL PEDIDO NO SE HA MODIFICADO\n\nStock insuficiente para: {$nombre_elaboracion}\nDisponible: {$stock_actual} unidades\nSolicitado: {$cantidad} unidades");
                }
                
                // Verificar que la línea pertenece al menú
                $sql_check_linea = "SELECT id_menu FROM lineas_menu WHERE id = :id_linea_menu";
                $stmt_check_linea = $pdo->prepare($sql_check_linea);
                $stmt_check_linea->execute([':id_linea_menu' => $id_linea_menu]);
                $linea_menu = $stmt_check_linea->fetch();
                
                if (!$linea_menu || $linea_menu['id_menu'] != $id_menu) {
                    throw new Exception("❌ EL PEDIDO NO SE HA MODIFICADO\n\nError: La elaboración seleccionada ya no está disponible en este menú.");
                }
                
                // Insertar línea de pedido
                $sql_linea = "INSERT INTO lineas_pedido (id_pedido, id_linea_menu, cantidad, precio) 
                             VALUES (:pedido, :linea_menu, :cantidad, :precio)";
                $stmt_linea = $pdo->prepare($sql_linea);
                $stmt_linea->execute([
                    ':pedido' => $id_pedido,
                    ':linea_menu' => $id_linea_menu,
                    ':cantidad' => $cantidad,
                    ':precio' => $precio
                ]);
            }
            
            // 6. Actualizar total del pedido
            $sql_update_pedido = "UPDATE pedidos SET total_pedido = :total WHERE id = :pedido_id";
            $stmt_update_pedido = $pdo->prepare($sql_update_pedido);
            $stmt_update_pedido->execute([
                ':total' => $total,
                ':pedido_id' => $id_pedido
            ]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => '✅ PEDIDO MODIFICADO\n\nTu pedido ha sido actualizado correctamente.\nNuevo total: ' . number_format($total, 2, ',', '.') . ' €'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '❌ ERROR\n\nAcción no válida.']);
    }
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error PDO en pedidos.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '❌ ERROR\n\nError del sistema: ' . $e->getMessage()]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en pedidos.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>