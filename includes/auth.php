<?php
session_start();

// Verificar autenticación del backend
function verificarAuthBackend() {
    if (!isset($_SESSION['backend_logged_in']) || $_SESSION['backend_logged_in'] !== true) {
        header('Location: /backend/index.php');
        exit;
    }
}

// Verificar autenticación del frontend
function verificarAuthFrontend() {
    if (!isset($_SESSION['cliente_id'])) {
        header('Location: /frontend/login.php');
        exit;
    }
}
?>