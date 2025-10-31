<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'pidepide');
define('DB_USER', 'pideusu');
define('DB_PASS', '8#AKbcM@mwnx0ec7');

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8mb4'");
    $pdo->exec("SET CHARACTER SET utf8mb4");
} catch(PDOException $e) {
    die("ERROR: No se pudo conectar. " . $e->getMessage());
}
?>