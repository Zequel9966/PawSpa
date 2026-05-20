<?php
require_once 'config.php';

// ============================================
// INCLUIR PHPMailer
// ============================================
$vendorPath = __DIR__ . '/../libs/vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    $vendorPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$nombre = trim($data['nombre'] ?? '');
$telefono = trim($data['telefono'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// ============================================
// 1. VALIDAR CAMPOS REQUERIDOS
// ============================================
if (empty($nombre) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Nombre, email y contraseña son requeridos']);
    exit;
}

// ============================================
// 2. VALIDAR EMAIL
// ============================================
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'El correo electrónico no es válido']);
    exit;
}

// ============================================
// 3. VALIDAR CONTRASEÑA
// ============================================
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}

// ============================================
// 4. VERIFICAR SI EL EMAIL YA EXISTE
// ============================================
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'El correo electrónico ya está registrado']);
    exit;
}
$stmt->close();

// ============================================
// 5. INSERTAR NUEVO CLIENTE
// ============================================
$hashed_password = md5($password);

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol, telefono, activo, puntos) VALUES (?, ?, ?, 'client', ?, 1, 0)");
$stmt->bind_param("ssss", $nombre, $email, $hashed_password, $telefono);

if ($stmt->execute()) {
    $usuario_id = $conn->insert_id;
    
    // Insertar en tabla clientes
    $stmt2 = $conn->prepare("INSERT INTO clientes (usuario_id, direccion, notificacion_pref) VALUES (?, '', 'whatsapp')");
    $stmt2->bind_param("i", $usuario_id);
    $stmt2->execute();
    $stmt2->close();
    
// ============================================
// 6. GENERAR TOKEN DE 6 DÍGITOS Y GUARDAR EN BD
// ============================================
$verification_code = sprintf("%06d", mt_rand(1, 999999));
$expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Guardar token en la tabla usuarios (ya existe la estructura)
$stmtToken = $conn->prepare("UPDATE usuarios SET verification_token = ?, token_expira = ?, verified = 0 WHERE id = ?");
$stmtToken->bind_param("ssi", $verification_code, $expira, $usuario_id);
$stmtToken->execute();
$stmtToken->close();

// ============================================
// 7. ENVIAR CORREO CON TOKEN (SOLO A CORREOS PERSONALES)
// ============================================
require_once 'enviar_correo_token.php';

$correoEnviado = false;
$mensajeCorreo = '';

// Verificar si el correo es personal (Gmail, Hotmail, etc.)
if (esCorreoPersonal($email)) {
    $resultado = enviarTokenPorCorreo($email, $nombre, $verification_code);
    
    if ($resultado['success']) {
        $correoEnviado = true;
        $mensajeCorreo = 'Te hemos enviado un código de verificación de 6 dígitos a tu correo.';
    } else {
        $mensajeCorreo = 'Cuenta creada, pero no se pudo enviar el correo de verificación. Contacta al administrador.';
        error_log("Error al enviar correo a {$email}: " . ($resultado['error'] ?? 'Desconocido'));
    }
} else {
    // Si no es correo personal, no enviamos token (puedes mostrar mensaje o no)
    $mensajeCorreo = 'Cuenta creada. Solo los correos personales (Gmail, Hotmail, etc.) reciben código de verificación.';
    error_log("Intento de registro con correo no personal: {$email}");
}

echo json_encode([
    'success' => true,
    'message' => '¡Cuenta creada exitosamente! ' . $mensajeCorreo,
    'email' => $email,
    'correo_enviado' => $correoEnviado
]);
    
} else {
    echo json_encode(['success' => false, 'error' => 'Error al crear la cuenta: ' . $stmt->error]);
}
$stmt->close();
?>