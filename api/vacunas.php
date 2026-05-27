<?php
require_once 'config.php';

header('Content-Type: application/json');

$usuario = verificarAuthJWT();

if (!$usuario) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// GET - Obtener vacunas disponibles
// ============================================
if ($method === 'GET' && $action === 'lista') {
    $sql = "SELECT * FROM vacunas WHERE activa = 1 ORDER BY nombre";
    $result = $conn->query($sql);
    
    $vacunas = [];
    while ($row = $result->fetch_assoc()) {
        $vacunas[] = $row;
    }
    
    echo json_encode(['success' => true, 'vacunas' => $vacunas]);
    exit;
}

// ============================================
// GET - Obtener cartilla de una mascota
// ============================================
if ($method === 'GET' && $action === 'cartilla') {
    $mascota_id = isset($_GET['mascota_id']) ? intval($_GET['mascota_id']) : 0;
    
    if ($mascota_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de mascota requerido']);
        exit;
    }
    
    // Verificar que la mascota pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM mascotas WHERE id = ? AND cliente_id = ?");
    $stmt->bind_param("ii", $mascota_id, $usuario['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0 && !in_array($usuario['rol'], ['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para ver esta cartilla']);
        exit;
    }
    $stmt->close();
    
    $sql = "SELECT cv.*, v.nombre as vacuna_nombre, v.descripcion, v.periodo_dias,
            DATEDIFF(cv.fecha_proxima, CURDATE()) as dias_restantes,
            CASE 
                WHEN cv.fecha_proxima < CURDATE() THEN 'vencida'
                WHEN cv.fecha_proxima <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'proxima'
                ELSE 'al_dia'
            END as estado
            FROM cartilla_vacunacion cv
            JOIN vacunas v ON cv.vacuna_id = v.id
            WHERE cv.mascota_id = ?
            ORDER BY cv.fecha_aplicacion DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mascota_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartilla = [];
    while ($row = $result->fetch_assoc()) {
        $cartilla[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'cartilla' => $cartilla]);
    exit;
}

// ============================================
// POST - Registrar nueva vacuna en cartilla
// ============================================
if ($method === 'POST' && $action === 'registrar') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $mascota_id = intval($data['mascota_id'] ?? 0);
    $vacuna_id = intval($data['vacuna_id'] ?? 0);
    $fecha_aplicacion = $data['fecha_aplicacion'] ?? '';
    $lote = trim($data['lote'] ?? '');
    $veterinario = trim($data['veterinario'] ?? '');
    $observaciones = trim($data['observaciones'] ?? '');
    
    if ($mascota_id <= 0 || $vacuna_id <= 0 || empty($fecha_aplicacion)) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    
    // Verificar permisos
    $stmt = $conn->prepare("SELECT id, cliente_id FROM mascotas WHERE id = ?");
    $stmt->bind_param("i", $mascota_id);
    $stmt->execute();
    $mascota = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$mascota) {
        echo json_encode(['success' => false, 'error' => 'Mascota no encontrada']);
        exit;
    }
    
    if ($mascota['cliente_id'] != $usuario['id'] && !in_array($usuario['rol'], ['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso']);
        exit;
    }
    
    // Obtener periodo de la vacuna
    $stmt = $conn->prepare("SELECT periodo_dias FROM vacunas WHERE id = ?");
    $stmt->bind_param("i", $vacuna_id);
    $stmt->execute();
    $vacuna = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $fecha_proxima = null;
    if ($vacuna && $vacuna['periodo_dias']) {
        $fecha_proxima = date('Y-m-d', strtotime($fecha_aplicacion . ' + ' . $vacuna['periodo_dias'] . ' days'));
    }
    
    $stmt = $conn->prepare("INSERT INTO cartilla_vacunacion (mascota_id, vacuna_id, fecha_aplicacion, fecha_proxima, lote, veterinario, observaciones) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $mascota_id, $vacuna_id, $fecha_aplicacion, $fecha_proxima, $lote, $veterinario, $observaciones);
    
    if ($stmt->execute()) {
        $cartilla_id = $conn->insert_id;
        
        // Crear recordatorio
        if ($fecha_proxima) {
            $stmt2 = $conn->prepare("INSERT INTO recordatorios_vacunas (cartilla_id, fecha_recordatorio) VALUES (?, ?)");
            $stmt2->bind_param("is", $cartilla_id, $fecha_proxima);
            $stmt2->execute();
            $stmt2->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Vacuna registrada correctamente', 'cartilla_id' => $cartilla_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// DELETE - Eliminar registro de vacuna
// ============================================
if ($method === 'DELETE' && $action === 'eliminar') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cartilla_id = intval($data['cartilla_id'] ?? 0);
    
    if ($cartilla_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID requerido']);
        exit;
    }
    
    // Verificar permisos
    $stmt = $conn->prepare("SELECT cv.id, m.cliente_id 
                            FROM cartilla_vacunacion cv 
                            JOIN mascotas m ON cv.mascota_id = m.id 
                            WHERE cv.id = ?");
    $stmt->bind_param("i", $cartilla_id);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$registro) {
        echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
        exit;
    }
    
    if ($registro['cliente_id'] != $usuario['id'] && !in_array($usuario['rol'], ['admin', 'recep'])) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM cartilla_vacunacion WHERE id = ?");
    $stmt->bind_param("i", $cartilla_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// ============================================
// GET - Estadísticas de vacunación
// ============================================
if ($method === 'GET' && $action === 'estadisticas') {
    $mascota_id = isset($_GET['mascota_id']) ? intval($_GET['mascota_id']) : 0;
    
    if ($mascota_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de mascota requerido']);
        exit;
    }
    
    $sql = "SELECT 
                COUNT(*) as total_vacunas,
                SUM(CASE WHEN fecha_proxima < CURDATE() THEN 1 ELSE 0 END) as vencidas,
                SUM(CASE WHEN fecha_proxima BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as proximas,
                SUM(CASE WHEN vacuna_id IN (SELECT id FROM vacunas WHERE obligatoria = 1) THEN 1 ELSE 0 END) as obligatorias
            FROM cartilla_vacunacion 
            WHERE mascota_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mascota_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['success' => true, 'estadisticas' => $stats]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida']);
?>