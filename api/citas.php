<?php
require_once 'config.php';

header('Content-Type: application/json');

// ============================================
// VERIFICAR AUTENTICACIÓN CON JWT
// ============================================
$usuario = verificarAuthJWT();

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Función para validar formato de fecha
function validarFecha($fecha) {
    if (empty($fecha)) return false;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return false;
    $date = DateTime::createFromFormat('Y-m-d', $fecha);
    return $date && $date->format('Y-m-d') === $fecha;
}

// Función para validar formato de hora
function validarHora($hora) {
    if (empty($hora)) return false;
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

$user = $usuario; // Usar el usuario autenticado por JWT
$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// FUNCIONES PARA CITAS
// ============================================

/**
 * Obtener cita por ID
 */
function getCitaById($conn, $id, $user) {
    $sql = "SELECT c.*, 
            u.nombre as cliente_nombre, u.telefono as cliente_telefono,
            m.nombre as mascota_nombre, m.especie, m.raza,
            s.nombre as servicio_nombre, s.duracion_min, s.precio_base,
            g.usuario_id as groomer_id, gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.id = ?";
    
    if ($user['rol'] == 'client') {
        $sql .= " AND c.cliente_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user['id']);
    } elseif ($user['rol'] == 'groo') {
        $sql .= " AND g.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user['id']);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($cita = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'cita' => $cita]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Cita no encontrada']);
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
 * Obtener citas por rango de fechas
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
 * Obtener próximas citas
 */
function getProximasCitas($conn, $user) {
    $hoy = date('Y-m-d');
    $limite = date('Y-m-d', strtotime('+30 days'));
    
    $sql = "SELECT c.*, 
            u.nombre as cliente_nombre, u.telefono as cliente_telefono,
            m.nombre as mascota_nombre, m.especie, m.raza,
            s.nombre as servicio_nombre, s.duracion_min, s.precio_base,
            gu.nombre as groomer_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.fecha BETWEEN ? AND ?
            AND c.estado NOT IN ('cancelada', 'completada')";
    
    $params = [$hoy, $limite];
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
    
    $sql .= " ORDER BY c.fecha, c.hora LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
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
 * Crear nueva cita
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
    
    $groomer_id = $data['groomer_id'] ?? null;
    
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
    
    $stmt = $conn->prepare("INSERT INTO citas (cliente_id, mascota_id, groomer_id, servicio_id, fecha, hora, estado, observaciones) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiisss", 
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

// Funciones auxiliares
function crearNotificacion($conn, $usuario_id, $titulo, $mensaje) {
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $usuario_id, $titulo, $mensaje);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// PROCESAR SOLICITUDES
// ============================================

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getCitaById($conn, $_GET['id'], $user);
        } elseif (isset($_GET['fecha'])) {
            getCitasByFecha($conn, $_GET['fecha'], $user);
        } elseif (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
            getCitasByRango($conn, $_GET['fecha_inicio'], $_GET['fecha_fin'], $user);
        } elseif (isset($_GET['semana']) && isset($_GET['year'])) {
            getCitasBySemana($conn, $_GET['semana'], $_GET['year'], $user);
        } else {
            getProximasCitas($conn, $user);
        }
        break;
        
    case 'POST':
        createCita($conn, $user);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

// ============================================
// VALIDACIÓN INTELIGENTE DE CITAS
// ============================================
function validarCitaInteligente($conn, $data) {
    require_once 'agenda_inteligente.php';
    
    // Reutilizar las funciones de validación
    if (esFeriado($data['fecha'], $conn)) {
        return ['success' => false, 'error' => 'El spa está cerrado por feriado'];
    }
    
    if (groomerAusente($data['groomer_id'], $data['fecha'], $conn)) {
        return ['success' => false, 'error' => 'El groomer no está disponible en esta fecha'];
    }
    
    // Obtener tamaño de la mascota
    $stmt = $conn->prepare("SELECT tamanio FROM mascotas WHERE id = ?");
    $stmt->bind_param("i", $data['mascota_id']);
    $stmt->execute();
    $mascota = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $duracion_info = calcularDuracionReal($data['servicio_id'], $mascota['tamanio'], $conn);
    
    $conflictos = verificarConflictos($conn, $data['groomer_id'], $data['fecha'], $data['hora'], $duracion_info['total_bloqueado']);
    
    if (count($conflictos) > 0) {
        return ['success' => false, 'error' => 'El groomer ya tiene una cita en ese horario'];
    }
    
    $capacidad = verificarCapacidadSpa($conn, $data['fecha'], $data['hora'], $duracion_info['total_bloqueado'], $data['groomer_id']);
    
    if (!$capacidad['disponible']) {
        return ['success' => false, 'error' => 'Capacidad del spa al máximo'];
    }
    
    return ['success' => true, 'duracion_calculada' => $duracion_info['total_bloqueado']];
}

?>