<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener un producto específico para la tienda
            getProductoTienda($conn, $_GET['id']);
        } elseif (isset($_GET['destacados'])) {
            // Obtener productos destacados
            getProductosDestacados($conn);
        } elseif (isset($_GET['ofertas'])) {
            // Obtener productos en oferta
            getProductosEnOferta($conn);
        } elseif (isset($_GET['categorias'])) {
            // Obtener categorías con conteo
            getCategoriasTienda($conn);
        } elseif (isset($_GET['carrito']) && isAuthenticated()) {
            // Obtener carrito del usuario
            getCarrito($conn);
        } elseif (isset($_GET['pedidos']) && isAuthenticated()) {
            // Obtener pedidos del usuario
            getPedidosCliente($conn);
        } elseif (isset($_GET['pedido_id'])) {
            // Obtener detalle de un pedido específico
            getPedidoDetalle($conn, $_GET['pedido_id']);
        } else {
            // Obtener todos los productos con filtros
            getAllProductosTienda($conn, $_GET);
        }
        break;
        
    case 'POST':
        $action = $_GET['action'] ?? '';
        if ($action === 'carrito') {
            // Agregar al carrito
            agregarAlCarrito($conn);
        } elseif ($action === 'checkout') {
            // Procesar pedido (checkout)
            procesarCheckout($conn);
        } elseif ($action === 'cupon') {
            // Validar cupón de descuento
            validarCupon($conn);
        } else {
            errorResponse('Acción no válida', 400);
        }
        break;
        
    case 'PUT':
        $action = $_GET['action'] ?? '';
        if ($action === 'carrito') {
            // Actualizar cantidad en carrito
            actualizarCarrito($conn);
        } else {
            errorResponse('Acción no válida', 400);
        }
        break;
        
    case 'DELETE':
        $action = $_GET['action'] ?? '';
        if ($action === 'carrito') {
            // Eliminar del carrito
            eliminarDelCarrito($conn);
        } else {
            errorResponse('Acción no válida', 400);
        }
        break;
        
    default:
        errorResponse('Método no permitido', 405);
        break;
}

// ──────────────────────────────────────────────
// FUNCIONES DE PRODUCTOS (TIENDA)
// ──────────────────────────────────────────────

/**
 * Obtener todos los productos de la tienda (solo activos, con stock)
 */
