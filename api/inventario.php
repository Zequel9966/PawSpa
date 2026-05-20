<?php
require_once 'config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    errorResponse('No autorizado', 401);
}

// Verificar roles (solo admin y recepción pueden gestionar inventario)
if (!hasRole(['admin', 'recep'])) {
    errorResponse('No tienes permiso para gestionar inventario', 403);
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener un producto específico
            getProductoById($conn, $_GET['id']);
        } elseif (isset($_GET['alertas'])) {
            // Obtener productos con stock bajo
            getProductosStockBajo($conn);
        } elseif (isset($_GET['movimientos'])) {
            // Obtener movimientos de inventario
            getMovimientos($conn, $_GET);
        } elseif (isset($_GET['categorias'])) {
            // Obtener categorías
            getCategorias($conn);
        } elseif (isset($_GET['estadisticas'])) {
            // Obtener estadísticas de inventario
            getEstadisticasInventario($conn);
        } else {
            // Obtener todos los productos
            getAllProductos($conn, $_GET);
        }
        break;
        
    case 'POST':
        $action = $_GET['action'] ?? '';
        if ($action === 'movimiento') {
            // Registrar movimiento de inventario
            registrarMovimiento($conn);
        } elseif ($action === 'reposicion') {
            // Solicitar reposición de producto
            solicitarReposicion($conn);
        } else {
            // Crear nuevo producto
            createProducto($conn);
        }
        break;
        
    case 'PUT':
        $action = $_GET['action'] ?? '';
        if ($action === 'stock') {
            // Actualizar stock directamente
            actualizarStock($conn);
        } else {
            // Actualizar producto
            updateProducto($conn);
        }
        break;
        
    case 'DELETE':
        // Eliminar producto (soft delete o desactivar)
        deleteProducto($conn);
        break;
        
    default:
        errorResponse('Método no permitido', 405);
        break;
}

// ──────────────────────────────────────────────
// FUNCIONES PRINCIPALES
// ──────────────────────────────────────────────

/**
 * Obtener todos los productos con filtros
 */
function getAllProductos($conn, $params) {
    $sql = "SELECT p.*, 
            (SELECT SUM(cantidad) FROM inventario_movimientos WHERE producto_id = p.id AND tipo = 'entrada') as total_entradas,
            (SELECT SUM(cantidad) FROM inventario_movimientos WHERE producto_id = p.id AND tipo = 'salida') as total_salidas
            FROM productos p
            WHERE p.activo = 1";
    
    $filters = [];
    $types = "";
    $values = [];
    
    // Filtro por categoría
    if (isset($params['categoria']) && !empty($params['categoria'])) {
        $sql .= " AND p.categoria = ?";
        $filters[] = $params['categoria'];
        $types .= "s";
        $values[] = $params['categoria'];
    }
    
    // Filtro por stock bajo
    if (isset($params['stock_bajo']) && $params['stock_bajo'] == 1) {
        $sql .= " AND p.stock <= p.stock_minimo";
    }
    
    // Filtro por búsqueda
    if (isset($params['search']) && !empty($params['search'])) {
        $sql .= " AND (p.nombre LIKE ? OR p.variante LIKE ?)";
        $search = "%{$params['search']}%";
        $filters[] = $search;
        $filters[] = $search;
        $types .= "ss";
        $values[] = $search;
        $values[] = $search;
    }
    
    $sql .= " ORDER BY p.nombre, p.variante";
    
    $stmt = $conn->prepare($sql);
    if (!empty($values)) {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        // Calcular estado del stock
        $row['estado_stock'] = getStockStatus($row['stock'], $row['stock_minimo']);
        $productos[] = $row;
    }
    $stmt->close();
    
    successResponse(['productos' => $productos, 'total' => count($productos)]);
}

/**
 * Obtener producto por ID
 */
function getProductoById($conn, $id) {
    $sql = "SELECT p.*, 
            (SELECT SUM(cantidad) FROM inventario_movimientos WHERE producto_id = p.id AND tipo = 'entrada') as total_entradas,
            (SELECT SUM(cantidad) FROM inventario_movimientos WHERE producto_id = p.id AND tipo = 'salida') as total_salidas
            FROM productos p
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($producto = $result->fetch_assoc()) {
        // Obtener movimientos recientes del producto
        $producto['movimientos_recientes'] = getMovimientosProducto($conn, $id, 10);
        $producto['estado_stock'] = getStockStatus($producto['stock'], $producto['stock_minimo']);
        
        successResponse(['producto' => $producto]);
    } else {
        errorResponse('Producto no encontrado', 404);
    }
    $stmt->close();
}

/**
 * Obtener productos con stock bajo
 */
