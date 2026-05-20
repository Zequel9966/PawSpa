<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'productos':
        $result = $conn->query("SELECT * FROM productos WHERE activo = 1");
        $productos = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'productos' => $productos]);
        break;
        
    case 'clientes':
        $result = $conn->query("SELECT id, nombre, email, telefono FROM usuarios WHERE rol = 'client'");
        $clientes = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'clientes' => $clientes]);
        break;
        
    case 'citas':
        $result = $conn->query("SELECT c.*, u.nombre as cliente_nombre FROM citas c JOIN usuarios u ON c.cliente_id = u.id ORDER BY c.fecha DESC LIMIT 20");
        $citas = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'citas' => $citas]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>