<?php
require_once 'config.php';

header('Content-Type: application/json');

// Recibir datos del log
$data = json_decode(file_get_contents('php://input'), true);

$tipo = $data['tipo'] ?? 'info';
$accion = $data['accion'] ?? '';
$detalle = $data['detalle'] ?? '';
$usuario_id = $_SESSION['user']['id'] ?? null;
$usuario_nombre = $_SESSION['user']['nombre'] ?? 'Sistema';
$usuario_rol = $_SESSION['user']['rol'] ?? 'system';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$stmt = $conn->prepare("INSERT INTO logs (tipo, accion, detalle, usuario_id, usuario_nombre, usuario_rol, ip, user_agent) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssissss", $tipo, $accion, $detalle, $usuario_id, $usuario_nombre, $usuario_rol, $ip, $user_agent);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
?>