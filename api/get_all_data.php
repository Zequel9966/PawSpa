<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$data = [];

// 1. Productos
$result = $conn->query("SELECT * FROM productos WHERE activo = 1");
$data['productos'] = $result->fetch_all(MYSQLI_ASSOC);

// 2 sección de clientes
$sql = "SELECT u.id as usuario_id, u.nombre, u.email, u.telefono, u.puntos, u.fecha_registro,
               c.id as cliente_id, c.direccion
        FROM usuarios u 
        LEFT JOIN clientes c ON u.id = c.usuario_id
        WHERE u.rol = 'client' AND u.activo = 1 
        ORDER BY u.id DESC";
$result = $conn->query($sql);

$clientes = [];
while ($row = $result->fetch_assoc()) {
    // Contar mascotas del cliente
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM mascotas WHERE cliente_id = ?");
    $stmt->bind_param("i", $row['usuario_id']);
    $stmt->execute();
    $mascotas = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $clientes[] = [
        'id' => $row['usuario_id'],  // ← USAR usuario_id como ID principal
        'cliente_table_id' => $row['cliente_id'],  // Guardar también el ID de clientes
        'nombre' => $row['nombre'],
        'email' => $row['email'],
        'telefono' => $row['telefono'] ?? '',
        'puntos' => $row['puntos'] ?? 0,
        'total_mascotas' => $mascotas['total'] ?? 0,
        'ultima_visita' => $row['fecha_registro'] ?? 'Nunca',
        'nivel' => $row['puntos'] >= 1000 ? 'Gold' : ($row['puntos'] >= 500 ? 'Silver' : 'Bronze'),
        'direccion' => $row['direccion'] ?? ''
    ];
}
$data['clientes'] = $clientes;

// 3. Citas con JOIN para obtener nombres
$sql = "SELECT c.*, u.nombre as cliente_nombre 
        FROM citas c 
        JOIN usuarios u ON c.cliente_id = u.id 
        ORDER BY c.fecha DESC LIMIT 50";
$result = $conn->query($sql);
$data['citas'] = $result->fetch_all(MYSQLI_ASSOC);

// 4. Usuarios del sistema
$result = $conn->query("SELECT id, nombre, email, rol, activo FROM usuarios");
$data['usuarios'] = $result->fetch_all(MYSQLI_ASSOC);

// 5. Mascotas
$sql = "SELECT m.*, u.nombre as duenio_nombre 
        FROM mascotas m 
        JOIN usuarios u ON m.cliente_id = u.id";
$result = $conn->query($sql);
$data['mascotas'] = $result->fetch_all(MYSQLI_ASSOC);

// 6. Servicios
$result = $conn->query("SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre");
if ($result) {
    $data['servicios'] = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $data['servicios'] = [];
}

// 7. Groomers
$sql = "SELECT g.id, g.horario_inicio, g.horario_fin, u.nombre, u.email
        FROM groomers g
        JOIN usuarios u ON g.usuario_id = u.id
        WHERE g.activo = 1
        ORDER BY u.nombre";
$result = $conn->query($sql);
if ($result) {
    $data['groomers'] = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $data['groomers'] = [];
}

// 8. Mascotas - CON DATOS COMPLETOS
$sql = "SELECT m.*, u.nombre as duenio_nombre, u.id as duenio_id
        FROM mascotas m 
        JOIN usuarios u ON m.cliente_id = u.id
        WHERE u.rol = 'client' AND u.activo = 1";
$result = $conn->query($sql);
$data['mascotas'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// 9. Configuración
$result = $conn->query("SELECT * FROM config LIMIT 1");
if ($result && $result->num_rows > 0) {
    $data['config'] = $result->fetch_assoc();
} else {
    $data['config'] = [
        'nombre' => 'PawSpa Bolivia',
        'wa' => '+591 72345678',
        'stockMin' => 5,
        'recordatorio' => 2
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
?>