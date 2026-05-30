<?php
// Asegurar que la sesión esté iniciada para validar la autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Función para validar formato de fecha
function validarFecha($fecha) {
    if (empty($fecha)) return false;
    // Formato YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return false;
    // Validar que sea una fecha real
    $date = DateTime::createFromFormat('Y-m-d', $fecha);
    return $date && $date->format('Y-m-d') === $fecha;
}

// Función para validar formato de hora
function validarHora($hora) {
    if (empty($hora)) return false;
    // Aceptar HH:MM:SS o HH:MM
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) return true;
    if (preg_match('/^\d{2}:\d{2}$/', $hora)) return true;
    return false;
}

// Función para normalizar hora (agregar segundos si faltan)
function normalizarHora($hora) {
    if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
        return $hora . ':00';
    }
    return $hora;
}

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user = $_SESSION['user'];
$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// PUT - Actualizar cita (estado, groomer, observaciones)
// ============================================
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cita requerido']);
        exit;
    }
    
    $updates = [];
    $params = [];
    $types = "";
    
    if (isset($data['estado'])) {
        $updates[] = "estado = ?";
        $params[] = $data['estado'];
        $types .= "s";
    }
    
    if (isset($data['groomer_id'])) {
        $updates[] = "groomer_id = ?";
        $params[] = $data['groomer_id'];
        $types .= "i";
    }
    
    if (isset($data['observaciones'])) {
        $updates[] = "observaciones = ?";
        $params[] = $data['observaciones'];
        $types .= "s";
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'No hay campos para actualizar']);
        exit;
    }
    
    $params[] = $id;
    $types .= "i";
    
    $sql = "UPDATE citas SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cita actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener una cita específica
            getCitaById($conn, $_GET['id'], $user);
        } elseif (isset($_GET['fecha'])) {
            // Obtener citas por fecha
            getCitasByFecha($conn, $_GET['fecha'], $user);
        } elseif (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
            // Obtener citas por rango de fechas
            getCitasByRango($conn, $_GET['fecha_inicio'], $_GET['fecha_fin'], $user);
        } elseif (isset($_GET['semana']) && isset($_GET['year'])) {
            // Obtener citas de una semana específica
            getCitasBySemana($conn, $_GET['semana'], $_GET['year'], $user);
        } else {
            // Obtener todas las citas próximas
            getProximasCitas($conn, $user);
        }
        break;
        
    case 'POST':
        // Crear nueva cita
        createCita($conn, $user);
        break;
        
    case 'PUT':
        // Actualizar cita existente
        updateCita($conn, $user);
        break;
        
    case 'DELETE':
        // Cancelar/eliminar cita
        deleteCita($conn, $user);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

// ──────────────────────────────────────────────
// FUNCIONES PARA CITAS
// ──────────────────────────────────────────────

/**
 * Obtener cita por ID (CORREGIDO bind_param)
 */
function getCitaById($conn, $id, $user) {
    // CORREGIDO: Mismo caso, LEFT JOIN para consultas individuales
    $sql = "SELECT c.*, 
                   u.nombre as cliente_nombre, 
                   m.nombre as mascota_nombre, 
                   s.nombre as servicio_nombre,
                   gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.id = ?";
    
    if ($user['rol'] === 'client') {
        $sql .= " AND c.cliente_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user['id']);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($cita = $result->fetch_assoc()) {
        $cita['groomer_nombre'] = $cita['groomer_nombre'] ?? 'No asignado';
        echo json_encode(['success' => true, 'cita' => $cita]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Cita no encontrada o acceso denegado']);
    }
    $stmt->close();
}

/**
 * Obtener citas por fecha específica
 */
function getCitasByFecha($conn, $fecha, $user) {
    $sql = "SELECT c.*, 
            u.nombre as cliente_nombre,
            m.nombre as mascota_nombre,
            s.nombre as servicio_nombre,
            gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.fecha = ?";
    
    // Filtrar por rol
    if ($user['rol'] == 'client') {
        $sql .= " AND c.cliente_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fecha, $user['id']);
    } elseif ($user['rol'] == 'groo') {
        $sql .= " AND g.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fecha, $user['id']);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $fecha);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
    
    echo json_encode(['success' => true, 'citas' => $citas]);
    $stmt->close();
}

/**
 * Obtener citas por rango de fechas (solo fechas válidas)
 */
function getCitasByRango($conn, $fecha_inicio, $fecha_fin, $user) {
    $sql = "SELECT c.*, 
            u.nombre as cliente_nombre,
            u.telefono as cliente_telefono,
            m.nombre as mascota_nombre,
            m.especie, m.raza,
            s.nombre as servicio_nombre,
            s.duracion_min, s.precio_base,
            gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.fecha BETWEEN ? AND ?
            AND c.fecha != '00:00:00'
            AND c.fecha != '0000-00-00'
            AND c.fecha IS NOT NULL";
    
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    if ($user['rol'] == 'client') {
        $sql .= " AND c.cliente_id = ?";
        $params[] = $user['id'];
        $types .= "i";
    } elseif ($user['rol'] == 'groo') {
        $sql .= " AND g.usuario_id = ?";
        $params[] = $user['id'];
        $types .= "i";
    }
    
    $sql .= " ORDER BY c.fecha, c.hora";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['hora'] && strlen($row['hora']) >= 5) {
            $row['hora'] = substr($row['hora'], 0, 5);
        }
        $citas[] = $row;
    }
    
    echo json_encode(['success' => true, 'citas' => $citas]);
    $stmt->close();
}

