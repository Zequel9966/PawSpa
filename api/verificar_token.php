<?php
require_once 'config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$codigo = trim($data['codigo'] ?? '');

if (empty($email) || empty($codigo)) {
    echo json_encode(['success' => false, 'error' => 'Email y código son requeridos']);
    exit;
}

// Buscar usuario con ese email y código
$stmt = $conn->prepare("SELECT id, nombre, verification_token, token_expira, verified FROM usuarios WHERE email = ? AND verification_token = ? AND verified = 0");
$stmt->bind_param("ss", $email, $codigo);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Código incorrecto']);
    exit;
}

// Verificar si el token expiró
if (strtotime($user['token_expira']) < time()) {
    echo json_encode(['success' => false, 'error' => 'El código ha expirado. Por favor, solicita uno nuevo.']);
    exit;
}

// Marcar como verificado
$stmt = $conn->prepare("UPDATE usuarios SET verified = 1, verification_token = NULL, token_expira = NULL WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success' => true,
    'message' => '¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.'
]);
?>