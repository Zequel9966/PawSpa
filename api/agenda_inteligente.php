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
// CONSTANTES DE CONFIGURACIÓN
// ============================================
define('TIEMPO_ENTRE_CITAS', 15); // minutos entre citas
define('HORARIO_INICIO', '09:00:00');
define('HORARIO_FIN', '18:00:00');

// Ajustes por tamaño de mascota
const AJUSTES_TAMANIO = [
    'pequeno' => 0,      // 0% adicional
    'mediano' => 10,     // +10%
    'grande' => 15,      // +15%
    'gigante' => 30      // +30%
];

// ============================================
// FUNCIÓN: Calcular duración real del servicio
// ============================================
function calcularDuracionReal($servicio_id, $mascota_tamanio, $conn) {
    // Obtener duración base del servicio
    $stmt = $conn->prepare("SELECT duracion_min FROM servicios WHERE id = ?");
    $stmt->bind_param("i", $servicio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servicio = $result->fetch_assoc();
    $stmt->close();
    
    $duracion_base = $servicio['duracion_min'] ?? 60;
    
    // Aplicar ajuste por tamaño
    $ajuste = AJUSTES_TAMANIO[$mascota_tamanio] ?? 0;
    $duracion_real = $duracion_base + ($duracion_base * $ajuste / 100);
    
    // Tiempo de limpieza entre citas
    $tiempo_limpieza = 15;
    
    return [
        'duracion_base' => $duracion_base,
        'duracion_real' => ceil($duracion_real),
        'tiempo_limpieza' => $tiempo_limpieza,
        'total_bloqueado' => ceil($duracion_real) + $tiempo_limpieza
    ];
}

// ============================================
// FUNCIÓN: Verificar si es feriado
// ============================================
function esFeriado($fecha, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM feriados WHERE fecha = ? AND activo = 1");
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $result = $stmt->get_result();
    $feriado = $result->fetch_assoc()['total'] > 0;
    $stmt->close();
    return $feriado;
}

// ============================================
// FUNCIÓN: Verificar si groomer está ausente
// ============================================
function groomerAusente($groomer_id, $fecha, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ausencias_groomers 
                            WHERE groomer_id = ? AND activo = 1 
                            AND fecha_inicio <= ? AND fecha_fin >= ?");
    $stmt->bind_param("iss", $groomer_id, $fecha, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();
    $ausente = $result->fetch_assoc()['total'] > 0;
    $stmt->close();
    return $ausente;
}

// ============================================
// FUNCIÓN: Verificar conflictos de horario
// ============================================
function verificarConflictos($conn, $groomer_id, $fecha, $hora_inicio, $duracion, $cita_id_excluir = null) {
    $hora_fin = date('H:i:s', strtotime($hora_inicio . " + {$duracion} minutes"));
    
    $sql = "SELECT c.id, c.hora, s.duracion_min,
            ADDTIME(c.hora, SEC_TO_TIME(s.duracion_min * 60)) as hora_fin
            FROM citas c
            JOIN servicios s ON c.servicio_id = s.id
            WHERE c.groomer_id = ? 
            AND c.fecha = ? 
            AND c.estado NOT IN ('cancelada')";
    
    if ($cita_id_excluir) {
        $sql .= " AND c.id != ?";
    }
    
    $sql .= " ORDER BY c.hora";
    
    if ($cita_id_excluir) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $groomer_id, $fecha, $cita_id_excluir);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $groomer_id, $fecha);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conflictos = [];
    $nueva_inicio = strtotime($hora_inicio);
    $nueva_fin = strtotime($hora_fin);
    
    while ($row = $result->fetch_assoc()) {
        $cita_inicio = strtotime($row['hora']);
        $cita_fin = strtotime($row['hora_fin']);
        
        // Verificar solapamiento
        if (($nueva_inicio >= $cita_inicio && $nueva_inicio < $cita_fin) ||
            ($nueva_fin > $cita_inicio && $nueva_fin <= $cita_fin) ||
            ($nueva_inicio <= $cita_inicio && $nueva_fin >= $cita_fin)) {
            $conflictos[] = $row;
        }
    }
    $stmt->close();
    
    return $conflictos;
}

