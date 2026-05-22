<?php
// ============================================
// mascotas.php - API para gestión de mascotas
// ============================================

require_once 'config.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Función para guardar logs
function guardarLogMascota($conn, $tipo, $accion, $detalle) {
    $usuario_id = $_SESSION['user']['id'] ?? null;
    $usuario_nombre = $_SESSION['user']['nombre'] ?? 'Sistema';
    $usuario_rol = $_SESSION['user']['rol'] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO logs (tipo, accion, detalle, usuario_id, usuario_nombre, usuario_rol, ip, user_agent) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissss", $tipo, $accion, $detalle, $usuario_id, $usuario_nombre, $usuario_rol, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// GET - Obtener mascotas de un cliente
// ============================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'por_cliente') {
    $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
    
    if ($cliente_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM mascotas WHERE cliente_id = ? ORDER BY nombre");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mascotas = [];
    while ($row = $result->fetch_assoc()) {
        $mascotas[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'mascotas' => $mascotas]);
    exit;
}

// ============================================
// GET - Obtener todas las mascotas
// ============================================
if ($method === 'GET' && !isset($_GET['action'])) {
    $sql = "SELECT m.*, u.nombre as duenio_nombre 
            FROM mascotas m 
            JOIN usuarios u ON m.cliente_id = u.id 
            WHERE u.rol = 'client' 
            ORDER BY m.id DESC";
    $result = $conn->query($sql);
    
    $mascotas = [];
    while ($row = $result->fetch_assoc()) {
        $mascotas[] = $row;
    }
    
    echo json_encode(['success' => true, 'mascotas' => $mascotas]);
    exit;
}

// ============================================
// POST - Crear nueva mascota
// ============================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $cliente_id = intval($data['cliente_id'] ?? 0);
    $nombre = trim($data['nombre'] ?? '');
    $especie = $data['especie'] ?? 'perro';
    $raza = trim($data['raza'] ?? '');
    $tamanio = $data['tamanio'] ?? 'mediano';
    $edad = intval($data['edad'] ?? 0);
    $peso = floatval($data['peso'] ?? 0);
    $alergias = trim($data['alergias'] ?? '');
    $temperamento = $data['temperamento'] ?? 'tranquilo';
    $foto_url = $data['foto_url'] ?? null;
    
    if ($cliente_id <= 0 || empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'Cliente ID y nombre son requeridos']);
        exit;
    }
    
    // Verificar que el cliente existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'client'");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO mascotas (cliente_id, nombre, especie, raza, tamanio, edad, peso, alergias, temperamento, foto_url) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiisss", $cliente_id, $nombre, $especie, $raza, $tamanio, $edad, $peso, $alergias, $temperamento, $foto_url);
    
    if ($stmt->execute()) {
        $mascota_id = $conn->insert_id;
        guardarLogMascota($conn, 'create', 'Mascota creada', "ID: {$mascota_id} - {$nombre} - Cliente ID: {$cliente_id}");
        echo json_encode(['success' => true, 'message' => 'Mascota creada correctamente', 'mascota_id' => $mascota_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// PUT - Actualizar mascota (CORREGIDO)
// ============================================
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Aceptar tanto 'id' como 'mascota_id' para compatibilidad
    $id = intval($data['id'] ?? $data['mascota_id'] ?? 0);
    $nombre = trim($data['nombre'] ?? '');
    $especie = $data['especie'] ?? 'perro';
    $raza = trim($data['raza'] ?? '');
    $tamanio = $data['tamanio'] ?? 'mediano';
    $edad = intval($data['edad'] ?? 0);
    $peso = floatval($data['peso'] ?? 0);
    $alergias = trim($data['alergias'] ?? '');
    $temperamento = $data['temperamento'] ?? 'tranquilo';
    $foto_url = $data['foto_url'] ?? null;
    
    // También puede venir cliente_id para actualizar el dueño
    $cliente_id = isset($data['cliente_id']) ? intval($data['cliente_id']) : null;
    
    if ($id <= 0 || empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'ID y nombre de mascota son requeridos']);
        exit;
    }
    
    // Construir la consulta dinámicamente
    if ($cliente_id !== null) {
        $stmt = $conn->prepare("UPDATE mascotas SET nombre = ?, especie = ?, raza = ?, tamanio = ?, edad = ?, peso = ?, alergias = ?, temperamento = ?, foto_url = ?, cliente_id = ? WHERE id = ?");
        $stmt->bind_param("sssssiisssi", $nombre, $especie, $raza, $tamanio, $edad, $peso, $alergias, $temperamento, $foto_url, $cliente_id, $id);
    } else {
        $stmt = $conn->prepare("UPDATE mascotas SET nombre = ?, especie = ?, raza = ?, tamanio = ?, edad = ?, peso = ?, alergias = ?, temperamento = ?, foto_url = ? WHERE id = ?");
        $stmt->bind_param("sssssiissi", $nombre, $especie, $raza, $tamanio, $edad, $peso, $alergias, $temperamento, $foto_url, $id);
    }
    
    if ($stmt->execute()) {
        guardarLogMascota($conn, 'edit', 'Mascota actualizada', "ID: {$id} - {$nombre}");
        echo json_encode(['success' => true, 'message' => 'Mascota actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// DELETE - Eliminar mascota
// ============================================
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? $data['mascota_id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de mascota requerido']);
        exit;
    }
    
    // Verificar que no tenga citas pendientes
    $stmt = $conn->prepare("SELECT id FROM citas WHERE mascota_id = ? AND fecha >= CURDATE() AND estado NOT IN ('cancelada', 'completada')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'No se puede eliminar la mascota porque tiene citas programadas']);
        exit;
    }
    $stmt->close();
    
    // Obtener nombre para el log
    $stmt = $conn->prepare("SELECT nombre FROM mascotas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mascota = $result->fetch_assoc();
    $nombre_mascota = $mascota['nombre'] ?? 'Desconocida';
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM mascotas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        guardarLogMascota($conn, 'delete', 'Mascota eliminada', "ID: {$id} - {$nombre_mascota}");
        echo json_encode(['success' => true, 'message' => 'Mascota eliminada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Si no es ningún método permitido
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
?>