function getAllProductosTienda($conn, $params) {
    $sql = "SELECT p.id, p.nombre, p.categoria, p.variante, p.emoji, p.precio, p.stock, 
            p.imagen_url, p.destacado, p.oferta, p.precio_oferta,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN p.precio_oferta 
                ELSE p.precio 
            END as precio_actual,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN ROUND(((p.precio - p.precio_oferta) / p.precio) * 100, 0)
                ELSE 0
            END as descuento
            FROM productos p
            WHERE p.activo = 1 AND p.stock > 0";
    
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
    
    // Filtro por búsqueda
    if (isset($params['search']) && !empty($params['search'])) {
        $sql .= " AND (p.nombre LIKE ? OR p.variante LIKE ? OR p.categoria LIKE ?)";
        $search = "%{$params['search']}%";
        $filters[] = $search;
        $filters[] = $search;
        $filters[] = $search;
        $types .= "sss";
        $values[] = $search;
        $values[] = $search;
        $values[] = $search;
    }
    
    // Ordenar
    $order = isset($params['order']) ? $params['order'] : 'nombre';
    switch($order) {
        case 'precio_asc':
            $sql .= " ORDER BY precio_actual ASC";
            break;
        case 'precio_desc':
            $sql .= " ORDER BY precio_actual DESC";
            break;
        case 'popular':
            $sql .= " ORDER BY p.vendidos DESC";
            break;
        default:
            $sql .= " ORDER BY p.nombre, p.variante";
    }
    
    // Paginación
    $page = isset($params['page']) ? (int)$params['page'] : 1;
    $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT ? OFFSET ?";
    $values[] = $limit;
    $values[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!empty($values)) {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $row['stock_status'] = getStockStatusTienda($row['stock']);
        $row['precio_formateado'] = "Bs. " . number_format($row['precio_actual'], 2);
        $row['precio_original_formateado'] = $row['oferta'] ? "Bs. " . number_format($row['precio'], 2) : null;
        $productos[] = $row;
    }
    $stmt->close();
    
    // Obtener total de productos para la paginación
    $countSql = "SELECT COUNT(*) as total FROM productos p WHERE p.activo = 1 AND p.stock > 0";
    // Aplicar mismos filtros para el conteo
    if (isset($params['categoria']) && !empty($params['categoria'])) {
        $countSql .= " AND p.categoria = '{$params['categoria']}'";
    }
    if (isset($params['search']) && !empty($params['search'])) {
        $search = $conn->real_escape_string($params['search']);
        $countSql .= " AND (p.nombre LIKE '%{$search}%' OR p.variante LIKE '%{$search}%')";
    }
    $result = $conn->query($countSql);
    $total = $result->fetch_assoc()['total'];
    
    successResponse([
        'productos' => $productos,
        'paginacion' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Obtener un producto específico para la tienda
 */
function getProductoTienda($conn, $id) {
    $sql = "SELECT p.*,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN p.precio_oferta 
                ELSE p.precio 
            END as precio_actual,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN ROUND(((p.precio - p.precio_oferta) / p.precio) * 100, 0)
                ELSE 0
            END as descuento
            FROM productos p
            WHERE p.id = ? AND p.activo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($producto = $result->fetch_assoc()) {
        $producto['stock_status'] = getStockStatusTienda($producto['stock']);
        $producto['precio_formateado'] = "Bs. " . number_format($producto['precio_actual'], 2);
        $producto['precio_original_formateado'] = $producto['oferta'] ? "Bs. " . number_format($producto['precio'], 2) : null;
        
        // Productos relacionados (misma categoría)
        $relacionados = getProductosRelacionados($conn, $producto['categoria'], $id);
        
        successResponse([
            'producto' => $producto,
            'relacionados' => $relacionados
        ]);
    } else {
        errorResponse('Producto no encontrado', 404);
    }
    $stmt->close();
}

/**
 * Obtener productos destacados
 */
function getProductosDestacados($conn) {
    $sql = "SELECT p.id, p.nombre, p.categoria, p.variante, p.emoji, p.precio, p.stock,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN p.precio_oferta 
                ELSE p.precio 
            END as precio_actual,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN ROUND(((p.precio - p.precio_oferta) / p.precio) * 100, 0)
                ELSE 0
            END as descuento
            FROM productos p
            WHERE p.activo = 1 AND p.stock > 0 AND p.destacado = 1
            ORDER BY p.vendidos DESC
            LIMIT 8";
    
    $result = $conn->query($sql);
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $row['stock_status'] = getStockStatusTienda($row['stock']);
        $row['precio_formateado'] = "Bs. " . number_format($row['precio_actual'], 2);
        $productos[] = $row;
    }
    
    successResponse(['productos' => $productos]);
}

/**
 * Obtener productos en oferta
 */
function getProductosEnOferta($conn) {
    $sql = "SELECT p.id, p.nombre, p.categoria, p.variante, p.emoji, p.precio, p.precio_oferta, p.stock,
            p.precio_oferta as precio_actual,
            ROUND(((p.precio - p.precio_oferta) / p.precio) * 100, 0) as descuento
            FROM productos p
            WHERE p.activo = 1 AND p.stock > 0 AND p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0
            ORDER BY p.vendidos DESC
            LIMIT 8";
    
    $result = $conn->query($sql);
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $row['stock_status'] = getStockStatusTienda($row['stock']);
        $row['precio_formateado'] = "Bs. " . number_format($row['precio_actual'], 2);
        $row['precio_original_formateado'] = "Bs. " . number_format($row['precio'], 2);
        $productos[] = $row;
    }
    
    successResponse(['productos' => $productos]);
}

/**
 * Obtener categorías para la tienda
 */
function getCategoriasTienda($conn) {
    $sql = "SELECT categoria, COUNT(*) as total, 
            (SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock > 0 AND categoria = p.categoria) as total_disponibles
            FROM productos p
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
 * Obtener productos relacionados
 */
function getProductosRelacionados($conn, $categoria, $excluir_id, $limit = 4) {
    $sql = "SELECT p.id, p.nombre, p.variante, p.emoji, p.precio,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN p.precio_oferta 
                ELSE p.precio 
            END as precio_actual
            FROM productos p
            WHERE p.activo = 1 AND p.stock > 0 AND p.categoria = ? AND p.id != ?
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $categoria, $excluir_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $row['precio_formateado'] = "Bs. " . number_format($row['precio_actual'], 2);
        $productos[] = $row;
    }
    $stmt->close();
    
    return $productos;
}

// ──────────────────────────────────────────────
// FUNCIONES DEL CARRITO
// ──────────────────────────────────────────────

/**
 * Obtener carrito del usuario
 */
function getCarrito($conn) {
    $usuario_id = getCurrentUser()['id'];
    
    $sql = "SELECT c.id as cart_id, c.producto_id, c.cantidad, 
            p.nombre, p.variante, p.emoji, p.stock,
            CASE 
                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                THEN p.precio_oferta 
                ELSE p.precio 
            END as precio_unitario
            FROM carrito c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ? AND c.estado = 'activo'
            ORDER BY c.fecha_agregado DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $carrito = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['precio_unitario'] * $row['cantidad'];
        $total += $subtotal;
        $row['subtotal'] = $subtotal;
        $row['subtotal_formateado'] = "Bs. " . number_format($subtotal, 2);
        $row['precio_formateado'] = "Bs. " . number_format($row['precio_unitario'], 2);
        $carrito[] = $row;
    }
    $stmt->close();
    
    successResponse([
        'carrito' => $carrito,
        'total' => $total,
        'total_formateado' => "Bs. " . number_format($total, 2),
        'cantidad_items' => count($carrito)
    ]);
}

