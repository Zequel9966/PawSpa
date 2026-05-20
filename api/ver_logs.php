<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$format = $_GET['format'] ?? 'html';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;

// Obtener logs de la base de datos
$sql = "SELECT * FROM logs ORDER BY fecha DESC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

if ($format === 'json') {
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PawSpa - Visor de Logs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .header { background: #2d2d2d; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .log-entry { padding: 8px; border-bottom: 1px solid #333; font-size: 12px; }
        .log-entry.login { border-left: 3px solid #4ec9b0; }
        .log-entry.logout { border-left: 3px solid #888; }
        .log-entry.create { border-left: 3px solid #4ec9b0; }
        .log-entry.edit { border-left: 3px solid #dcdcaa; }
        .log-entry.delete { border-left: 3px solid #f48771; }
        .timestamp { color: #569cd6; }
        .user { color: #ce9178; }
    </style>
</head>
<body>
<div class="header">
    <h1>📋 Registro de Actividad</h1>
    <p>Total: <?php echo count($logs); ?> registros</p>
</div>
<?php foreach ($logs as $log): ?>
<div class="log-entry <?php echo $log['tipo']; ?>">
    <span class="timestamp">[<?php echo $log['fecha']; ?>]</span>
    <span class="user">[<?php echo htmlspecialchars($log['usuario_nombre']); ?>]</span>
    [<?php echo strtoupper($log['tipo']); ?>] 
    <?php echo htmlspecialchars($log['accion']); ?> — 
    <?php echo htmlspecialchars($log['detalle']); ?>
</div>
<?php endforeach; ?>
</body>
</html>