function getProductosStockBajo($conn) {
    $sql = "SELECT p.*, 
            (p.stock_minimo - p.stock) as faltante,
            CASE 
                WHEN p.stock <= 0 THEN 'crítico'
                WHEN p.stock <= p.stock_minimo/2 THEN 'urgente'
                ELSE 'bajo'
            END as nivel_alerta
            FROM productos p
            WHERE p.activo = 1 AND p.stock <= p.stock_minimo
            ORDER BY (p.stock_minimo - p.stock) DESC";
    
    $result = $conn->query($sql);
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    successResponse([
        'productos_stock_bajo' => $productos,
        'total' => count($productos)
    ]);
}

/**
 * Obtener todos los movimientos de inventario
 */
function getMovimientos($conn, $params) {
    $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
    $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
    
    $sql = "SELECT m.*, 
            p.nombre as producto_nombre, p.variante as producto_variante,
            u.nombre as usuario_nombre
            FROM inventario_movimientos m
            JOIN productos p ON m.producto_id = p.id
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            ORDER BY m.fecha DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $movimientos = [];
    while ($row = $result->fetch_assoc()) {
        $movimientos[] = $row;
    }
    $stmt->close();
    
    // Obtener total de movimientos
    $result = $conn->query("SELECT COUNT(*) as total FROM inventario_movimientos");
    $total = $result->fetch_assoc()['total'];
    
    successResponse([
        'movimientos' => $movimientos,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * Obtener movimientos de un producto específico
 */
function getMovimientosProducto($conn, $producto_id, $limit = 20) {
    $sql = "SELECT m.*, u.nombre as usuario_nombre
            FROM inventario_movimientos m
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.producto_id = ?
            ORDER BY m.fecha DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $producto_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $movimientos = [];
    while ($row = $result->fetch_assoc()) {
        $movimientos[] = $row;
    }
    $stmt->close();
    
    return $movimientos;
}

/**
 * Obtener categorías de productos
 */
function getCategorias($conn) {
    $sql = "SELECT DISTINCT categoria, COUNT(*) as total 
            FROM productos 
            WHERE activo = 1 AND categoria IS NOT NULL
            GROUP BY categoria
            ORDER BY categoria";
    
    $result = $conn->query($sql);
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    
    successResponse(['categorias' => $categorias]);
}

/**
 * Obtener estadísticas de inventario
 */
function getEstadisticasInventario($conn) {
    $stats = [];
    
    // Total de productos activos
    $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
    $stats['total_productos'] = $result->fetch_assoc()['total'];
    
    // Productos con stock bajo
    $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock <= stock_minimo");
    $stats['stock_bajo'] = $result->fetch_assoc()['total'];
    
    // Productos con stock crítico (0 o menos)
    $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND stock <= 0");
    $stats['stock_critico'] = $result->fetch_assoc()['total'];
    
    // Valor total del inventario
    $result = $conn->query("SELECT SUM(stock * precio) as total FROM productos WHERE activo = 1");
    $stats['valor_inventario'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Movimientos del día
    $result = $conn->query("SELECT COUNT(*) as total FROM inventario_movimientos WHERE DATE(fecha) = CURDATE()");
    $stats['movimientos_hoy'] = $result->fetch_assoc()['total'];
    
    // Movimientos del mes
    $result = $conn->query("SELECT COUNT(*) as total FROM inventario_movimientos WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())");
    $stats['movimientos_mes'] = $result->fetch_assoc()['total'];
    
    // Productos por categoría
    $result = $conn->query("SELECT categoria, COUNT(*) as total FROM productos WHERE activo = 1 GROUP BY categoria");
    $stats['por_categoria'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['por_categoria'][] = $row;
    }
    
    // Top productos más movidos (entradas + salidas)
    $sql = "SELECT p.id, p.nombre, p.variante, 
            SUM(CASE WHEN m.tipo = 'entrada' THEN m.cantidad ELSE 0 END) as total_entradas,
            SUM(CASE WHEN m.tipo = 'salida' THEN m.cantidad ELSE 0 END) as total_salidas,
            SUM(m.cantidad) as total_movimientos
            FROM productos p
            JOIN inventario_movimientos m ON p.id = m.producto_id
            WHERE m.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY total_movimientos DESC
            LIMIT 5";
    
    $result = $conn->query($sql);
    $stats['top_productos'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['top_productos'][] = $row;
    }
    
    successResponse(['estadisticas' => $stats]);
}

/**
 * Crear nuevo producto
 */
function createProducto($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    if (empty($data['nombre']) || !isset($data['precio'])) {
        errorResponse('Nombre y precio son requeridos');
        return;
    }
    
    $nombre = sanitizeInput($data['nombre']);
    $categoria = sanitizeInput($data['categoria'] ?? 'otros');
    $variante = sanitizeInput($data['variante'] ?? '');
    $emoji = sanitizeInput($data['emoji'] ?? '📦');
    $precio = floatval($data['precio']);
    $stock = intval($data['stock'] ?? 0);
    $stock_minimo = intval($data['stock_minimo'] ?? 5);
    $imagen_url = sanitizeInput($data['imagen_url'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO productos (nombre, categoria, variante, emoji, precio, stock, stock_minimo, imagen_url) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdiis", $nombre, $categoria, $variante, $emoji, $precio, $stock, $stock_minimo, $imagen_url);
    
    if ($stmt->execute()) {
        $producto_id = $conn->insert_id;
        
        // Registrar movimiento inicial si hay stock
        if ($stock > 0) {
            registrarMovimientoInterno($conn, $producto_id, 'entrada', $stock, 'Stock inicial', getCurrentUser()['id']);
        }
        
        systemLog('Producto creado', ['id' => $producto_id, 'nombre' => $nombre, 'stock' => $stock]);
        successResponse(['producto_id' => $producto_id], 'Producto creado exitosamente');
    } else {
        errorResponse('Error al crear producto: ' . $stmt->error);
    }
    $stmt->close();
}

/**
 * Actualizar producto
 */
function updateProducto($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        errorResponse('ID de producto requerido');
        return;
    }
    
    $updates = [];
    $params = [];
    $types = "";
    
    $allowedFields = ['nombre', 'categoria', 'variante', 'emoji', 'precio', 'stock_minimo', 'imagen_url'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = sanitizeInput($data[$field]);
            $types .= "s";
        }
    }
    
    // Precio es decimal
    if (isset($data['precio'])) {
        // Ya lo agregamos arriba, pero asegurar tipo
        $key = array_search('precio', $allowedFields);
        if ($key !== false) {
            $params[$key] = floatval($data['precio']);
        }
    }
    
    if (empty($updates)) {
        errorResponse('No hay campos para actualizar');
        return;
    }
    
    $params[] = $data['id'];
    $types .= "i";
    
    $sql = "UPDATE productos SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        systemLog('Producto actualizado', ['id' => $data['id'], 'campos' => array_keys($data)]);
        successResponse([], 'Producto actualizado exitosamente');
    } else {
        errorResponse('Error al actualizar producto: ' . $stmt->error);
    }
    $stmt->close();
}

/**
 * Actualizar stock directamente
 */
function actualizarStock($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id']) || !isset($data['stock'])) {
        errorResponse('ID de producto y nuevo stock son requeridos');
        return;
    }
    
    // Obtener stock actual
    $stmt = $conn->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    if (!$producto) {
        errorResponse('Producto no encontrado', 404);
        return;
    }
    
    $stock_anterior = $producto['stock'];
    $nuevo_stock = intval($data['stock']);
    $diferencia = $nuevo_stock - $stock_anterior;
    $motivo = $data['motivo'] ?? 'Ajuste manual de inventario';
    
    // Actualizar stock
    $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $nuevo_stock, $data['id']);
    
    if ($stmt->execute()) {
        // Registrar movimiento si hay diferencia
        if ($diferencia != 0) {
            $tipo = $diferencia > 0 ? 'entrada' : 'salida';
            registrarMovimientoInterno($conn, $data['id'], $tipo, abs($diferencia), $motivo, getCurrentUser()['id']);
        }
        
        systemLog('Stock actualizado', [
            'producto_id' => $data['id'],
            'producto' => $producto['nombre'],
            'stock_anterior' => $stock_anterior,
            'nuevo_stock' => $nuevo_stock
        ]);
        
        successResponse([
            'stock_anterior' => $stock_anterior,
            'nuevo_stock' => $nuevo_stock
        ], 'Stock actualizado exitosamente');
    } else {
        errorResponse('Error al actualizar stock: ' . $stmt->error);
    }
    $stmt->close();
}

/**
 * Registrar movimiento de inventario (desde API)
 */
function registrarMovimiento($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['producto_id']) || empty($data['tipo']) || empty($data['cantidad'])) {
        errorResponse('Producto, tipo y cantidad son requeridos');
        return;
    }
    
    $producto_id = intval($data['producto_id']);
    $tipo = $data['tipo'];
    $cantidad = intval($data['cantidad']);
    $motivo = sanitizeInput($data['motivo'] ?? '');
    
    if (!in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
        errorResponse('Tipo de movimiento inválido');
        return;
    }
    
    if ($cantidad <= 0) {
        errorResponse('La cantidad debe ser mayor a 0');
        return;
    }
    
    // Obtener stock actual del producto
    $stmt = $conn->prepare("SELECT stock, nombre FROM productos WHERE id = ? AND activo = 1");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    if (!$producto) {
        errorResponse('Producto no encontrado', 404);
        return;
    }
    
    // Verificar que no haya stock negativo
    if ($tipo == 'salida' && $producto['stock'] < $cantidad) {
        errorResponse('Stock insuficiente. Stock actual: ' . $producto['stock']);
        return;
    }
    
    // Actualizar stock
    $nuevo_stock = ($tipo == 'entrada') ? $producto['stock'] + $cantidad : $producto['stock'] - $cantidad;
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $nuevo_stock, $producto_id);
        $stmt->execute();
        $stmt->close();
        
        // Registrar movimiento
        $usuario_id = getCurrentUser()['id'];
        registrarMovimientoInterno($conn, $producto_id, $tipo, $cantidad, $motivo, $usuario_id);
        
        $conn->commit();
        
        systemLog('Movimiento registrado', [
            'producto' => $producto['nombre'],
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'motivo' => $motivo
        ]);
        
        successResponse([
            'stock_anterior' => $producto['stock'],
            'nuevo_stock' => $nuevo_stock
        ], 'Movimiento registrado exitosamente');
        
    } catch (Exception $e) {
        $conn->rollback();
        errorResponse('Error al registrar movimiento: ' . $e->getMessage());
    }
}

