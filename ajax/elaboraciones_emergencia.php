<?php
// VERSIÓN DE EMERGENCIA - MÍNIMA Y ROBUSTA
header('Content-Type: application/json');

// Configuración directa como fallback
$host = 'localhost';
$dbname = 'pidepide';
$user = 'pideusu';
$pass = '8#AKbcM@mwnx0ec7';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    session_start();
    
    if (!isset($_SESSION['backend_logged_in'])) {
        throw new Exception('No autenticado');
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    
    if (empty($nombre)) throw new Exception('Nombre requerido');
    if ($precio <= 0) throw new Exception('Precio inválido');
    
    $stmt = $pdo->prepare("INSERT INTO elaboraciones (nombre, precio) VALUES (?, ?)");
    $stmt->execute([$nombre, $precio]);
    
    echo json_encode(['success' => true, 'message' => 'Creado con éxito']);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>