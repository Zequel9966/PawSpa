<?php
session_start();

// Destruir sesión PHP
session_destroy();

// Limpiar cookie JWT si existe
if (isset($_COOKIE['jwt_token'])) {
    setcookie('jwt_token', '', time() - 3600, '/');
}

echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente']);