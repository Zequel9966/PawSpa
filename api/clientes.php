<?php
// Desactivar errores en pantalla
ini_set('display_errors', 0);
error_reporting(0);

require_once 'config.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

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
// GET - Obtener clientes
// ============================================
if ($method === 'GET' && !isset($_GET['action'])) {
    if (!hasRole(['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
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
        // Contar mascotas del cliente
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM mascotas WHERE cliente_id = ?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $mascotas = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Determinar nivel según puntos
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
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'mascotas_cliente') {
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
// POST - Crear nuevo cliente
// ============================================
if ($method === 'POST' && !isset($_GET['action'])) {
    if (!hasRole(['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
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
    
    // Verificar si el email ya existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
        exit;
    }
    $stmt->close();
    
    // Generar contraseña aleatoria
    $password = substr(md5($email . time()), 0, 8);
    
    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol, telefono, activo) VALUES (?, ?, MD5(?), 'client', ?, 1)");
    $stmt->bind_param("ssss", $nombre, $email, $password, $telefono);
    
    if ($stmt->execute()) {
        $usuario_id = $conn->insert_id;
        
        // Insertar en tabla clientes
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
// POST - Crear nueva mascota (cliente)
// ============================================
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'mascota_cliente') {
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
    
    // Verificar que el cliente existe
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
// PUT - Actualizar cliente (CORREGIDO)
// ============================================
if ($method === 'PUT') {
    // Obtener los datos del cuerpo de la petición
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log para depuración
    error_log("PUT request received. Input: " . $input);
    error_log("Decoded data: " . print_r($data, true));
    
    $id = $data['id'] ?? 0;
    $nombre = trim($data['nombre'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    $email = trim($data['email'] ?? '');
    $nivel = $data['nivel'] ?? 'Bronze';
    
    // Validaciones
    if (!$id || empty($nombre) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'ID, nombre y email son requeridos']);
        exit;
    }
    
    // Verificar permisos (solo admin y recep)
    if (!hasRole(['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para editar clientes']);
        exit;
    }
    
    // Verificar si el email ya existe en otro usuario
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'El email ya está en uso por otro usuario']);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Actualizar el usuario
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ? AND rol = 'client'");
    $stmt->bind_param("sssi", $nombre, $email, $telefono, $id);
    
    if ($stmt->execute()) {
        // Actualizar puntos según nivel
        $puntos = 0;
        if ($nivel === 'Gold') $puntos = 1000;
        elseif ($nivel === 'Silver') $puntos = 500;
        
        $stmt2 = $conn->prepare("UPDATE usuarios SET puntos = ? WHERE id = ?");
        $stmt2->bind_param("ii", $puntos, $id);
        $stmt2->execute();
        $stmt2->close();
        
        // Guardar log
        if (function_exists('guardarLog')) {
            guardarLog($conn, 'edit', 'Cliente actualizado', "ID: {$id} - Nombre: {$nombre}");
        }
        
        echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// PUT - Actualizar mascota
// ============================================
if ($method === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'mascota_cliente') {
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
// DELETE - Eliminar cliente FÍSICAMENTE
// ============================================
if ($method === 'DELETE' && !isset($_GET['action'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente requerido']);
        exit;
    }
    
    // Solo admin puede eliminar
    if (!hasRole(['admin'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar clientes']);
        exit;
    }
    
    // Obtener datos del cliente antes de eliminar
    $stmtNombre = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = ? AND rol = 'client'");
    $stmtNombre->bind_param("i", $id);
    $stmtNombre->execute();
    $cliente = $stmtNombre->get_result()->fetch_assoc();
    $stmtNombre->close();
    
    if (!$cliente) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }
    
    // Verificar si tiene citas pendientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE cliente_id = ? AND fecha >= CURDATE() AND estado NOT IN ('cancelada', 'completada')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $citas_pendientes = $result->fetch_assoc()['total'];
    $stmt->close();
    
    if ($citas_pendientes > 0) {
        echo json_encode(['success' => false, 'error' => "No se puede eliminar el cliente porque tiene {$citas_pendientes} citas pendientes"]);
        exit;
    }
    
    // INICIAR TRANSACCIÓN
    $conn->begin_transaction();
    
    try {
        // 1. Eliminar mascotas
        $stmt = $conn->prepare("DELETE FROM mascotas WHERE cliente_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // 2. Eliminar registro de clientes
        $stmt = $conn->prepare("DELETE FROM clientes WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // 3. Eliminar notificaciones
        $stmt = $conn->prepare("DELETE FROM notificaciones WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // 4. Eliminar carrito
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // 5. Eliminar tokens enviados
        $stmt = $conn->prepare("DELETE FROM tokens_enviados WHERE usuario_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // 6. Eliminar el usuario
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'client'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        guardarLog($conn, 'delete', 'Cliente eliminado permanentemente', "ID: {$id} - Nombre: {$cliente['nombre']} - Email: {$cliente['email']}");
        
        echo json_encode(['success' => true, 'message' => "Cliente '{$cliente['nombre']}' eliminado permanentemente"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// DELETE - Eliminar mascota
// ============================================
if ($method === 'DELETE' && isset($_GET['action']) && $_GET['action'] === 'mascota_cliente') {
    $data = json_decode(file_get_contents('php://input'), true);
    $mascota_id = intval($data['mascota_id'] ?? 0);
    
    if ($mascota_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }
    
    // Verificar que no tenga citas pendientes
    $stmt = $conn->prepare("SELECT id FROM citas WHERE mascota_id = ? AND fecha >= CURDATE() AND estado NOT IN ('cancelada', 'completada')");
    $stmt->bind_param("i", $mascota_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'No se puede eliminar la mascota porque tiene citas programadas']);
        exit;
    }
    $stmt->close();
    
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
// GET - Verificar si un cliente existe (para debug)
// ============================================
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verificar') {
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