/**
 * Registrar movimiento internamente (función auxiliar)
 */
function registrarMovimientoInterno($conn, $producto_id, $tipo, $cantidad, $motivo, $usuario_id) {
    $stmt = $conn->prepare("INSERT INTO inventario_movimientos (producto_id, tipo, cantidad, motivo, usuario_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isisi", $producto_id, $tipo, $cantidad, $motivo, $usuario_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Solicitar reposición de producto (crea una notificación)
 */
function solicitarReposicion($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['producto_id'])) {
        errorResponse('ID de producto requerido');
        return;
    }
    
    $producto_id = intval($data['producto_id']);
    $cantidad_solicitada = intval($data['cantidad'] ?? 0);
    
    // Obtener información del producto
    $stmt = $conn->prepare("SELECT p.*, u.nombre as proveedor 
                            FROM productos p
                            LEFT JOIN usuarios u ON p.proveedor_id = u.id
                            WHERE p.id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    if (!$producto) {
        errorResponse('Producto no encontrado', 404);
        return;
    }
    
    $cantidad_recomendada = $cantidad_solicitada > 0 ? $cantidad_solicitada : ($producto['stock_minimo'] * 2);
    
    // Crear notificación para admin
    $mensaje = "Se requiere reposición de {$producto['nombre']} {$producto['variante']}. " .
               "Stock actual: {$producto['stock']}, " .
               "Stock mínimo: {$producto['stock_minimo']}. " .
               "Cantidad sugerida: {$cantidad_recomendada}";
    
    $stmt = $conn->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje) 
                            SELECT id, ?, ? FROM usuarios WHERE rol = 'admin'");
    $titulo = "⚠️ Reposición de inventario - " . $producto['nombre'];
    $stmt->bind_param("ss", $titulo, $mensaje);
    $stmt->execute();
    $stmt->close();
    
    systemLog('Reposición solicitada', [
        'producto_id' => $producto_id,
        'producto' => $producto['nombre'],
        'cantidad_sugerida' => $cantidad_recomendada
    ]);
    
    successResponse([
        'producto' => $producto['nombre'],
        'stock_actual' => $producto['stock'],
        'stock_minimo' => $producto['stock_minimo'],
        'cantidad_sugerida' => $cantidad_recomendada
    ], 'Solicitud de reposición enviada');
}