/**
 * Agregar producto al carrito
 */
function agregarAlCarrito($conn) {
    if (!isAuthenticated()) {
        errorResponse('Debes iniciar sesión para agregar al carrito', 401);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['producto_id']) || empty($data['cantidad'])) {
        errorResponse('Producto y cantidad son requeridos');
        return;
    }
    
    $usuario_id = getCurrentUser()['id'];
    $producto_id = intval($data['producto_id']);
    $cantidad = intval($data['cantidad']);
    
    if ($cantidad <= 0) {
        errorResponse('La cantidad debe ser mayor a 0');
        return;
    }
    
    // Verificar stock disponible
    $stmt = $conn->prepare("SELECT stock, nombre FROM productos WHERE id = ? AND activo = 1");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    if (!$producto) {
        errorResponse('Producto no disponible', 404);
        return;
    }
    
    if ($producto['stock'] < $cantidad) {
        errorResponse('Stock insuficiente. Disponible: ' . $producto['stock']);
        return;
    }
    
    // Verificar si ya existe en el carrito
    $stmt = $conn->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ? AND estado = 'activo'");
    $stmt->bind_param("ii", $usuario_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existente = $result->fetch_assoc();
    $stmt->close();
    
    if ($existente) {
        // Actualizar cantidad
        $nueva_cantidad = $existente['cantidad'] + $cantidad;
        $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva_cantidad, $existente['id']);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Cantidad actualizada en el carrito";
    } else {
        // Agregar nuevo item
        $stmt = $conn->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $usuario_id, $producto_id, $cantidad);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Producto agregado al carrito";
    }
    
    systemLog('Producto agregado al carrito', [
        'usuario_id' => $usuario_id,
        'producto' => $producto['nombre'],
        'cantidad' => $cantidad
    ]);
    
    successResponse([], $mensaje);
}

/**
 * Actualizar cantidad en carrito
 */
