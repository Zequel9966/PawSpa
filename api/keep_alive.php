<?php
session_start();

// Actualizar el timestamp de última actividad
$_SESSION['LAST_ACTIVITY'] = time();

echo json_encode(['success' => true]);
?>