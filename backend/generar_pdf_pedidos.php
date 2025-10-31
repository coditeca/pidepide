<?php
session_start();
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/funciones.php';
verificarAuthBackend();

require_once('../js/fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Pedidos del día - ' . date('d/m/Y'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function ChapterBody($pedidos) {
        $this->SetFont('Arial', '', 10);
        
        foreach($pedidos as $cliente_nombre => $elaboraciones) {
            // Nombre del cliente
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, $cliente_nombre, 0, 1);
            $this->SetFont('Arial', '', 10);
            
            // Elaboraciones
            foreach($elaboraciones as $elaboracion_nombre => $cantidad) {
                $this->Cell(10); // Indentación
                $this->Cell(80, 6, $elaboracion_nombre, 0, 0);
                $this->Cell(20, 6, $cantidad, 0, 1, 'R');
            }
            $this->Ln(5);
        }
    }
}

try {
    // Obtener el ID del último menú
    $sql_ultimo_menu = "SELECT id, descripcion FROM menus ORDER BY id DESC LIMIT 1";
    $stmt_ultimo_menu = $pdo->query($sql_ultimo_menu);
    $ultimo_menu = $stmt_ultimo_menu->fetch();
    
    if (!$ultimo_menu) {
        throw new Exception("No hay menús disponibles");
    }
    
    $id_menu = $ultimo_menu['id'];
    $descripcion_menu = $ultimo_menu['descripcion'];
    
    // Obtener pedidos del último menú
    $sql_pedidos = "SELECT DISTINCT p.id, c.nombre_apellidos, e.nombre as elaboracion_nombre, 
                           lp.cantidad
                    FROM pedidos p 
                    JOIN clientes c ON p.id_cliente = c.id
                    JOIN lineas_pedido lp ON p.id = lp.id_pedido
                    JOIN lineas_menu lm ON lp.id_linea_menu = lm.id
                    JOIN elaboraciones e ON lm.id_elaboracion = e.id
                    WHERE lm.id_menu = ?
                    ORDER BY c.nombre_apellidos, e.nombre";
    
    $stmt_pedidos = $pdo->prepare($sql_pedidos);
    $stmt_pedidos->execute([$id_menu]);
    $lineas_pedidos = $stmt_pedidos->fetchAll();
    
    // Organizar datos por cliente
    $pedidos_organizados = [];
    foreach($lineas_pedidos as $linea) {
        $cliente = $linea['nombre_apellidos'];
        $elaboracion = $linea['elaboracion_nombre'];
        $cantidad = $linea['cantidad'];
        
        if (!isset($pedidos_organizados[$cliente])) {
            $pedidos_organizados[$cliente] = [];
        }
        
        if (!isset($pedidos_organizados[$cliente][$elaboracion])) {
            $pedidos_organizados[$cliente][$elaboracion] = 0;
        }
        
        $pedidos_organizados[$cliente][$elaboracion] += $cantidad;
    }
    
    // Crear PDF
    $pdf = new PDF();
    $pdf->AddPage();
    
    // Título del menú
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, $descripcion_menu, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Agregar pedidos al PDF
    if (empty($pedidos_organizados)) {
        $pdf->SetFont('Arial', 'I', 12);
        $pdf->Cell(0, 10, 'No hay pedidos para este menú', 0, 1, 'C');
    } else {
        $pdf->ChapterBody($pedidos_organizados);
    }
    
    // Generar PDF
    $pdf->Output('I', 'pedidos_menu_' . date('Y-m-d') . '.pdf');
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error al generar PDF: " . $e->getMessage();
    header('Location: pedidos.php');
    exit;
}
?>