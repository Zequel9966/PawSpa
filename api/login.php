<?php
require_once 'config.php';

// ============================================
// DETECTAR SI ESTAMOS EN LOCALHOST
// ============================================
function isLocalhost() {
    $localhosts = ['127.0.0.1', '::1', 'localhost'];
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', $localhosts);
}

// Si estamos en localhost, omitir verificación de captcha
if (isLocalhost()) {
    define('SKIP_CAPTCHA', true);
} else {
    define('SKIP_CAPTCHA', false);
}

// ============================================
// FUNCIÓN PARA REGISTRAR LOGS
// ============================================
function registrarLogLogin($email, $success, $error = null, $captcha_valid = null) {
    $logFile = __DIR__ . '/../logs/login.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $fecha = date('Y-m-d H:i:s');
    $status = $success ? 'EXITO' : 'FALLO';
    $captchaStatus = $captcha_valid === true ? 'CAPTCHA_OK' : ($captcha_valid === false ? 'CAPTCHA_ERROR' : 'SIN_CAPTCHA');
    
    $logEntry = "[{$fecha}] {$status} | IP: {$ip} | Email: {$email} | {$captchaStatus}";
    if ($error) {
        $logEntry .= " | Error: {$error}";
    }
    $logEntry .= " | UA: {$userAgent}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// ============================================
// FUNCIÓN PARA OBTENER INTENTOS RESTANTES
// ============================================
function getIntentosRestantes($conn, $email, $ip) {
    $limite = 5;
    $ventana = 15;
    
    $sql = "SELECT COUNT(*) as intentos FROM login_attempts WHERE (email = ? OR ip = ?) 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $email, $ip, $ventana);
    $stmt->execute();
    $result = $stmt->get_result();
    $intentos = $result->fetch_assoc()['intentos'];
    $stmt->close();
    
    return max(0, $limite - $intentos);
}

// ============================================
// FUNCIÓN PARA VERIFICAR INTENTOS FALLIDOS
// ============================================
function verificarIntentosFallidos($conn, $email, $ip) {
    $limite = 5;
    $ventana = 15;
    
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100),
        ip VARCHAR(45),
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $cleanSql = "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    $cleanStmt = $conn->prepare($cleanSql);
    $cleanStmt->bind_param("i", $ventana);
    $cleanStmt->execute();
    $cleanStmt->close();
    
    $sql = "SELECT COUNT(*) as intentos FROM login_attempts WHERE (email = ? OR ip = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $intentos = $result->fetch_assoc()['intentos'];
    $stmt->close();
    
    return $intentos < $limite;
}

function registrarIntentoFallido($conn, $email, $ip) {
    $sql = "INSERT INTO login_attempts (email, ip) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $stmt->close();
}

