<?php
require_once 'config.php';

header('Content-Type: application/json');

$usuario = verificarAuthJWT();

if (!$usuario) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// GET - Obtener checklist de una cita
// ============================================
if ($method === 'GET') {
    $cita_id = isset($_GET['cita_id']) ? intval($_GET['cita_id']) : 0;
    
    if ($cita_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cita requerido']);
        exit;
    }
    
    // Verificar que la tabla existe
    $checkTable = $conn->query("SHOW TABLES LIKE 'grooming_checklist'");
    if ($checkTable->num_rows == 0) {
        echo json_encode(['success' => true, 'checklist' => []]);
        exit;
    }
    
    $sql = "SELECT * FROM grooming_checklist WHERE cita_id = ? ORDER BY item_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $checklist = [];
    while ($row = $result->fetch_assoc()) {
        $checklist[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'checklist' => $checklist]);
    exit;
}

// ============================================
// POST - Guardar checklist de una cita
// ============================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $cita_id = intval($data['cita_id'] ?? 0);
    $checklist = $data['checklist'] ?? [];
    $observaciones = $data['observaciones'] ?? '';
    
    if ($cita_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cita requerido']);
        exit;
    }
    
    // Verificar que la cita existe
    $stmt = $conn->prepare("SELECT id, estado FROM citas WHERE id = ?");
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$cita) {
        echo json_encode(['success' => false, 'error' => 'Cita no encontrada']);
        exit;
    }
    
    // Crear tabla si no existe
    $conn->query("CREATE TABLE IF NOT EXISTS `grooming_checklist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `cita_id` int(11) NOT NULL,
        `item_id` int(11) NOT NULL,
        `completado` tinyint(1) DEFAULT 0,
        `observacion` text,
        `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `cita_id` (`cita_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Eliminar checklist existente
    $stmt = $conn->prepare("DELETE FROM grooming_checklist WHERE cita_id = ?");
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $stmt->close();
    
    // Insertar nuevo checklist
    $success = true;
    $errorMsg = '';
    
    foreach ($checklist as $item) {
        $item_id = intval($item['item_id'] ?? 0);
        $completado = $item['completado'] ? 1 : 0;
        $observacion_item = $item['observacion'] ?? '';
        
        if ($item_id > 0) {
            $stmt = $conn->prepare("INSERT INTO grooming_checklist (cita_id, item_id, completado, observacion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $cita_id, $item_id, $completado, $observacion_item);
            if (!$stmt->execute()) {
                $success = false;
                $errorMsg = $stmt->error;
            }
            $stmt->close();
        }
    }
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Checklist guardado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar checklist: ' . $errorMsg]);
    }
    exit;
}

// ============================================
// DELETE - Eliminar checklist de una cita
// ============================================
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cita_id = intval($data['cita_id'] ?? 0);
    
    if ($cita_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cita requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM grooming_checklist WHERE cita_id = ?");
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Checklist eliminado']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Método no permitido']);
?>