// ============================================
// FUNCIÓN: Verificar capacidad del spa
// ============================================
function verificarCapacidadSpa($conn, $fecha, $hora_inicio, $duracion, $groomer_id_excluir = null) {
    $hora_fin = date('H:i:s', strtotime($hora_inicio . " + {$duracion} minutes"));
    
    $sql = "SELECT COUNT(DISTINCT c.groomer_id) as groomers_ocupados
            FROM citas c
            JOIN servicios s ON c.servicio_id = s.id
            WHERE c.fecha = ? 
            AND c.estado NOT IN ('cancelada')
            AND (
                (c.hora <= ? AND ADDTIME(c.hora, SEC_TO_TIME(s.duracion_min * 60)) > ?)
                OR (? < ADDTIME(c.hora, SEC_TO_TIME(s.duracion_min * 60)) AND ? >= c.hora)
            )";
    
    if ($groomer_id_excluir) {
        $sql .= " AND c.groomer_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $fecha, $hora_fin, $hora_inicio, $hora_fin, $hora_inicio, $groomer_id_excluir);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $fecha, $hora_fin, $hora_inicio, $hora_fin, $hora_inicio);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $ocupados = $result->fetch_assoc()['groomers_ocupados'];
    $stmt->close();
    
    // Obtener total de groomers activos
    $result = $conn->query("SELECT COUNT(*) as total FROM groomers WHERE activo = 1");
    $total_groomers = $result->fetch_assoc()['total'];
    
    return [
        'disponible' => $ocupados < $total_groomers,
        'ocupados' => $ocupados,
        'total' => $total_groomers,
        'capacidad_restante' => $total_groomers - $ocupados
    ];
}

// ============================================
// FUNCIÓN: Buscar slots disponibles
// ============================================
function buscarSlotsDisponibles($conn, $groomer_id, $fecha, $duracion, $tolerancia = 30) {
    $slots_disponibles = [];
    
    // Horario laboral del groomer
    $stmt = $conn->prepare("SELECT horario_inicio, horario_fin FROM groomers WHERE id = ?");
    $stmt->bind_param("i", $groomer_id);
    $stmt->execute();
    $groomer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $inicio_jornada = strtotime($groomer['horario_inicio']);
    $fin_jornada = strtotime($groomer['horario_fin']);
    
    // Obtener citas existentes
    $stmt = $conn->prepare("SELECT c.hora, s.duracion_min,
                            ADDTIME(c.hora, SEC_TO_TIME(s.duracion_min * 60)) as hora_fin
                            FROM citas c
                            JOIN servicios s ON c.servicio_id = s.id
                            WHERE c.groomer_id = ? AND c.fecha = ? 
                            AND c.estado NOT IN ('cancelada')
                            ORDER BY c.hora");
    $stmt->bind_param("is", $groomer_id, $fecha);
    $stmt->execute();
    $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Generar slots cada 30 minutos
    for ($time = $inicio_jornada; $time + ($duracion * 60) <= $fin_jornada; $time += 1800) {
        $hora_inicio = date('H:i:s', $time);
        $hora_fin = date('H:i:s', $time + ($duracion * 60));
        
        $conflicto = false;
        foreach ($citas as $cita) {
            $cita_inicio = strtotime($cita['hora']);
            $cita_fin = strtotime($cita['hora_fin']);
            
            if (($time >= $cita_inicio && $time < $cita_fin) ||
                ($time + ($duracion * 60) > $cita_inicio && $time + ($duracion * 60) <= $cita_fin)) {
                $conflicto = true;
                break;
            }
        }
        
        if (!$conflicto) {
            $slots_disponibles[] = [
                'hora_inicio' => date('H:i', $time),
                'hora_fin' => date('H:i', $time + ($duracion * 60)),
                'timestamp' => $time
            ];
        }
    }
    
    return $slots_disponibles;
}

// ============================================
// ENDPOINT: Validar disponibilidad
// ============================================
if ($method === 'POST' && $action === 'validar') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $groomer_id = intval($data['groomer_id'] ?? 0);
    $servicio_id = intval($data['servicio_id'] ?? 0);
    $mascota_tamanio = $data['mascota_tamanio'] ?? 'mediano';
    $fecha = $data['fecha'] ?? '';
    $hora = $data['hora'] ?? '';
    
    // Validar feriado
    if (esFeriado($fecha, $conn)) {
        echo json_encode([
            'success' => false,
            'error' => '❌ El spa está cerrado por feriado',
            'code' => 'FERIADO'
        ]);
        exit;
    }
    
    // Validar ausencia del groomer
    if (groomerAusente($groomer_id, $fecha, $conn)) {
        echo json_encode([
            'success' => false,
            'error' => '❌ El groomer no está disponible en esta fecha',
            'code' => 'AUSENCIA'
        ]);
        exit;
    }
    
    // Calcular duración real
    $duracion_info = calcularDuracionReal($servicio_id, $mascota_tamanio, $conn);
    
    // Verificar conflictos
    $conflictos = verificarConflictos($conn, $groomer_id, $fecha, $hora, $duracion_info['total_bloqueado']);
    
    if (count($conflictos) > 0) {
        echo json_encode([
            'success' => false,
            'error' => '❌ El groomer ya tiene una cita en ese horario',
            'code' => 'CONFLICTO',
            'conflictos' => $conflictos
        ]);
        exit;
    }
    
    // Verificar capacidad del spa
    $capacidad = verificarCapacidadSpa($conn, $fecha, $hora, $duracion_info['total_bloqueado'], $groomer_id);
    
    if (!$capacidad['disponible']) {
        echo json_encode([
            'success' => false,
            'error' => "❌ Capacidad del spa al máximo. {$capacidad['ocupados']} de {$capacidad['total']} groomers ocupados",
            'code' => 'CAPACIDAD'
        ]);
        exit;
    }
    
    // Buscar slots alternativos si es necesario
    $slots_alternativos = buscarSlotsDisponibles($conn, $groomer_id, $fecha, $duracion_info['total_bloqueado']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Horario disponible',
        'duracion_calculada' => $duracion_info,
        'capacidad' => $capacidad,
        'slots_alternativos' => $slots_alternativos
    ]);
    exit;
}

// ============================================
// ENDPOINT: Obtener feriados
// ============================================
if ($method === 'GET' && $action === 'feriados') {
    $result = $conn->query("SELECT fecha, nombre FROM feriados WHERE activo = 1 AND fecha >= CURDATE() ORDER BY fecha");
    $feriados = [];
    while ($row = $result->fetch_assoc()) {
        $feriados[] = $row;
    }
    echo json_encode(['success' => true, 'feriados' => $feriados]);
    exit;
}

// ============================================
// ENDPOINT: Obtener ausencias de groomer
// ============================================
if ($method === 'GET' && $action === 'ausencias') {
    $groomer_id = isset($_GET['groomer_id']) ? intval($_GET['groomer_id']) : 0;
    
    if ($groomer_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de groomer requerido']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT fecha_inicio, fecha_fin, motivo FROM ausencias_groomers 
                            WHERE groomer_id = ? AND activo = 1 AND fecha_fin >= CURDATE()");
    $stmt->bind_param("i", $groomer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ausencias = [];
    while ($row = $result->fetch_assoc()) {
        $ausencias[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'ausencias' => $ausencias]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida']);
?>