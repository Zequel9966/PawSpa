<?php
// Desactivar errores en pantalla
ini_set('display_errors', 0);
error_reporting(0);

require_once 'config.php';

header('Content-Type: application/json');

// ============================================
// VERIFICAR AUTENTICACIÓN CON JWT
// ============================================
$usuario = verificarAuthJWT();

if (!$usuario) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Función para guardar logs
function guardarLog($conn, $tipo, $accion, $detalle) {
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
// GET - Obtener clientes (solo admin y recep)
// ============================================
if ($method === 'GET' && !isset($_GET['action'])) {
    if (!in_array($usuario['rol'], ['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para ver clientes']);
        exit;
    }
    
    $sql = "SELECT id, nombre, email, telefono, puntos FROM usuarios WHERE rol = 'client' AND activo = 1 ORDER BY id DESC";
    $result = $conn->query($sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM mascotas WHERE cliente_id = ?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $mascotas = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $nivel = 'Bronze';
        if ($row['puntos'] >= 1000) $nivel = 'Gold';
        elseif ($row['puntos'] >= 500) $nivel = 'Silver';
        
        $clientes[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'email' => $row['email'],
            'telefono' => $row['telefono'] ?? '',
            'total_mascotas' => $mascotas['total'] ?? 0,
            'ultima_visita' => 'Nunca',
            'nivel' => $nivel,
            'puntos' => $row['puntos'] ?? 0
        ];
    }
    
    echo json_encode(['success' => true, 'clientes' => $clientes]);
    exit;
}

// ============================================
// GET - Obtener mascotas de un cliente
// ============================================
if ($method === 'GET' && $action === 'mascotas_cliente') {
    $cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
    
    // Log para depuración
    error_log("=== mascotas_cliente llamado con cliente_id: " . $cliente_id);
    
    if ($cliente_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente requerido']);
        exit;
    }
    
    // Verificar permiso
    if ($usuario['rol'] !== 'admin' && $usuario['rol'] !== 'recep' && $usuario['id'] != $cliente_id) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para ver estas mascotas']);
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
    
    error_log("Mascotas encontradas: " . count($mascotas));
    
    echo json_encode(['success' => true, 'mascotas' => $mascotas]);
    exit;
}

// ============================================
// POST - Crear nuevo cliente (solo admin y recep)
// ============================================
if ($method === 'POST' && !isset($_GET['action'])) {
    if (!in_array($usuario['rol'], ['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para crear clientes']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombre = trim($data['nombre'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $email = trim($data['email'] ?? '');
    
    if (empty($nombre) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Nombre y email son requeridos']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
        exit;
    }
    $stmt->close();
    
    $password = substr(md5($email . time()), 0, 8);
    
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol, telefono, activo) VALUES (?, ?, MD5(?), 'client', ?, 1)");
    $stmt->bind_param("ssss", $nombre, $email, $password, $telefono);
    
    if ($stmt->execute()) {
        $usuario_id = $conn->insert_id;
        
        $stmt2 = $conn->prepare("INSERT INTO clientes (usuario_id) VALUES (?)");
        $stmt2->bind_param("i", $usuario_id);
        $stmt2->execute();
        $stmt2->close();
        
        guardarLog($conn, 'create', 'Cliente creado', "ID: {$usuario_id} - Nombre: {$nombre} - Email: {$email}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'cliente' => [
                'id' => $usuario_id,
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// POST - Crear nueva mascota (cliente, admin, recep)
// ============================================
if ($method === 'POST' && $action === 'mascota_cliente') {
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
    
    if ($cliente_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente inválido']);
        exit;
    }
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'El nombre de la mascota es requerido']);
        exit;
    }
    
    // Verificar permiso: el cliente solo puede agregar mascotas a su propia cuenta
    if ($usuario['rol'] !== 'admin' && $usuario['rol'] !== 'recep' && $usuario['id'] != $cliente_id) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para agregar mascotas a este cliente']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'client'");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO mascotas (cliente_id, nombre, especie, raza, tamanio, edad, peso, alergias, temperamento) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiiss", $cliente_id, $nombre, $especie, $raza, $tamanio, $edad, $peso, $alergias, $temperamento);
    
    if ($stmt->execute()) {
        $mascota_id = $conn->insert_id;
        guardarLog($conn, 'create', 'Mascota agregada', "Cliente ID: {$cliente_id} - Mascota: {$nombre}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Mascota agregada exitosamente',
            'mascota_id' => $mascota_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// PUT - Actualizar cliente
// ============================================
if ($method === 'PUT' && !isset($_GET['action'])) {
    if (!in_array($usuario['rol'], ['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para editar clientes']);
        exit;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? 0;
    $nombre = trim($data['nombre'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $email = trim($data['email'] ?? '');
    $nivel = $data['nivel'] ?? 'Bronze';
    
    if (!$id || empty($nombre) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'ID, nombre y email son requeridos']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El email ya está en uso por otro usuario']);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ? AND rol = 'client'");
    $stmt->bind_param("sssi", $nombre, $email, $telefono, $id);
    
    if ($stmt->execute()) {
        $puntos = 0;
        if ($nivel === 'Gold') $puntos = 1000;
        elseif ($nivel === 'Silver') $puntos = 500;
        
        $stmt2 = $conn->prepare("UPDATE usuarios SET puntos = ? WHERE id = ?");
        $stmt2->bind_param("ii", $puntos, $id);
        $stmt2->execute();
        $stmt2->close();
        
        guardarLog($conn, 'edit', 'Cliente actualizado', "ID: {$id} - Nombre: {$nombre}");
        
        echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// PUT - Actualizar mascota
// ============================================
if ($method === 'PUT' && $action === 'mascota_cliente') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $mascota_id = intval($data['mascota_id'] ?? 0);
    $nombre = trim($data['nombre'] ?? '');
    $especie = $data['especie'] ?? 'perro';
    $raza = trim($data['raza'] ?? '');
    $tamanio = $data['tamanio'] ?? 'mediano';
    $edad = intval($data['edad'] ?? 0);
    $peso = floatval($data['peso'] ?? 0);
    $alergias = trim($data['alergias'] ?? '');
    $temperamento = $data['temperamento'] ?? 'tranquilo';
    
    if ($mascota_id <= 0 || empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE mascotas SET nombre = ?, especie = ?, raza = ?, tamanio = ?, edad = ?, peso = ?, alergias = ?, temperamento = ? WHERE id = ?");
    $stmt->bind_param("sssssiiss", $nombre, $especie, $raza, $tamanio, $edad, $peso, $alergias, $temperamento, $mascota_id);
    
    if ($stmt->execute()) {
        guardarLog($conn, 'edit', 'Mascota actualizada', "ID: {$mascota_id} - {$nombre}");
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
if ($method === 'DELETE' && $action === 'mascota_cliente') {
    $data = json_decode(file_get_contents('php://input'), true);
    $mascota_id = intval($data['mascota_id'] ?? 0);
    
    if ($mascota_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM mascotas WHERE id = ?");
    $stmt->bind_param("i", $mascota_id);
    
    if ($stmt->execute()) {
        guardarLog($conn, 'delete', 'Mascota eliminada', "ID: {$mascota_id}");
        echo json_encode(['success' => true, 'message' => 'Mascota eliminada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// DELETE - Eliminar cliente (solo admin)
// ============================================
if ($method === 'DELETE' && !isset($_GET['action'])) {
    if ($usuario['rol'] !== 'admin') {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar clientes']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente requerido']);
        exit;
    }
    
    $stmtNombre = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = ? AND rol = 'client'");
    $stmtNombre->bind_param("i", $id);
    $stmtNombre->execute();
    $cliente = $stmtNombre->get_result()->fetch_assoc();
    $stmtNombre->close();
    
    if (!$cliente) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("DELETE FROM mascotas WHERE cliente_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM clientes WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM notificaciones WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'client'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        guardarLog($conn, 'delete', 'Cliente eliminado', "ID: {$id} - Nombre: {$cliente['nombre']}");
        
        echo json_encode(['success' => true, 'message' => "Cliente eliminado correctamente"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// GET - Verificar si un cliente existe (para debug)
// ============================================
if ($method === 'GET' && $action === 'verificar') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, nombre, email, telefono, puntos, activo FROM usuarios WHERE id = ? AND rol = 'client'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();
    
    if ($cliente) {
        echo json_encode(['success' => true, 'cliente' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
    }
    exit;
}

// Si no es ningún método permitido
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
?>