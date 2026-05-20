<?php
require_once 'config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    errorResponse('No autorizado', 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$action = $_GET['action'] ?? 'principal';

switch($action) {
    case 'principal':
        // Dashboard principal completo
        getDashboardPrincipal($conn);
        break;
        
    case 'kpi':
        // Solo indicadores KPI (tarjetas)
        getDashboardKPI($conn);
        break;
        
    case 'citas_hoy':
        // Citas del día
        getCitasHoy($conn);
        break;
        
    case 'alertas':
        // Alertas y notificaciones
        getAlertas($conn);
        break;
        
    case 'graficos':
        // Datos para gráficos
        getDatosGraficos($conn, $_GET);
        break;
        
    default:
        errorResponse('Acción no válida', 400);
        break;
}

// ──────────────────────────────────────────────
// FUNCIONES DEL DASHBOARD
// ──────────────────────────────────────────────

/**
 * Dashboard principal completo
 */
function getDashboardPrincipal($conn) {
    $hoy = date('Y-m-d');
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    $inicio_mes = date('Y-m-01');
    
    $dashboard = [];
    
    // ============================================
    // 1. TARJETAS KPI (Indicadores clave)
    // ============================================
    
    // Ventas de hoy
    $sql = "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad 
            FROM ventas 
            WHERE DATE(fecha) = ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $dashboard['ventas_hoy'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Ventas de la semana (vs semana anterior)
    $semana_anterior_inicio = date('Y-m-d', strtotime('monday last week'));
    $semana_anterior_fin = date('Y-m-d', strtotime('sunday last week'));
    
    $sql = "SELECT COALESCE(SUM(total), 0) as total 
            FROM ventas 
            WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $inicio_semana, $hoy);
    $stmt->execute();
    $ventas_semana = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt->bind_param("ss", $semana_anterior_inicio, $semana_anterior_fin);
    $stmt->execute();
    $ventas_semana_anterior = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $variacion_semana = 0;
    if ($ventas_semana_anterior > 0) {
        $variacion_semana = round((($ventas_semana - $ventas_semana_anterior) / $ventas_semana_anterior) * 100, 1);
    }
    
    $dashboard['ventas_semana'] = [
        'total' => $ventas_semana,
        'variacion' => $variacion_semana,
        'tendencia' => $variacion_semana >= 0 ? 'up' : 'down'
    ];
    
    // Ventas del mes
    $sql = "SELECT COALESCE(SUM(total), 0) as total 
            FROM ventas 
            WHERE DATE(fecha) >= ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_mes);
    $stmt->execute();
    $dashboard['ventas_mes'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // ============================================
    // 2. CITAS DEL DÍA
    // ============================================
    
    $sql = "SELECT c.*, 
            u.nombre as cliente_nombre,
            m.nombre as mascota_nombre,
            s.nombre as servicio_nombre,
            gu.nombre as groomer_nombre,
            TIME_FORMAT(c.hora, '%H:%i') as hora_formateada
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            LEFT JOIN groomers g ON c.groomer_id = g.id
            LEFT JOIN usuarios gu ON g.usuario_id = gu.id
            WHERE c.fecha = ?
            ORDER BY c.hora";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dashboard['citas_hoy'] = [];
    $dashboard['estadisticas_citas'] = [
        'total' => 0,
        'pendientes' => 0,
        'confirmadas' => 0,
        'en_progreso' => 0,
        'completadas' => 0,
        'canceladas' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $dashboard['citas_hoy'][] = $row;
        $dashboard['estadisticas_citas']['total']++;
        $dashboard['estadisticas_citas'][$row['estado']]++;
    }
    $stmt->close();
    
    // ============================================
    // 3. OCUPACIÓN DE GROOMERS
    // ============================================
    
    $sql = "SELECT gu.id, gu.nombre,
            COUNT(c.id) as citas_hoy,
            g.capacidad_diaria as capacidad_maxima,
            ROUND((COUNT(c.id) / g.capacidad_diaria) * 100, 1) as porcentaje_ocupacion
            FROM groomers g
            JOIN usuarios gu ON g.usuario_id = gu.id
            LEFT JOIN citas c ON g.id = c.groomer_id AND c.fecha = ? AND c.estado NOT IN ('cancelada')
            WHERE g.activo = 1
            GROUP BY g.id
            ORDER BY porcentaje_ocupacion DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dashboard['ocupacion_groomers'] = [];
    while ($row = $result->fetch_assoc()) {
        $dashboard['ocupacion_groomers'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // 4. PRODUCTOS CON STOCK BAJO (Alertas)
    // ============================================
    
    $sql = "SELECT id, nombre, variante, emoji, stock, stock_minimo,
            (stock_minimo - stock) as faltante
            FROM productos
            WHERE activo = 1 AND stock <= stock_minimo
            ORDER BY (stock_minimo - stock) DESC
            LIMIT 5";
    $result = $conn->query($sql);
    
    $dashboard['stock_bajo'] = [];
    while ($row = $result->fetch_assoc()) {
        $row['nivel'] = $row['stock'] <= 0 ? 'critico' : ($row['stock'] <= $row['stock_minimo'] / 2 ? 'urgente' : 'bajo');
        $dashboard['stock_bajo'][] = $row;
    }
    
    // ============================================
    // 5. CLIENTES NUEVOS
    // ============================================
    
    $sql = "SELECT id, nombre, email, telefono, DATE(fecha_registro) as fecha
            FROM usuarios
            WHERE rol = 'client' AND DATE(fecha_registro) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY fecha_registro DESC
            LIMIT 5";
    $result = $conn->query($sql);
    
    $dashboard['clientes_nuevos'] = [];
    while ($row = $result->fetch_assoc()) {
        $dashboard['clientes_nuevos'][] = $row;
    }
    
    // ============================================
    // 6. PRÓXIMAS CITAS (siguientes 3 días)
    // ============================================
    
    $sql = "SELECT c.fecha, c.hora, 
            u.nombre as cliente_nombre,
            m.nombre as mascota_nombre,
            s.nombre as servicio_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            JOIN servicios s ON c.servicio_id = s.id
            WHERE c.fecha > ? AND c.fecha <= DATE_ADD(?, INTERVAL 3 DAY)
            AND c.estado IN ('pendiente', 'confirmada')
            ORDER BY c.fecha, c.hora
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hoy, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dashboard['proximas_citas'] = [];
    while ($row = $result->fetch_assoc()) {
        $dashboard['proximas_citas'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // 7. NOTIFICACIONES NO LEÍDAS
    // ============================================
    
    $usuario_id = getCurrentUser()['id'];
    $sql = "SELECT id, titulo, mensaje, fecha
            FROM notificaciones
            WHERE usuario_id = ? AND leida = 0
            ORDER BY fecha DESC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dashboard['notificaciones'] = [];
    while ($row = $result->fetch_assoc()) {
        $dashboard['notificaciones'][] = $row;
    }
    $stmt->close();
    
    $dashboard['total_notificaciones'] = count($dashboard['notificaciones']);
    
    // ============================================
    // 8. DATOS PARA GRÁFICOS (ventas últimos 7 días)
    // ============================================
    
    $sql = "SELECT DATE(fecha) as dia, COALESCE(SUM(total), 0) as total
            FROM ventas
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            AND estado = 'pagado'
            GROUP BY DATE(fecha)
            ORDER BY dia";
    $result = $conn->query($sql);
    
    $dashboard['ventas_7dias'] = [];
    $ventas_por_dia = [];
    while ($row = $result->fetch_assoc()) {
        $ventas_por_dia[$row['dia']] = $row['total'];
    }
    
    // Completar días sin ventas
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $dashboard['ventas_7dias'][] = [
            'fecha' => $fecha,
            'dia' => date('d/m', strtotime($fecha)),
            'total' => $ventas_por_dia[$fecha] ?? 0
        ];
    }
    
    // ============================================
    // 9. SERVICIOS MÁS POPULARES
    // ============================================
    
    $sql = "SELECT s.nombre, COUNT(c.id) as total
            FROM servicios s
            JOIN citas c ON s.id = c.servicio_id
            WHERE c.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND c.estado = 'completada'
            GROUP BY s.id
            ORDER BY total DESC
            LIMIT 5";
    $result = $conn->query($sql);
    
    $dashboard['servicios_top'] = [];
    while ($row = $result->fetch_assoc()) {
        $dashboard['servicios_top'][] = $row;
    }
    
    // ============================================
    // 10. HORARIO MÁS SOLICITADO
    // ============================================
    
    $sql = "SELECT HOUR(hora) as hora, COUNT(*) as total
            FROM citas
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY HOUR(hora)
            ORDER BY total DESC
            LIMIT 3";
    $result = $conn->query($sql);
    
    $dashboard['horarios_populares'] = [];
    while ($row = $result->fetch_assoc()) {
        $dashboard['horarios_populares'][] = [
            'hora' => sprintf("%02d:00", $row['hora']),
            'total' => $row['total']
        ];
    }
    
    successResponse(['dashboard' => $dashboard]);
}

/**
 * Solo indicadores KPI (para actualización rápida)
 */
function getDashboardKPI($conn) {
    $hoy = date('Y-m-d');
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    $inicio_mes = date('Y-m-01');
    
    $kpi = [];
    
    // Ventas hoy
    $sql = "SELECT COALESCE(SUM(total), 0) as total 
            FROM ventas 
            WHERE DATE(fecha) = ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $kpi['ventas_hoy'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Citas hoy
    $sql = "SELECT COUNT(*) as total 
            FROM citas 
            WHERE fecha = ? AND estado NOT IN ('cancelada')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $kpi['citas_hoy'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Citas completadas hoy
    $sql = "SELECT COUNT(*) as total 
            FROM citas 
            WHERE fecha = ? AND estado = 'completada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $kpi['citas_completadas_hoy'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Clientes nuevos (este mes)
    $sql = "SELECT COUNT(*) as total 
            FROM usuarios 
            WHERE rol = 'client' AND DATE(fecha_registro) >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_mes);
    $stmt->execute();
    $kpi['clientes_nuevos_mes'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Productos con stock bajo
    $sql = "SELECT COUNT(*) as total 
            FROM productos 
            WHERE activo = 1 AND stock <= stock_minimo";
    $result = $conn->query($sql);
    $kpi['stock_bajo'] = $result->fetch_assoc()['total'];
    
    // Tasa de ocupación promedio de groomers
    $sql = "SELECT ROUND(AVG(citas_por_dia), 1) as promedio
            FROM (
                SELECT g.id, COUNT(c.id) as citas_por_dia
                FROM groomers g
                LEFT JOIN citas c ON g.id = c.groomer_id AND c.fecha = ?
                GROUP BY g.id
            ) as ocupacion";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    $kpi['ocupacion_promedio'] = $result->fetch_assoc()['promedio'] ?? 0;
    $stmt->close();
    
    successResponse(['kpi' => $kpi]);
}

/**
 * Obtener citas del día (para refresco automático)
 */
function getCitasHoy($conn) {
    $hoy = date('Y-m-d');
    
    $sql = "SELECT c.id, c.hora, c.estado,
            TIME_FORMAT(c.hora, '%H:%i') as hora_formateada,
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
            WHERE c.fecha = ?
            ORDER BY c.hora";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
    $stmt->close();
    
    successResponse(['citas' => $citas]);
}

/**
 * Obtener alertas y notificaciones
 */
function getAlertas($conn) {
    $alertas = [];
    
    // ============================================
    // 1. Stock bajo
    // ============================================
    
    $sql = "SELECT id, nombre, variante, stock, stock_minimo
            FROM productos
            WHERE activo = 1 AND stock <= stock_minimo
            ORDER BY (stock_minimo - stock) DESC
            LIMIT 5";
    $result = $conn->query($sql);
    
    $alertas['stock_bajo'] = [];
    while ($row = $result->fetch_assoc()) {
        $alertas['stock_bajo'][] = $row;
    }
    
    // ============================================
    // 2. Citas próximas (recordatorios)
    // ============================================
    
    $manana = date('Y-m-d', strtotime('+1 day'));
    $sql = "SELECT c.fecha, c.hora, 
            u.nombre as cliente_nombre, u.telefono as cliente_telefono,
            m.nombre as mascota_nombre
            FROM citas c
            JOIN usuarios u ON c.cliente_id = u.id
            JOIN mascotas m ON c.mascota_id = m.id
            WHERE c.fecha = ? AND c.estado = 'confirmada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $manana);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alertas['recordatorios'] = [];
    while ($row = $result->fetch_assoc()) {
        $alertas['recordatorios'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // 3. Clientes inactivos (más de 30 días sin cita)
    // ============================================
    
    $sql = "SELECT u.id, u.nombre, u.telefono,
            MAX(c.fecha) as ultima_visita,
            DATEDIFF(CURDATE(), MAX(c.fecha)) as dias_inactivo
            FROM usuarios u
            LEFT JOIN citas c ON u.id = c.cliente_id AND c.estado = 'completada'
            WHERE u.rol = 'client' AND u.activo = 1
            GROUP BY u.id
            HAVING ultima_visita IS NULL OR dias_inactivo > 30
            ORDER BY dias_inactivo DESC
            LIMIT 5";
    $result = $conn->query($sql);
    
    $alertas['clientes_inactivos'] = [];
    while ($row = $result->fetch_assoc()) {
        $alertas['clientes_inactivos'][] = $row;
    }
    
    // ============================================
    // 4. Notificaciones del sistema
    // ============================================
    
    $usuario_id = getCurrentUser()['id'];
    $sql = "SELECT id, titulo, mensaje, fecha
            FROM notificaciones
            WHERE usuario_id = ? AND leida = 0
            ORDER BY fecha DESC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alertas['notificaciones'] = [];
    while ($row = $result->fetch_assoc()) {
        $alertas['notificaciones'][] = $row;
    }
    $stmt->close();
    
    successResponse(['alertas' => $alertas]);
}

/**
 * Obtener datos para gráficos
 */
function getDatosGraficos($conn, $params) {
    $tipo = $params['tipo'] ?? 'ventas';
    $dias = isset($params['dias']) ? (int)$params['dias'] : 30;
    
    $datos = [];
    
    if ($tipo == 'ventas') {
        // Datos de ventas para gráfico de líneas
        $sql = "SELECT DATE(fecha) as fecha, 
                COALESCE(SUM(total), 0) as total,
                COUNT(*) as cantidad
                FROM ventas
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND estado = 'pagado'
                GROUP BY DATE(fecha)
                ORDER BY fecha";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ventas_por_dia = [];
        while ($row = $result->fetch_assoc()) {
            $ventas_por_dia[$row['fecha']] = $row;
        }
        $stmt->close();
        
        // Completar días sin datos
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $datos[] = [
                'fecha' => $fecha,
                'dia' => date('d/m', strtotime($fecha)),
                'total' => $ventas_por_dia[$fecha]['total'] ?? 0,
                'cantidad' => $ventas_por_dia[$fecha]['cantidad'] ?? 0
            ];
        }
        
    } elseif ($tipo == 'citas') {
        // Datos de citas para gráfico de barras
        $sql = "SELECT DATE(fecha) as fecha,
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
                FROM citas
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(fecha)
                ORDER BY fecha";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
        $stmt->close();
        
    } elseif ($tipo == 'servicios') {
        // Top servicios para gráfico de torta
        $sql = "SELECT s.nombre, COUNT(c.id) as total
                FROM servicios s
                JOIN citas c ON s.id = c.servicio_id
                WHERE c.fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND c.estado = 'completada'
                GROUP BY s.id
                ORDER BY total DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
        $stmt->close();
    }
    
    successResponse(['datos' => $datos, 'tipo' => $tipo, 'dias' => $dias]);
}

// ============================================
// ENDPOINT ADICIONAL: Marcar notificación como leída
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'marcar_leida') {
    if (!isAuthenticated()) {
        errorResponse('No autorizado', 401);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['notificacion_id'])) {
        errorResponse('ID de notificación requerido');
        exit;
    }
    
    $usuario_id = getCurrentUser()['id'];
    
    $stmt = $conn->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $data['notificacion_id'], $usuario_id);
    
    if ($stmt->execute()) {
        successResponse([], 'Notificación marcada como leída');
    } else {
        errorResponse('Error al marcar notificación');
    }
    $stmt->close();
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'kpi') {
    $hoy = date('Y-m-d');
    
    // Ventas de hoy
    $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(fecha) = '$hoy' AND estado = 'pagado'");
    $ventas_hoy = $result->fetch_assoc()['total'];
    
    // Citas de hoy
    $result = $conn->query("SELECT COUNT(*) as total FROM citas WHERE fecha = '$hoy'");
    $citas_hoy = $result->fetch_assoc()['total'];
    
    // Clientes nuevos este mes
    $result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'client' AND MONTH(fecha_registro) = MONTH(CURDATE())");
    $clientes_nuevos = $result->fetch_assoc()['total'];
    
    // Productos con stock bajo
    $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock <= stock_minimo");
    $stock_bajo = $result->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'kpi' => [
            'ventas_hoy' => $ventas_hoy,
            'citas_hoy' => $citas_hoy,
            'clientes_nuevos_mes' => $clientes_nuevos,
            'stock_bajo' => $stock_bajo
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>