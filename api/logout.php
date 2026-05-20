<?php
session_start();

// Guardar log antes de destruir sesión
if (isset($_SESSION['user'])) {
    require_once 'config.php';
    $usuario_id = $_SESSION['user']['id'];
    $usuario_nombre = $_SESSION['user']['nombre'];
    $usuario_rol = $_SESSION['user']['rol'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO logs (tipo, accion, detalle, usuario_id, usuario_nombre, usuario_rol, ip, user_agent) 
                           VALUES ('logout', 'Cierre de sesión', 'Usuario cerró sesión', ?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $usuario_id, $usuario_nombre, $usuario_rol, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

session_destroy();
echo json_encode(['success' => true]);
?>