function actualizarCarrito($conn) {
    if (!isAuthenticated()) {
        errorResponse('No autorizado', 401);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['cart_id']) || !isset($data['cantidad'])) {
        errorResponse('ID del carrito y cantidad son requeridos');
        return;
    }
    
    $usuario_id = getCurrentUser()['id'];
    $cart_id = intval($data['cart_id']);
    $cantidad = intval($data['cantidad']);
    
    if ($cantidad < 0) {
        errorResponse('Cantidad no válida');
        return;
    }
    
    if ($cantidad == 0) {
        // Eliminar del carrito
        eliminarDelCarritoById($conn, $cart_id, $usuario_id);
        successResponse([], 'Producto eliminado del carrito');
        return;
    }
    
    // Verificar stock
    $stmt = $conn->prepare("SELECT c.producto_id, p.stock, p.nombre 
                            FROM carrito c 
                            JOIN productos p ON c.producto_id = p.id 
                            WHERE c.id = ? AND c.usuario_id = ? AND c.estado = 'activo'");
    $stmt->bind_param("ii", $cart_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if (!$item) {
        errorResponse('Item no encontrado en el carrito', 404);
        return;
    }
    
    if ($item['stock'] < $cantidad) {
        errorResponse('Stock insuficiente. Disponible: ' . $item['stock']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("iii", $cantidad, $cart_id, $usuario_id);
    $stmt->execute();
    $stmt->close();
    
    successResponse([], 'Cantidad actualizada');
}

/**
 * Eliminar producto del carrito
 */
function eliminarDelCarrito($conn) {
    if (!isAuthenticated()) {
        errorResponse('No autorizado', 401);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['cart_id'])) {
        errorResponse('ID del carrito requerido');
        return;
    }
    
    $usuario_id = getCurrentUser()['id'];
    eliminarDelCarritoById($conn, $data['cart_id'], $usuario_id);
    
    successResponse([], 'Producto eliminado del carrito');
}

function eliminarDelCarritoById($conn, $cart_id, $usuario_id) {
    $stmt = $conn->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ? AND estado = 'activo'");
    $stmt->bind_param("ii", $cart_id, $usuario_id);
    $stmt->execute();
    $stmt->close();
}

// ──────────────────────────────────────────────
// FUNCIONES DE PEDIDOS Y CHECKOUT
// ──────────────────────────────────────────────

/**
 * Procesar checkout (crear pedido)
 */
function procesarCheckout($conn) {
    if (!isAuthenticated()) {
        errorResponse('Debes iniciar sesión para realizar el pedido', 401);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $usuario_id = getCurrentUser()['id'];
    
    // Validar datos del checkout
    if (empty($data['metodo_pago'])) {
        errorResponse('Método de pago requerido');
        return;
    }
    
    if (empty($data['direccion_envio'])) {
        errorResponse('Dirección de envío requerida');
        return;
    }
    
    // Obtener carrito
    $carrito = [];
    $stmt = $conn->prepare("SELECT c.id as cart_id, c.producto_id, c.cantidad, 
                            p.nombre, p.variante, p.precio,
                            CASE 
                                WHEN p.oferta = 1 AND p.precio_oferta IS NOT NULL AND p.precio_oferta > 0 
                                THEN p.precio_oferta 
                                ELSE p.precio 
                            END as precio_unitario,
                            p.stock
                            FROM carrito c
                            JOIN productos p ON c.producto_id = p.id
                            WHERE c.usuario_id = ? AND c.estado = 'activo'");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $subtotal = 0;
    while ($row = $result->fetch_assoc()) {
        // Verificar stock nuevamente
        if ($row['stock'] < $row['cantidad']) {
            errorResponse("Stock insuficiente para {$row['nombre']}. Disponible: {$row['stock']}");
            return;
        }
        $items[] = $row;
        $subtotal += $row['precio_unitario'] * $row['cantidad'];
    }
    $stmt->close();
    
    if (empty($items)) {
        errorResponse('El carrito está vacío');
        return;
    }
    
    // Calcular totales
    $envio = floatval($data['envio'] ?? 0);
    $descuento = 0;
    
    // Aplicar cupón si existe
    if (!empty($data['cupon'])) {
        $cupon_info = validarCuponInterno($conn, $data['cupon'], $subtotal);
        if ($cupon_info['valido']) {
            $descuento = $cupon_info['descuento'];
        }
    }
    
    $total = $subtotal + $envio - $descuento;
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Crear pedido
        $estado = 'pendiente';
        $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, subtotal, envio, descuento, total, metodo_pago, direccion_envio, estado, notas) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idddssss", 
            $usuario_id, $subtotal, $envio, $descuento, $total, 
            $data['metodo_pago'], $data['direccion_envio'], $estado, 
            $data['notas'] ?? ''
        );
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        $stmt->close();
        
        // Agregar items al pedido y actualizar stock
        foreach ($items as $item) {
            // Insertar detalle del pedido
            $stmt = $conn->prepare("INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $pedido_id, $item['producto_id'], $item['cantidad'], $item['precio_unitario']);
            $stmt->execute();
            $stmt->close();
            
            // Actualizar stock del producto
            $nuevo_stock = $item['stock'] - $item['cantidad'];
            $stmt = $conn->prepare("UPDATE productos SET stock = ?, vendidos = COALESCE(vendidos, 0) + ? WHERE id = ?");
            $stmt->bind_param("iii", $nuevo_stock, $item['cantidad'], $item['producto_id']);
            $stmt->execute();
            $stmt->close();
            
            // Registrar movimiento de inventario
            $stmt = $conn->prepare("INSERT INTO inventario_movimientos (producto_id, tipo, cantidad, motivo, usuario_id) 
                                    VALUES (?, 'salida', ?, 'Venta en tienda - Pedido #' || ?, ?)");
            $stmt->bind_param("iiii", $item['producto_id'], $item['cantidad'], $pedido_id, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Vaciar carrito
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ? AND estado = 'activo'");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();
        
        // Agregar puntos al cliente (1 punto por cada 10 Bs)
        $puntos = floor($total / 10);
        if ($puntos > 0) {
            $stmt = $conn->prepare("UPDATE usuarios SET puntos = COALESCE(puntos, 0) + ? WHERE id = ?");
            $stmt->bind_param("ii", $puntos, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        
        systemLog('Pedido creado', [
            'pedido_id' => $pedido_id,
            'usuario_id' => $usuario_id,
            'total' => $total,
            'items' => count($items)
        ]);
        
        successResponse([
            'pedido_id' => $pedido_id,
            'total' => $total,
            'total_formateado' => "Bs. " . number_format($total, 2),
            'puntos_ganados' => $puntos
        ], 'Pedido realizado exitosamente');
        
    } catch (Exception $e) {
        $conn->rollback();
        errorResponse('Error al procesar el pedido: ' . $e->getMessage());
    }
}

/**
 * Obtener pedidos del cliente
 */
function getPedidosCliente($conn) {
    if (!isAuthenticated()) {
        errorResponse('No autorizado', 401);
        return;
    }
    
    $usuario_id = getCurrentUser()['id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    $sql = "SELECT p.*,
            (SELECT COUNT(*) FROM pedido_items WHERE pedido_id = p.id) as total_items
            FROM pedidos p
            WHERE p.usuario_id = ?
            ORDER BY p.fecha_creacion DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_formateado'] = "Bs. " . number_format($row['total'], 2);
        $row['estado_badge'] = getEstadoPedidoBadge($row['estado']);
        $pedidos[] = $row;
    }
    $stmt->close();
    
    successResponse(['pedidos' => $pedidos]);
}

/**
 * Obtener detalle de un pedido específico
 */
function getPedidoDetalle($conn, $pedido_id) {
    if (!isAuthenticated()) {
        errorResponse('No autorizado', 401);
        return;
    }
    
    $usuario_id = getCurrentUser()['id'];
    $rol = getCurrentUser()['rol'];
    
    // Verificar que el pedido pertenezca al usuario o sea admin
    if ($rol == 'admin') {
        $sql = "SELECT p.* FROM pedidos p WHERE p.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pedido_id);
    } else {
        $sql = "SELECT p.* FROM pedidos p WHERE p.id = ? AND p.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $pedido_id, $usuario_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido = $result->fetch_assoc();
    $stmt->close();
    
    if (!$pedido) {
        errorResponse('Pedido no encontrado', 404);
        return;
    }
    
    // Obtener items del pedido
    $sql = "SELECT pi.*, p.nombre, p.variante, p.emoji
            FROM pedido_items pi
            JOIN productos p ON pi.producto_id = p.id
            WHERE pi.pedido_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['subtotal'] = $row['precio_unitario'] * $row['cantidad'];
        $row['precio_formateado'] = "Bs. " . number_format($row['precio_unitario'], 2);
        $row['subtotal_formateado'] = "Bs. " . number_format($row['subtotal'], 2);
        $items[] = $row;
    }
    $stmt->close();
    
    $pedido['total_formateado'] = "Bs. " . number_format($pedido['total'], 2);
    $pedido['subtotal_formateado'] = "Bs. " . number_format($pedido['subtotal'], 2);
    $pedido['envio_formateado'] = "Bs. " . number_format($pedido['envio'], 2);
    $pedido['descuento_formateado'] = "Bs. " . number_format($pedido['descuento'], 2);
    $pedido['estado_badge'] = getEstadoPedidoBadge($pedido['estado']);
    
    successResponse([
        'pedido' => $pedido,
        'items' => $items
    ]);
}

/**
 * Validar cupón de descuento
 */
function validarCupon($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['codigo'])) {
        errorResponse('Código de cupón requerido');
        return;
    }
    
    $subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0;
    $resultado = validarCuponInterno($conn, $data['codigo'], $subtotal);
    
    if ($resultado['valido']) {
        successResponse([
            'cupon' => $resultado['cupon'],
            'descuento' => $resultado['descuento'],
            'descuento_formateado' => "Bs. " . number_format($resultado['descuento'], 2)
        ], $resultado['mensaje']);
    } else {
        errorResponse($resultado['mensaje'], 400);
    }
}

function validarCuponInterno($conn, $codigo, $subtotal) {
    $sql = "SELECT * FROM cupones WHERE codigo = ? AND activo = 1 
            AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) 
            AND (fecha_fin IS NULL OR fecha_fin >= NOW())
            AND (usos_limite IS NULL OR usos_actuales < usos_limite)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $cupon = $result->fetch_assoc();
    $stmt->close();
    
    if (!$cupon) {
        return ['valido' => false, 'mensaje' => 'Cupón inválido o expirado'];
    }
    
    // Verificar monto mínimo
    if ($cupon['monto_minimo'] > 0 && $subtotal < $cupon['monto_minimo']) {
        return ['valido' => false, 'mensaje' => "El cupón requiere una compra mínima de Bs. " . number_format($cupon['monto_minimo'], 2)];
    }
    
    // Calcular descuento
    $descuento = 0;
    if ($cupon['tipo'] == 'porcentaje') {
        $descuento = $subtotal * ($cupon['valor'] / 100);
        if ($cupon['descuento_maximo'] > 0 && $descuento > $cupon['descuento_maximo']) {
            $descuento = $cupon['descuento_maximo'];
        }
    } else {
        $descuento = $cupon['valor'];
    }
    
    return [
        'valido' => true,
        'mensaje' => "Cupón aplicado: " . ($cupon['tipo'] == 'porcentaje' ? $cupon['valor'] . '%' : 'Bs. ' . $cupon['valor']),
        'descuento' => $descuento,
        'cupon' => $cupon
    ];
}

// ──────────────────────────────────────────────
// FUNCIONES AUXILIARES
// ──────────────────────────────────────────────

/**
 * Obtener estado del stock para mostrar en tienda
 */
function getStockStatusTienda($stock) {
    if ($stock <= 0) {
        return ['texto' => 'Agotado', 'clase' => 'badge-red', 'disponible' => false];
    } elseif ($stock <= 5) {
        return ['texto' => "Últimas {$stock} unidades", 'clase' => 'badge-orange', 'disponible' => true];
    } else {
        return ['texto' => 'En stock', 'clase' => 'badge-green', 'disponible' => true];
    }
}

/**
 * Obtener badge del estado del pedido
 */
function getEstadoPedidoBadge($estado) {
    $badges = [
        'pendiente' => '<span class="badge badge-gray">⏳ Pendiente</span>',
        'confirmado' => '<span class="badge badge-blue">✓ Confirmado</span>',
        'preparando' => '<span class="badge badge-orange">📦 Preparando</span>',
        'enviado' => '<span class="badge badge-teal">🚚 Enviado</span>',
        'entregado' => '<span class="badge badge-green">✅ Entregado</span>',
        'cancelado' => '<span class="badge badge-red">❌ Cancelado</span>'
    ];
    return $badges[$estado] ?? '<span class="badge badge-gray">' . $estado . '</span>';
}

// Endpoint adicional: Actualizar estado de pedido (solo admin)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'estado_pedido') {
    if (!isAuthenticated() || !hasRole('admin')) {
        errorResponse('No autorizado', 401);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['pedido_id']) || empty($data['estado'])) {
        errorResponse('Pedido ID y estado requeridos');
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $data['estado'], $data['pedido_id']);
    
    if ($stmt->execute()) {
        systemLog('Estado de pedido actualizado', [
            'pedido_id' => $data['pedido_id'],
            'nuevo_estado' => $data['estado']
        ]);
        successResponse([], 'Estado del pedido actualizado');
    } else {
        errorResponse('Error al actualizar estado');
    }
    $stmt->close();
    exit;
}

// Endpoint adicional: Obtener todos los pedidos (admin)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['all_pedidos']) && hasRole('admin')) {
    $sql = "SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email,
            (SELECT COUNT(*) FROM pedido_items WHERE pedido_id = p.id) as total_items
            FROM pedidos p
            JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.fecha_creacion DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_formateado'] = "Bs. " . number_format($row['total'], 2);
        $row['estado_badge'] = getEstadoPedidoBadge($row['estado']);
        $pedidos[] = $row;
    }
    
    successResponse(['pedidos' => $pedidos]);
    exit;
}
?>