<?php
require_once 'config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    errorResponse('No autorizado', 401);
}

// Verificar roles (solo admin y recepción pueden ver reportes)
if (!hasRole(['admin', 'recep'])) {
    errorResponse('No tienes permiso para ver reportes', 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$action = $_GET['action'] ?? 'dashboard';

switch($action) {
    case 'dashboard':
        // Reporte del dashboard principal
        getDashboardReport($conn);
        break;
        
    case 'ventas':
        // Reporte de ventas
        getVentasReport($conn, $_GET);
        break;
        
    case 'citas':
        // Reporte de citas
        getCitasReport($conn, $_GET);
        break;
        
    case 'clientes':
        // Reporte de clientes
        getClientesReport($conn, $_GET);
        break;
        
    case 'inventario':
        // Reporte de inventario
        getInventarioReport($conn, $_GET);
        break;
        
    case 'groomers':
        // Reporte de groomers
        getGroomersReport($conn, $_GET);
        break;
        
    case 'financiero':
        // Reporte financiero
        getFinancieroReport($conn, $_GET);
        break;
        
    case 'exportar':
        // Exportar reporte a CSV/Excel
        exportarReporte($conn, $_GET);
        break;
        
    default:
        errorResponse('Acción no válida', 400);
        break;
}

// ──────────────────────────────────────────────
// REPORTES PRINCIPALES
// ──────────────────────────────────────────────

/**
 * Reporte del dashboard principal
 */
function getDashboardReport($conn) {
    $hoy = date('Y-m-d');
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    $inicio_mes = date('Y-m-01');
    $inicio_ano = date('Y-01-01');
    
    $reporte = [];
    
    // ============================================
    // VENTAS
    // ============================================
    
    // Ventas de hoy
    $sql = "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad 
            FROM ventas 
            WHERE DATE(fecha) = ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['ventas_hoy'] = $result->fetch_assoc();
    $stmt->close();
    
    // Ventas de la semana
    $sql = "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad 
            FROM ventas 
            WHERE DATE(fecha) >= ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_semana);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['ventas_semana'] = $result->fetch_assoc();
    $stmt->close();
    
    // Ventas del mes
    $sql = "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad 
            FROM ventas 
            WHERE DATE(fecha) >= ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_mes);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['ventas_mes'] = $result->fetch_assoc();
    $stmt->close();
    
    // Ventas del año
    $sql = "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad 
            FROM ventas 
            WHERE DATE(fecha) >= ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_ano);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['ventas_ano'] = $result->fetch_assoc();
    $stmt->close();
    
    // ============================================
    // CITAS
    // ============================================
    
    // Citas de hoy
    $sql = "SELECT COUNT(*) as total,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            FROM citas WHERE fecha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['citas_hoy'] = $result->fetch_assoc();
    $stmt->close();
    
    // Citas de la semana
    $sql = "SELECT COUNT(*) as total,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            FROM citas WHERE fecha >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_semana);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['citas_semana'] = $result->fetch_assoc();
    $stmt->close();
    
    // ============================================
    // CLIENTES
    // ============================================
    
    // Total de clientes
    $result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'client' AND activo = 1");
    $reporte['total_clientes'] = $result->fetch_assoc()['total'];
    
    // Clientes nuevos este mes
    $sql = "SELECT COUNT(*) as total FROM usuarios 
            WHERE rol = 'client' AND DATE(fecha_registro) >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_mes);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['clientes_nuevos_mes'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Clientes recurrentes (más de 3 visitas)
    $sql = "SELECT COUNT(DISTINCT cliente_id) as total 
            FROM citas 
            WHERE estado = 'completada' 
            GROUP BY cliente_id 
            HAVING COUNT(*) >= 3";
    $result = $conn->query($sql);
    $reporte['clientes_recurrentes'] = $result->num_rows;
    
    // ============================================
    // PRODUCTOS
    // ============================================
    
    // Productos más vendidos
    $sql = "SELECT p.id, p.nombre, p.variante, p.emoji, 
            COALESCE(SUM(vp.cantidad), 0) as total_vendidos,
            COALESCE(SUM(vp.cantidad * vp.precio_unitario), 0) as total_ingresos
            FROM productos p
            LEFT JOIN venta_productos vp ON p.id = vp.producto_id
            LEFT JOIN ventas v ON vp.venta_id = v.id AND v.estado = 'pagado'
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY total_vendidos DESC
            LIMIT 5";
    $result = $conn->query($sql);
    $reporte['top_productos'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['top_productos'][] = $row;
    }
    
    // Stock bajo
    $sql = "SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock <= stock_minimo";
    $result = $conn->query($sql);
    $reporte['productos_stock_bajo'] = $result->fetch_assoc()['total'];
    
    // ============================================
    // GROOMERS
    // ============================================
    
    // Rendimiento de groomers
    $sql = "SELECT gu.nombre, 
            COUNT(c.id) as total_citas,
            SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) as citas_completadas,
            ROUND(AVG(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) * 100, 2) as tasa_completacion
            FROM groomers g
            JOIN usuarios gu ON g.usuario_id = gu.id
            LEFT JOIN citas c ON g.id = c.groomer_id AND DATE(c.fecha) >= ?
            GROUP BY g.id
            ORDER BY citas_completadas DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inicio_mes);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['top_groomers'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['top_groomers'][] = $row;
    }
    $stmt->close();
    
    successResponse(['reporte' => $reporte]);
}

