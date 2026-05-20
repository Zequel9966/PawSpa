<?php
$file = $_GET['file'] ?? 'system';
$logPath = __DIR__ . '/../logs/' . $file . '.log';

if (file_exists($logPath)) {
    file_put_contents($logPath, "# Log limpiado el " . date('Y-m-d H:i:s') . "\n");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