/**
 * Obtener citas de una semana específica
 */
function getCitasBySemana($conn, $semana, $year, $user) {
    $startDate = new DateTime();
    $startDate->setISODate($year, $semana);
    $endDate = clone $startDate;
    $endDate->modify('+6 days');
    
    $sql = "SELECT c.*, 
            u.nombre as cliente_nombre,
            m.nombre as mascota_nombre,
            s.nombre as servicio_nombre, s.duracion_min,
            gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.fecha BETWEEN ? AND ?";
    
    $params = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
    $types = "ss";
    
    if ($user['rol'] == 'client') {
        $sql .= " AND c.cliente_id = ?";
        $params[] = $user['id'];
        $types .= "i";
    } elseif ($user['rol'] == 'groo') {
        $sql .= " AND g.usuario_id = ?";
        $params[] = $user['id'];
        $types .= "i";
    }
    
    $sql .= " ORDER BY c.fecha, c.hora";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
    
    echo json_encode(['success' => true, 'citas' => $citas, 'semana' => $semana, 'year' => $year]);
    $stmt->close();
}

/**
 * Obtener próximas citas (próximos 30 días)
 */
function getProximasCitas($conn, $user) {
    $hoy = date('Y-m-d');
    
    // CORREGIDO: Se cambiaron los JOIN de groomers por LEFT JOIN para que traiga la cita aunque no tenga groomer asignado
    $sql = "SELECT c.*, 
                   u.nombre as cliente_nombre, 
                   m.nombre as mascota_nombre, 
                   s.nombre as servicio_nombre,
                   gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.fecha >= ? AND c.estado != 'cancelada'";
    
    if ($user['rol'] === 'client') {
        $sql .= " AND c.cliente_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hoy, $user['id']);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $hoy);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $citas = [];
    while ($row = $result->fetch_assoc()) { 
        // Si el groomer es NULL, evitar que mande una variable vacía rota
        $row['groomer_nombre'] = $row['groomer_nombre'] ?? 'No asignado';
        $citas[] = $row; 
    }
    echo json_encode(['success' => true, 'citas' => $citas]);
    $stmt->close();
}



/**
 * Crear nueva cita (CORREGIDO tipos e inserción de groomer_id)
 */
function createCita($conn, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['cliente_id', 'mascota_id', 'servicio_id', 'fecha', 'hora'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => "El campo {$field} es requerido"]);
            return;
        }
    }
    
    if (!validarFecha($data['fecha'])) {
        echo json_encode(['success' => false, 'error' => 'Formato de fecha inválido. Use YYYY-MM-DD']);
        return;
    }
    
    if (!validarHora($data['hora'])) {
        echo json_encode(['success' => false, 'error' => 'Formato de hora inválido. Use HH:MM o HH:MM:SS']);
        return;
    }
    
    $hora_normalizada = normalizarHora($data['hora']);
    
    // Validar groomer_id si viene
    $groomer_id = null;
    if (isset($data['groomer_id']) && !empty($data['groomer_id'])) {
        $groomer_id = intval($data['groomer_id']);
        // Verificar que el groomer existe
        $check = $conn->prepare("SELECT id FROM groomers WHERE id = ?");
        $check->bind_param("i", $groomer_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $groomer_id = null; // Si no existe, poner null
        }
        $check->close();
    }
    
    // Verificar que la mascota pertenece al cliente
    $stmt = $conn->prepare("SELECT id FROM mascotas WHERE id = ? AND cliente_id = ?");
    $stmt->bind_param("ii", $data['mascota_id'], $data['cliente_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'La mascota no pertenece al cliente especificado']);
        return;
    }
    $stmt->close();
    
    $estado = 'pendiente';
    $observaciones = $data['observaciones'] ?? '';
    
    // Insertar cita - groomer_id puede ser NULL
    $stmt = $conn->prepare("INSERT INTO citas (cliente_id, mascota_id, groomer_id, servicio_id, fecha, hora, estado, observaciones) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiissss", 
        $data['cliente_id'], 
        $data['mascota_id'], 
        $groomer_id, 
        $data['servicio_id'], 
        $data['fecha'], 
        $hora_normalizada, 
        $estado, 
        $observaciones
    );
    
    if ($stmt->execute()) {
        $cita_id = $conn->insert_id;
        
        crearNotificacion($conn, $data['cliente_id'], 
            "Nueva cita agendada", 
            "Tu cita ha sido agendada para el {$data['fecha']} a las {$hora_normalizada}"
        );
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cita creada exitosamente',
            'cita_id' => $cita_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}


/**
 * Actualizar cita existente (CORREGIDO mapeo dinámico de tipos)
 */
function updateCita($conn, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'error' => 'ID de cita requerido']);
        return;
    }
    
    if (!hasCitaPermission($conn, $data['id'], $user)) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para modificar esta cita']);
        return;
    }
    
    $updates = [];
    $params = [];
    $types = "";
    
    $allowedFields = ['fecha', 'hora', 'estado', 'observaciones', 'groomer_id', 'servicio_id'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            // Manejar valores nulos explícitos para el groomer
            if ($field === 'groomer_id' && $data[$field] === '') {
                $params[] = null;
                $types .= "i";
            } else {
                $params[] = $data[$field];
                // CORREGIDO: Mapear dinámicamente si es un id numérico
                $types .= (in_array($field, ['groomer_id', 'servicio_id'])) ? "i" : "s";
            }
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'No hay campos para actualizar']);
        return;
    }
    
    $params[] = $data['id'];
    $types .= "i";
    
    $sql = "UPDATE citas SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        if (isset($data['estado']) && $data['estado'] == 'completada') {
            crearFichaGrooming($conn, $data['id']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cita actualizada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

/**
 * Cancelar/Eliminar cita
 */
function deleteCita($conn, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'error' => 'ID de cita requerido']);
        return;
    }
    
    if (!hasCitaPermission($conn, $data['id'], $user)) {
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para cancelar esta cita']);
        return;
    }
    
    $motivo = $data['motivo'] ?? 'Cancelada por el usuario';
    
    $stmt = $conn->prepare("UPDATE citas SET estado = 'cancelada', observaciones = CONCAT(observaciones, ' | Cancelado: ', ?) WHERE id = ?");
    $stmt->bind_param("si", $motivo, $data['id']);
    
    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("SELECT cliente_id FROM citas WHERE id = ?");
        $stmt2->bind_param("i", $data['id']);
        $stmt2->execute();
        $result = $stmt2->get_result();
        if ($cita = $result->fetch_assoc()) {
            crearNotificacion($conn, $cita['cliente_id'], 
                "Cita cancelada", 
                "Tu cita ha sido cancelada. Motivo: {$motivo}"
            );
        }
        $stmt2->close();
        
        echo json_encode(['success' => true, 'message' => 'Cita cancelada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}

/**
 * Verificar disponibilidad de un groomer en fecha y hora específica
 */
function checkDisponibilidad($conn, $groomer_id, $fecha, $hora, $servicio_id) {
    $stmt = $conn->prepare("SELECT duracion_min FROM servicios WHERE id = ?");
    $stmt->bind_param("i", $servicio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servicio = $result->fetch_assoc();
    $duracion = $servicio['duracion_min'] ?? 60;
    $stmt->close();
    
    $hora_fin = date('H:i:s', strtotime($hora . " + {$duracion} minutes"));
    
    $stmt = $conn->prepare("SELECT c.*, s.duracion_min 
                            FROM citas c
                            JOIN servicios s ON c.servicio_id = s.id
                            WHERE c.groomer_id = ? 
                            AND c.fecha = ? 
                            AND c.estado NOT IN ('cancelada', 'completada')
                            AND (
                                (c.hora <= ? AND ADDTIME(c.hora, SEC_TO_TIME(s.duracion_min * 60)) > ?)
                                OR (? < ADDTIME(c.hora, SEC_TO_TIME(s.duracion_min * 60)) AND ? >= c.hora)
                            )");
    $stmt->bind_param("isssss", $groomer_id, $fecha, $hora, $hora, $hora_fin, $hora);
    $stmt->execute();
    $conflictos = $stmt->get_result()->num_rows;
    $stmt->close();
    
    return $conflictos == 0;
}

/**
 * Verificar si el usuario tiene permiso sobre una cita
 */
function hasCitaPermission($conn, $cita_id, $user) {
    if (in_array($user['rol'], ['admin', 'recep'])) {
        return true;
    }
    
    if ($user['rol'] == 'client') {
        $stmt = $conn->prepare("SELECT id FROM citas WHERE id = ? AND cliente_id = ?");
        $stmt->bind_param("ii", $cita_id, $user['id']);
        $stmt->execute();
        $has = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $has;
    }
    
    if ($user['rol'] == 'groo') {
        $stmt = $conn->prepare("SELECT c.id FROM citas c 
                                JOIN groomers g ON c.groomer_id = g.id 
                                WHERE c.id = ? AND g.usuario_id = ?");
        $stmt->bind_param("ii", $cita_id, $user['id']);
        $stmt->execute();
        $has = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $has;
    }
    
    return false;
}

/**
 * Crear ficha de grooming al completar un servicio
 */
function crearFichaGrooming($conn, $cita_id) {
    $stmt = $conn->prepare("SELECT id FROM fichas_grooming WHERE cita_id = ?");
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if (!$existe) {
        $checklist_default = json_encode([
            'baño' => false,
            'corte_pelo' => false,
            'corte_uñas' => false,
            'limpieza_oidos' => false,
            'expresion_glandulas' => false,
            'perfume' => false
        ]);
        
        $stmt = $conn->prepare("INSERT INTO fichas_grooming (cita_id, checklist_items) VALUES (?, ?)");
        $stmt->bind_param("is", $cita_id, $checklist_default);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Crear una notificación para un usuario
 */
function crearNotificacion($conn, $usuario_id, $titulo, $mensaje) {
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $usuario_id, $titulo, $mensaje);
    $stmt->execute();
    $stmt->close();
}

/**
 * Endpoint adicional: Obtener horarios disponibles
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'horarios_disponibles') {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $groomer_id = $_GET['groomer_id'] ?? null;
    $servicio_id = $_GET['servicio_id'] ?? null;
    
    if (!$groomer_id || !$servicio_id) {
        echo json_encode(['error' => 'Se requiere groomer_id y servicio_id']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT horario_inicio, horario_fin FROM groomers WHERE id = ?");
    $stmt->bind_param("i", $groomer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $groomer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$groomer) {
        echo json_encode(['error' => 'Groomer no encontrado']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT duracion_min FROM servicios WHERE id = ?");
    $stmt->bind_param("i", $servicio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servicio = $result->fetch_assoc();
    $duracion = $servicio['duracion_min'] ?? 60;
    $stmt->close();
    
    $inicio = strtotime($groomer['horario_inicio']);
    $fin = strtotime($groomer['horario_fin']);
    $slots = [];
    
    for ($time = $inicio; $time + ($duracion * 60) <= $fin; $time += 1800) {
        $hora = date('H:i:s', $time);
        if (checkDisponibilidad($conn, $groomer_id, $fecha, $hora, $servicio_id)) {
            $slots[] = date('H:i', $time);
        }
    }
    
    echo json_encode(['success' => true, 'horarios' => $slots]);
    exit;
}

/**
 * Endpoint adicional: Estadísticas de citas
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'estadisticas') {
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
    
    $stmt = $conn->prepare("SELECT estado, COUNT(*) as total FROM citas WHERE fecha BETWEEN ? AND ? GROUP BY estado");
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $estados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT s.nombre, COUNT(*) as total 
                            FROM citas c 
                            JOIN servicios s ON c.servicio_id = s.id 
                            WHERE c.fecha BETWEEN ? AND ? 
                            GROUP BY c.servicio_id 
                            ORDER BY total DESC 
                            LIMIT 5");
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $top_servicios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT g.id as groomer_id, gu.nombre, COUNT(*) as total 
                            FROM citas c 
                            JOIN groomers g ON c.groomer_id = g.id 
                            JOIN usuarios gu ON g.usuario_id = gu.id 
                            WHERE c.fecha BETWEEN ? AND ? 
                            GROUP BY c.groomer_id 
                            ORDER BY total DESC");
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $top_groomers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'estadisticas' => [
            'por_estado' => $estados,
            'top_servicios' => $top_servicios,
            'top_groomers' => $top_groomers
        ]
    ]);
    exit;
}
?>