function limpiarIntentosFallidos($conn, $email, $ip) {
    $sql = "DELETE FROM login_attempts WHERE email = ? OR ip = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// PROCESAR SOLICITUD DE LOGIN
// ============================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? '';
$captcha_token = $data['captcha_token'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// ============================================
// 1. VALIDAR CAMPOS REQUERIDOS
// ============================================
if (empty($email) || empty($password)) {
    registrarLogLogin($email, false, 'Campos vacíos', null);
    echo json_encode(['success' => false, 'error' => 'Por favor ingresa email y contraseña']);
    exit;
}

// ============================================
// 2. VERIFICAR CAPTCHA (omitido en localhost)
// ============================================
if (!SKIP_CAPTCHA && empty($captcha_token)) {
    registrarLogLogin($email, false, 'Captcha no proporcionado', false);
    echo json_encode(['success' => false, 'error' => 'Por favor completa el reCAPTCHA']);
    exit;
}

// ============================================
// 3. VERIFICAR INTENTOS FALLIDOS
// ============================================
if (!verificarIntentosFallidos($conn, $email, $ip)) {
    registrarLogLogin($email, false, 'Demasiados intentos fallidos', true);
    echo json_encode([
        'success' => false, 
        'error' => 'Demasiados intentos fallidos. Espera 15 minutos para intentar nuevamente.',
        'bloqueado' => true,
        'intentos_restantes' => 0
    ]);
    exit;
}

// ============================================
// 4. VERIFICAR CREDENCIALES (INCLUYE verified)
// ============================================
$stmt = $conn->prepare("SELECT id, nombre, email, rol, activo, verified FROM usuarios WHERE email = ? AND password = MD5(?) AND rol = ?");
$stmt->bind_param("sss", $email, $password, $role);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // Verificar si la cuenta está activa
    if (isset($user['activo']) && $user['activo'] == 0) {
        registrarLogLogin($email, false, 'Cuenta desactivada', true);
        echo json_encode(['success' => false, 'error' => 'Tu cuenta está desactivada. Contacta al administrador.']);
        exit;
    }
    
    // ============================================
    // VERIFICAR SI EL CORREO ESTÁ VALIDADO
    // ============================================
    if (isset($user['verified']) && $user['verified'] == 0) {
        registrarLogLogin($email, false, 'Cuenta no verificada', true);
        echo json_encode([
            'success' => false, 
            'error' => 'Por favor verifica tu cuenta con el código de 6 dígitos que enviamos a tu correo electrónico.'
        ]);
        exit;
    }
    
    // ============================================
    // LOGIN EXITOSO
    // ============================================
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nombre' => $user['nombre'],
        'email' => $user['email'],
        'rol' => $user['rol']
    ];
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Guardar log en BD
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmtLog = $conn->prepare("INSERT INTO logs (tipo, accion, detalle, usuario_id, usuario_nombre, usuario_rol, ip, user_agent) 
                VALUES ('login', 'Inicio de sesión', 'Login exitoso', ?, ?, ?, ?, ?)");
    $stmtLog->bind_param("issss", $user['id'], $user['nombre'], $user['rol'], $ip, $userAgent);
    $stmtLog->execute();
    $stmtLog->close();

    registrarLogLogin($email, true, null, true);
    limpiarIntentosFallidos($conn, $email, $ip);
    
    // Calcular intentos restantes (para mostrar en frontend)
    $intentos_restantes = getIntentosRestantes($conn, $email, $ip);
    
    echo json_encode([
        'success' => true,
        'user' => $_SESSION['user'],
        'message' => 'Bienvenido ' . $user['nombre'],
        'intentos_restantes' => $intentos_restantes
    ]);
    exit;
}

// ============================================
// 5. VERIFICAR SI EL USUARIO EXISTE CON OTRO ROL
// ============================================
$stmt2 = $conn->prepare("SELECT rol FROM usuarios WHERE email = ? AND activo = 1");
$stmt2->bind_param("s", $email);
$stmt2->execute();
$result2 = $stmt2->get_result();
$user_other_role = $result2->fetch_assoc();
$stmt2->close();

if ($user_other_role) {
    registrarIntentoFallido($conn, $email, $ip);
    registrarLogLogin($email, false, "Rol incorrecto. El usuario es {$user_other_role['rol']}", true);
    
    $intentos_restantes = getIntentosRestantes($conn, $email, $ip);
    
    echo json_encode([
        'success' => false,
        'error' => "El usuario existe pero con rol '{$user_other_role['rol']}'. Por favor selecciona el rol correcto.",
        'intentos_restantes' => $intentos_restantes
    ]);
    exit;
}

// ============================================
// 6. VERIFICAR CONTRASEÑA INCORRECTA
// ============================================
$stmt3 = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND activo = 1");
$stmt3->bind_param("s", $email);
$stmt3->execute();
$result3 = $stmt3->get_result();
$user_exists = $result3->fetch_assoc();
$stmt3->close();

if ($user_exists) {
    registrarIntentoFallido($conn, $email, $ip);
    registrarLogLogin($email, false, "Contraseña incorrecta", true);
    
    $intentos_restantes = getIntentosRestantes($conn, $email, $ip);
    
    echo json_encode([
        'success' => false, 
        'error' => 'Contraseña incorrecta',
        'intentos_restantes' => $intentos_restantes
    ]);
    exit;
}

// ============================================
// 7. USUARIO NO EXISTE
// ============================================
registrarIntentoFallido($conn, $email, $ip);
registrarLogLogin($email, false, "Usuario no encontrado", true);

$intentos_restantes = getIntentosRestantes($conn, $email, $ip);

echo json_encode([
    'success' => false, 
    'error' => 'Credenciales incorrectas',
    'intentos_restantes' => $intentos_restantes
]);
exit;
?>