/**
 * Eliminar producto (soft delete)
 */
function deleteProducto($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        errorResponse('ID de producto requerido');
        return;
    }
    
    // Verificar si el producto tiene movimientos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM inventario_movimientos WHERE producto_id = ?");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $movimientos = $result->fetch_assoc()['total'];
    $stmt->close();
    
    if ($movimientos > 0) {
        // Si tiene movimientos, solo desactivar
        $stmt = $conn->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
    } else {
        // Si no tiene movimientos, eliminar físicamente
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
    }
    
    if ($stmt->execute()) {
        systemLog('Producto eliminado', ['producto_id' => $data['id']]);
        successResponse([], 'Producto eliminado exitosamente');
    } else {
        errorResponse('Error al eliminar producto: ' . $stmt->error);
    }
    $stmt->close();
}

/**
 * Obtener estado del stock
 */
function getStockStatus($stock, $stock_minimo) {
    if ($stock <= 0) {
        return ['texto' => 'Crítico', 'clase' => 'badge-red', 'icono' => '⚠️'];
    } elseif ($stock <= $stock_minimo) {
        return ['texto' => 'Stock bajo', 'clase' => 'badge-orange', 'icono' => '⚠️'];
    } elseif ($stock <= $stock_minimo * 2) {
        return ['texto' => 'Por debajo', 'clase' => 'badge-blue', 'icono' => '📉'];
    } else {
        return ['texto' => 'OK', 'clase' => 'badge-green', 'icono' => '✓'];
    }
}
?>