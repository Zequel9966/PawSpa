<?php

// ============================================
// CARGAR AUTOLOADER DE COMPOSER (JWT)
// ============================================

// La ruta correcta desde api/config.php hacia vendor es:
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    // Si no existe, intentar con ruta alternativa
    $vendorPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
    }
}

// ============================================
// CABECERAS
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CONEXIÓN A LA BASE DE DATOS
// ============================================

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'pawspa_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');
$conn->query("SET sql_mode = ''");
$conn->query("SET time_zone = '-04:00'");

// ============================================
// SESIÓN
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isAuthenticated() {
    iniciarSesionSegura();
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function getCurrentUser() {
    iniciarSesionSegura();
    return $_SESSION['user'] ?? null;
}

function hasRole($roles) {
    if (!isAuthenticated()) return false;
    $userRole = $_SESSION['user']['rol'];
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    return $userRole === $roles;
}

// ============================================
// FUNCIONES DE RESPUESTA
// ============================================

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function errorResponse($message, $statusCode = 400, $details = null) {
    $response = ['success' => false, 'error' => $message];
    if ($details && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
        $response['details'] = $details;
    }
    jsonResponse($response, $statusCode);
}

function successResponse($data = [], $message = 'Operación exitosa') {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ============================================
// LOGS
// ============================================

function systemLog($action, $details = null) {
    $logFile = __DIR__ . '/../logs/system.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $user = getCurrentUser();
    $userId = $user['id'] ?? 'anonymous';
    $userName = $user['nombre'] ?? 'Unknown';
    $logEntry = date('Y-m-d H:i:s') . " | [{$userId}] {$userName} | {$action}";
    if ($details) {
        $logEntry .= " | " . json_encode($details);
    }
    $logEntry .= PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $user = getCurrentUser();
    $userId = $user['id'] ?? 'anonymous';
    $logEntry = date('Y-m-d H:i:s') . " | [{$userId}] | IP: {$ip} | URI: {$uri} | ERROR: {$message}";
    if (!empty($context)) {
        $logEntry .= " | CONTEXT: " . json_encode($context);
    }
    $logEntry .= PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// ============================================
// CONTROL DE SESIÓN POR INACTIVIDAD
// ============================================

define('SESSION_TIMEOUT', 900);

function verificarActividad() {
    if (isset($_SESSION['user'])) {
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['LAST_ACTIVITY'] = time();
        return true;
    }
    return false;
}
verificarActividad();

// ============================================
// CONFIGURACIÓN DE RECAPTCHA
// ============================================

define('RECAPTCHA_SITE_KEY', '6LfXRtOsAAAAAGrBtHT_YXDzr_AWYal6lzdSbLlo');
define('RECAPTCHA_SECRET_KEY', '6LfXRtOsAAAAAPty-mIEWqqx424NPwUhPITndqP');
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');

// ============================================
// CONFIGURACIÓN DE CORREO
// ============================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'zequel65cap@gmail.com');
define('SMTP_PASS', 'hlbbzlqypurrlhiv');
define('SMTP_FROM', 'zequel65cap@gmail.com');
define('SMTP_FROM_NAME', 'PawSpa');

function esCorreoPersonal($email) {
    $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'icloud.com'];
    $dominio = substr(strrchr($email, "@"), 1);
    return in_array(strtolower($dominio), $dominiosPermitidos);
}

// ============================================
// CONFIGURACIÓN JWT
// ============================================

define('JWT_SECRET_KEY', 'Lotus&Spa_927XYZ!@#');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 3600 * 24);

function generarJWT($usuario) {
    if (!class_exists('Firebase\JWT\JWT')) {
        error_log("ERROR: Clase Firebase\JWT\JWT no encontrada");
        return null;
    }
    
    $issuedAt = time();
    $expiration = $issuedAt + JWT_EXPIRATION;
    
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expiration,
        'data' => [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol']
        ]
    ];
    
    return Firebase\JWT\JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
}

function verificarJWT($token) {
    if (!class_exists('Firebase\JWT\JWT')) {
        error_log("ERROR: Clase Firebase\JWT\JWT no encontrada");
        return null;
    }
    
    try {
        $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        return (array) $decoded->data;
    } catch (Exception $e) {
        error_log("JWT Error: " . $e->getMessage());
        return null;
    }
}

function obtenerTokenFromHeader() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return null;
}

function verificarAuthJWT() {
    $token = obtenerTokenFromHeader();
    
    if (!$token) {
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return null;
    }
    
    $usuario = verificarJWT($token);
    if ($usuario) {
        $_SESSION['user'] = $usuario;
        return $usuario;
    }
    return null;
}

function authMiddleware() {
    $usuario = verificarAuthJWT();
    
    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado', 'code' => 'UNAUTHORIZED']);
        exit;
    }
    return $usuario;
}

function adminOnlyMiddleware() {
    $usuario = authMiddleware();
    
    if ($usuario['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requieren permisos de administrador']);
        exit;
    }
    return $usuario;
}