/**
 * Reporte de ventas detallado
 */
function getVentasReport($conn, $params) {
    $periodo = $params['periodo'] ?? 'mes';
    $fecha_inicio = $params['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $params['fecha_fin'] ?? date('Y-m-t');
    
    $reporte = [];
    
    // ============================================
    // VENTAS POR PERIODO
    // ============================================
    
    if ($periodo == 'dia') {
        // Ventas por día del mes
        $sql = "SELECT DAY(fecha) as dia, 
                COALESCE(SUM(total), 0) as total,
                COUNT(*) as cantidad
                FROM ventas
                WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'
                GROUP BY DAY(fecha)
                ORDER BY dia";
    } elseif ($periodo == 'semana') {
        // Ventas por semana
        $sql = "SELECT WEEK(fecha) as semana,
                DATE_FORMAT(fecha, '%d/%m') as fecha_inicio,
                COALESCE(SUM(total), 0) as total,
                COUNT(*) as cantidad
                FROM ventas
                WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'
                GROUP BY WEEK(fecha)
                ORDER BY semana";
    } elseif ($periodo == 'mes') {
        // Ventas por mes
        $sql = "SELECT MONTH(fecha) as mes,
                DATE_FORMAT(fecha, '%M') as nombre_mes,
                COALESCE(SUM(total), 0) as total,
                COUNT(*) as cantidad
                FROM ventas
                WHERE YEAR(fecha) = YEAR(CURDATE()) AND estado = 'pagado'
                GROUP BY MONTH(fecha)
                ORDER BY mes";
    } else {
        // Ventas por rango de fechas
        $sql = "SELECT fecha,
                COALESCE(SUM(total), 0) as total,
                COUNT(*) as cantidad
                FROM ventas
                WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'
                GROUP BY DATE(fecha)
                ORDER BY fecha";
    }
    
    $stmt = $conn->prepare($sql);
    if ($periodo == 'dia' || $periodo == 'semana' || $periodo == 'rango') {
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    } elseif ($periodo == 'mes') {
        // No necesita bind params
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['ventas_por_periodo'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['ventas_por_periodo'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // VENTAS POR MÉTODO DE PAGO
    // ============================================
    
    $sql = "SELECT metodo_pago,
            COUNT(*) as cantidad,
            COALESCE(SUM(total), 0) as total
            FROM ventas
            WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'
            GROUP BY metodo_pago";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['ventas_por_metodo'] = [];
    while ($row = $result->fetch_assoc()) {
        $row['porcentaje'] = 0; // Se calculará después
        $reporte['ventas_por_metodo'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // VENTAS POR CATEGORÍA
    // ============================================
    
    $sql = "SELECT p.categoria,
            COALESCE(SUM(vp.cantidad), 0) as cantidad_vendida,
            COALESCE(SUM(vp.cantidad * vp.precio_unitario), 0) as total_ingresos
            FROM productos p
            LEFT JOIN venta_productos vp ON p.id = vp.producto_id
            LEFT JOIN ventas v ON vp.venta_id = v.id 
            WHERE v.estado = 'pagado' AND DATE(v.fecha) BETWEEN ? AND ?
            GROUP BY p.categoria
            ORDER BY total_ingresos DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['ventas_por_categoria'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['ventas_por_categoria'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // TICKET PROMEDIO
    // ============================================
    
    $sql = "SELECT AVG(total) as promedio,
            MIN(total) as minimo,
            MAX(total) as maximo
            FROM ventas
            WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['ticket_promedio'] = $result->fetch_assoc();
    $stmt->close();
    
    // ============================================
    // COMPARATIVA CON PERÍODO ANTERIOR
    // ============================================
    
    $diff_days = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / (60 * 60 * 24);
    $periodo_anterior_inicio = date('Y-m-d', strtotime($fecha_inicio . " - {$diff_days} days"));
    $periodo_anterior_fin = date('Y-m-d', strtotime($fecha_fin . " - {$diff_days} days"));
    
    $sql = "SELECT COALESCE(SUM(total), 0) as total
            FROM ventas
            WHERE DATE(fecha) BETWEEN ? AND ? AND estado = 'pagado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $periodo_anterior_inicio, $periodo_anterior_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    $ventas_anterior = $result->fetch_assoc();
    $stmt->close();
    
    $reporte['comparativa'] = [
        'periodo_actual' => $reporte['ventas_por_periodo'] ? array_sum(array_column($reporte['ventas_por_periodo'], 'total')) : 0,
        'periodo_anterior' => $ventas_anterior['total'],
        'variacion' => 0
    ];
    
    if ($reporte['comparativa']['periodo_anterior'] > 0) {
        $reporte['comparativa']['variacion'] = round(
            (($reporte['comparativa']['periodo_actual'] - $reporte['comparativa']['periodo_anterior']) / 
            $reporte['comparativa']['periodo_anterior']) * 100, 2
        );
    }
    
    successResponse(['reporte' => $reporte, 'filtros' => compact('periodo', 'fecha_inicio', 'fecha_fin')]);
}

/**
 * Reporte de citas
 */
function getCitasReport($conn, $params) {
    $periodo = $params['periodo'] ?? 'mes';
    $fecha_inicio = $params['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $params['fecha_fin'] ?? date('Y-m-t');
    
    $reporte = [];
    
    // ============================================
    // CITAS POR ESTADO
    // ============================================
    
    $sql = "SELECT estado, COUNT(*) as total
            FROM citas
            WHERE fecha BETWEEN ? AND ?
            GROUP BY estado";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['citas_por_estado'] = [];
    $total_citas = 0;
    while ($row = $result->fetch_assoc()) {
        $reporte['citas_por_estado'][] = $row;
        $total_citas += $row['total'];
    }
    $stmt->close();
    
    // Calcular porcentajes
    foreach ($reporte['citas_por_estado'] as &$estado) {
        $estado['porcentaje'] = $total_citas > 0 ? round(($estado['total'] / $total_citas) * 100, 2) : 0;
    }
    
    // ============================================
    // CITAS POR SERVICIO
    // ============================================
    
    $sql = "SELECT s.nombre, COUNT(c.id) as total
            FROM citas c
            JOIN servicios s ON c.servicio_id = s.id
            WHERE c.fecha BETWEEN ? AND ?
            GROUP BY c.servicio_id
            ORDER BY total DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['citas_por_servicio'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['citas_por_servicio'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // CITAS POR HORA (distribución horaria)
    // ============================================
    
    $sql = "SELECT HOUR(hora) as hora, COUNT(*) as total
            FROM citas
            WHERE fecha BETWEEN ? AND ?
            GROUP BY HOUR(hora)
            ORDER BY hora";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['citas_por_hora'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['citas_por_hora'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // TASA DE CANCELACIÓN
    // ============================================
    
    $sql = "SELECT 
            COUNT(*) as total_citas,
            SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
            FROM citas
            WHERE fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    $reporte['tasa_cancelacion'] = [
        'total' => $stats['total_citas'],
        'canceladas' => $stats['canceladas'],
        'completadas' => $stats['completadas'],
        'porcentaje_cancelacion' => $stats['total_citas'] > 0 ? round(($stats['canceladas'] / $stats['total_citas']) * 100, 2) : 0,
        'porcentaje_completadas' => $stats['total_citas'] > 0 ? round(($stats['completadas'] / $stats['total_citas']) * 100, 2) : 0
    ];
    
    // ============================================
    // OCUPACIÓN DE GROOMERS
    // ============================================
    
    $sql = "SELECT gu.nombre,
            COUNT(c.id) as total_citas,
            SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            g.capacidad_diaria * DATEDIFF(?, ?) as capacidad_total
            FROM groomers g
            JOIN usuarios gu ON g.usuario_id = gu.id
            LEFT JOIN citas c ON g.id = c.groomer_id AND c.fecha BETWEEN ? AND ?
            GROUP BY g.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fecha_fin, $fecha_inicio, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['ocupacion_groomers'] = [];
    while ($row = $result->fetch_assoc()) {
        $row['tasa_ocupacion'] = $row['capacidad_total'] > 0 ? round(($row['total_citas'] / $row['capacidad_total']) * 100, 2) : 0;
        $reporte['ocupacion_groomers'][] = $row;
    }
    $stmt->close();
    
    successResponse(['reporte' => $reporte, 'filtros' => compact('periodo', 'fecha_inicio', 'fecha_fin')]);
}

/**
 * Reporte de clientes
 */
function getClientesReport($conn, $params) {
    $periodo = $params['periodo'] ?? 'mes';
    $fecha_inicio = $params['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $params['fecha_fin'] ?? date('Y-m-t');
    
    $reporte = [];
    
    // ============================================
    // CRECIMIENTO DE CLIENTES
    // ============================================
    
    // Clientes por mes
    $sql = "SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes,
            COUNT(*) as nuevos_clientes
            FROM usuarios
            WHERE rol = 'client' AND YEAR(fecha_registro) = YEAR(CURDATE())
            GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
            ORDER BY mes";
    $result = $conn->query($sql);
    $reporte['crecimiento_clientes'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['crecimiento_clientes'][] = $row;
    }
    
    // ============================================
    // CLIENTES POR NIVEL (puntos)
    // ============================================
    
    $sql = "SELECT 
            SUM(CASE WHEN puntos >= 1000 THEN 1 ELSE 0 END) as gold,
            SUM(CASE WHEN puntos >= 500 AND puntos < 1000 THEN 1 ELSE 0 END) as silver,
            SUM(CASE WHEN puntos < 500 THEN 1 ELSE 0 END) as bronze
            FROM usuarios
            WHERE rol = 'client' AND activo = 1";
    $result = $conn->query($sql);
    $reporte['clientes_por_nivel'] = $result->fetch_assoc();
    
    // ============================================
    // CLIENTES TOP (más gastos)
    // ============================================
    
    $sql = "SELECT u.id, u.nombre, u.telefono, u.puntos,
            COUNT(c.id) as total_citas,
            COALESCE(SUM(v.total), 0) as total_gastado
            FROM usuarios u
            LEFT JOIN citas c ON u.id = c.cliente_id AND c.estado = 'completada'
            LEFT JOIN ventas v ON u.id = v.cliente_id AND v.estado = 'pagado'
            WHERE u.rol = 'client'
            GROUP BY u.id
            ORDER BY total_gastado DESC
            LIMIT 10";
    $result = $conn->query($sql);
    $reporte['top_clientes_gastos'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['top_clientes_gastos'][] = $row;
    }
    
    // ============================================
    // CLIENTES INACTIVOS
    // ============================================
    
    $sql = "SELECT u.id, u.nombre, u.telefono, u.email,
            MAX(c.fecha) as ultima_visita,
            DATEDIFF(CURDATE(), MAX(c.fecha)) as dias_inactivo
            FROM usuarios u
            LEFT JOIN citas c ON u.id = c.cliente_id AND c.estado = 'completada'
            WHERE u.rol = 'client' AND u.activo = 1
            GROUP BY u.id
            HAVING ultima_visita IS NULL OR dias_inactivo > 30
            ORDER BY dias_inactivo DESC
            LIMIT 20";
    $result = $conn->query($sql);
    $reporte['clientes_inactivos'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['clientes_inactivos'][] = $row;
    }
    
    successResponse(['reporte' => $reporte, 'filtros' => compact('periodo', 'fecha_inicio', 'fecha_fin')]);
}

/**
 * Reporte de inventario
 */
function getInventarioReport($conn, $params) {
    $reporte = [];
    
    // ============================================
    // VALOR DEL INVENTARIO
    // ============================================
    
    $sql = "SELECT 
            SUM(stock * precio) as valor_total,
            SUM(stock * precio) as valor_venta,
            COUNT(*) as total_productos,
            SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as productos_stock_bajo,
            SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as productos_agotados
            FROM productos
            WHERE activo = 1";
    $result = $conn->query($sql);
    $reporte['resumen_inventario'] = $result->fetch_assoc();
    
    // ============================================
    // VALOR POR CATEGORÍA
    // ============================================
    
    $sql = "SELECT categoria,
            COUNT(*) as cantidad,
            SUM(stock) as total_unidades,
            SUM(stock * precio) as valor_total
            FROM productos
            WHERE activo = 1 AND categoria IS NOT NULL
            GROUP BY categoria
            ORDER BY valor_total DESC";
    $result = $conn->query($sql);
    $reporte['valor_por_categoria'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['valor_por_categoria'][] = $row;
    }
    
    // ============================================
    // MOVIMIENTOS DE INVENTARIO
    // ============================================
    
    $sql = "SELECT 
            DATE(fecha) as dia,
            SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
            SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END) as salidas
            FROM inventario_movimientos
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(fecha)
            ORDER BY dia DESC
            LIMIT 30";
    $result = $conn->query($sql);
    $reporte['movimientos_recientes'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['movimientos_recientes'][] = $row;
    }
    
    // ============================================
    // ROTACIÓN DE INVENTARIO
    // ============================================
    
    $sql = "SELECT p.nombre, p.variante,
            p.stock,
            COALESCE(SUM(vp.cantidad), 0) as vendidos_30dias,
            CASE 
                WHEN COALESCE(SUM(vp.cantidad), 0) > 0 
                THEN ROUND(p.stock / COALESCE(SUM(vp.cantidad), 1), 2)
                ELSE NULL
            END as meses_cobertura
            FROM productos p
            LEFT JOIN venta_productos vp ON p.id = vp.producto_id
            LEFT JOIN ventas v ON vp.venta_id = v.id AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY meses_cobertura ASC
            LIMIT 20";
    $result = $conn->query($sql);
    $reporte['rotacion_inventario'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['rotacion_inventario'][] = $row;
    }
    
    successResponse(['reporte' => $reporte]);
}

/**
 * Reporte de groomers
 */
function getGroomersReport($conn, $params) {
    $periodo = $params['periodo'] ?? 'mes';
    $fecha_inicio = $params['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $params['fecha_fin'] ?? date('Y-m-t');
    
    $reporte = [];
    
    // ============================================
    // RENDIMIENTO POR GROOMER
    // ============================================
    
    $sql = "SELECT gu.nombre,
            COUNT(c.id) as total_citas,
            SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN c.estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            ROUND(AVG(CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) * 100, 2) as tasa_checklist
            FROM groomers g
            JOIN usuarios gu ON g.usuario_id = gu.id
            LEFT JOIN citas c ON g.id = c.groomer_id AND c.fecha BETWEEN ? AND ?
            LEFT JOIN fichas_grooming f ON c.id = f.cita_id
            GROUP BY g.id
            ORDER BY completadas DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['rendimiento_groomers'] = [];
    while ($row = $result->fetch_assoc()) {
        $row['tasa_completacion'] = $row['total_citas'] > 0 ? round(($row['completadas'] / $row['total_citas']) * 100, 2) : 0;
        $reporte['rendimiento_groomers'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // SERVICIOS POR GROOMER
    // ============================================
    
    $sql = "SELECT gu.nombre as groomer, s.nombre as servicio, COUNT(c.id) as total
            FROM groomers g
            JOIN usuarios gu ON g.usuario_id = gu.id
            JOIN citas c ON g.id = c.groomer_id
            JOIN servicios s ON c.servicio_id = s.id
            WHERE c.fecha BETWEEN ? AND ? AND c.estado = 'completada'
            GROUP BY g.id, c.servicio_id
            ORDER BY groomer, total DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['servicios_por_groomer'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['servicios_por_groomer'][] = $row;
    }
    $stmt->close();
    
    successResponse(['reporte' => $reporte, 'filtros' => compact('periodo', 'fecha_inicio', 'fecha_fin')]);
}

/**
 * Reporte financiero
 */
function getFinancieroReport($conn, $params) {
    $periodo = $params['periodo'] ?? 'mes';
    $fecha_inicio = $params['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $params['fecha_fin'] ?? date('Y-m-t');
    
    $reporte = [];
    
    // ============================================
    // INGRESOS TOTALES
    // ============================================
    
    $sql = "SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'servicio' THEN monto ELSE 0 END), 0) as ingresos_servicios,
            COALESCE(SUM(CASE WHEN tipo = 'producto' THEN monto ELSE 0 END), 0) as ingresos_productos,
            COALESCE(SUM(monto), 0) as total_ingresos
            FROM (
                SELECT 'servicio' as tipo, v.total as monto
                FROM ventas v
                WHERE v.estado = 'pagado' AND DATE(v.fecha) BETWEEN ? AND ?
                UNION ALL
                SELECT 'producto' as tipo, vp.cantidad * vp.precio_unitario as monto
                FROM venta_productos vp
                JOIN ventas v ON vp.venta_id = v.id
                WHERE v.estado = 'pagado' AND DATE(v.fecha) BETWEEN ? AND ?
            ) as ingresos";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    $reporte['ingresos'] = $result->fetch_assoc();
    $stmt->close();
    
    // ============================================
    // INGRESOS POR DÍA
    // ============================================
    
    $sql = "SELECT DATE(fecha) as dia, SUM(total) as total
            FROM ventas
            WHERE estado = 'pagado' AND DATE(fecha) BETWEEN ? AND ?
            GROUP BY DATE(fecha)
            ORDER BY dia";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reporte['ingresos_por_dia'] = [];
    while ($row = $result->fetch_assoc()) {
        $reporte['ingresos_por_dia'][] = $row;
    }
    $stmt->close();
    
    // ============================================
    // PROYECCIÓN MENSUAL
    // ============================================
    
    $dias_transcurridos = (strtotime(date('Y-m-d')) - strtotime($fecha_inicio)) / (60 * 60 * 24) + 1;
    $dias_totales = date('t');
    
    $ingreso_promedio_diario = $reporte['ingresos']['total_ingresos'] / $dias_transcurridos;
    $proyeccion_mensual = $ingreso_promedio_diario * $dias_totales;
    
    $reporte['proyeccion'] = [
        'ingreso_promedio_diario' => round($ingreso_promedio_diario, 2),
        'proyeccion_mensual' => round($proyeccion_mensual, 2),
        'meta_mensual' => 10000, // Meta configurable
        'cumplimiento_meta' => round(($proyeccion_mensual / 10000) * 100, 2)
    ];
    
    successResponse(['reporte' => $reporte, 'filtros' => compact('periodo', 'fecha_inicio', 'fecha_fin')]);
}

/**
 * Exportar reporte a CSV
 */
function exportarReporte($conn, $params) {
    $tipo = $params['tipo'] ?? 'ventas';
    $formato = $params['formato'] ?? 'csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Agregar BOM para UTF-8 en Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    switch($tipo) {
        case 'ventas':
            $sql = "SELECT v.id, v.fecha, u.nombre as cliente, v.total, v.metodo_pago, v.estado
                    FROM ventas v
                    LEFT JOIN usuarios u ON v.cliente_id = u.id
                    ORDER BY v.fecha DESC
                    LIMIT 1000";
            $result = $conn->query($sql);
            
            fputcsv($output, ['ID', 'Fecha', 'Cliente', 'Total', 'Método de Pago', 'Estado']);
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
            
        case 'citas':
            $sql = "SELECT c.id, c.fecha, c.hora, u.nombre as cliente, m.nombre as mascota, 
                    s.nombre as servicio, c.estado
                    FROM citas c
                    LEFT JOIN usuarios u ON c.cliente_id = u.id
                    LEFT JOIN mascotas m ON c.mascota_id = m.id
                    LEFT JOIN servicios s ON c.servicio_id = s.id
                    ORDER BY c.fecha DESC
                    LIMIT 1000";
            $result = $conn->query($sql);
            
            fputcsv($output, ['ID', 'Fecha', 'Hora', 'Cliente', 'Mascota', 'Servicio', 'Estado']);
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
            
        case 'inventario':
            $sql = "SELECT id, nombre, categoria, variante, stock, stock_minimo, precio
                    FROM productos
                    WHERE activo = 1
                    ORDER BY nombre";
            $result = $conn->query($sql);
            
            fputcsv($output, ['ID', 'Producto', 'Categoría', 'Variante', 'Stock', 'Stock Mínimo', 'Precio']);
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
            
        case 'clientes':
            $sql = "SELECT u.id, u.nombre, u.email, u.telefono, u.puntos, u.fecha_registro,
                    COUNT(c.id) as total_citas
                    FROM usuarios u
                    LEFT JOIN citas c ON u.id = c.cliente_id
                    WHERE u.rol = 'client'
                    GROUP BY u.id
                    ORDER BY u.nombre";
            $result = $conn->query($sql);
            
            fputcsv($output, ['ID', 'Nombre', 'Email', 'Teléfono', 'Puntos', 'Fecha Registro', 'Total Citas']);
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
    }
    
    fclose($output);
    exit();
}
?>