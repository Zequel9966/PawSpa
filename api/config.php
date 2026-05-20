<?php


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'pawspa_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

$conn->set_charset('utf8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Configuración de la base de datos ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pawspa_db');

// --- Conexión a la base de datos con manejo de errores ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    $error = ['error' => 'Error de conexión a la base de datos', 'code' => $conn->connect_errno];
    if (in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
        $error['detail'] = $conn->connect_error; // Solo mostrar detalle en desarrollo
    }
    die(json_encode($error));
}

// Configurar charset
$conn->set_charset('utf8mb4'); // utf8mb4 soporta emojis

// Configurar modo SQL para evitar errores comunes
$conn->query("SET sql_mode = ''");
$conn->query("SET time_zone = '-04:00'"); // Bolivia UTC-4


function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    iniciarSesionSegura();
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Obtener usuario actual
 */
function getCurrentUser() {
    iniciarSesionSegura();
    return $_SESSION['user'] ?? null;
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function hasRole($roles) {
    if (!isAuthenticated()) return false;
    
    $userRole = $_SESSION['user']['rol'];
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    return $userRole === $roles;
}

/**
 * Responder con JSON y salir
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Responder con error
 */
function errorResponse($message, $statusCode = 400, $details = null) {
    $response = ['success' => false, 'error' => $message];
    if ($details && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
        $response['details'] = $details;
    }
    jsonResponse($response, $statusCode);
}

/**
 * Responder con éxito
 */
function successResponse($data = [], $message = 'Operación exitosa') {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Limpiar entrada de usuario
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Registrar acción en log del sistema
 */
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

// Iniciar sesión automáticamente
iniciarSesionSegura();

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// ============================================
// CONFIGURACIÓN DE RECAPTCHA
// ============================================

define('RECAPTCHA_SITE_KEY', '6LfXRtOsAAAAAGrBtHT_YXDzr_AWYal6lzdSbLlo');
define('RECAPTCHA_SECRET_KEY', '6LfXRtOsAAAAAPty-mIEWqqx424NPwUhPITndqP');
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');


// ============================================
// AGREGAR AL FINAL DEL ARCHIVO config.php
// ============================================

// Función auxiliar para respuestas de error
if (!function_exists('errorResponse')) {
    function errorResponse($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}

// Función auxiliar para respuestas de éxito
if (!function_exists('successResponse')) {
    function successResponse($data = [], $message = 'Operación exitosa') {
        $response = ['success' => true, 'message' => $message];
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        echo json_encode($response);
        exit;
    }
}

// Función para obtener el usuario actual
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}

// Función para verificar si está autenticado
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
}

// Función para verificar roles
if (!function_exists('hasRole')) {
    function hasRole($roles) {
        if (!isAuthenticated()) return false;
        $userRole = $_SESSION['user']['rol'];
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        return $userRole === $roles;
    }
}

// Función para registrar logs del sistema
if (!function_exists('systemLog')) {
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

// ============================================
// CONTROL DE SESIÓN POR INACTIVIDAD
// ============================================
// Tiempo de expiración en segundos (15 minutos = 900 segundos)
define('SESSION_TIMEOUT', 900);

function verificarActividad() {
    if (isset($_SESSION['user'])) {
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
            // Sesión expirada por inactividad
            session_unset();
            session_destroy();
            return false;
        }
        // Actualizar último tiempo de actividad
        $_SESSION['LAST_ACTIVITY'] = time();
        return true;
    }
    return false;
}

// Verificar actividad al inicio de cada petición API
verificarActividad();


// ============================================
// CONFIGURACIÓN DE CORREO SMTP REAL (GMAIL)
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);  // TLS usa 587
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'zequel65cap@gmail.com');  // ← CAMBIA POR TU CORREO
define('SMTP_PASS', 'hlbbzlqypurrlhiv');  // ← CONTRASEÑA DE APLICACIÓN GMAIL
define('SMTP_FROM', 'zequel65cap@gmail.com');
define('SMTP_FROM_NAME', 'PawSpa');

// ============================================
// VALIDACIÓN DE CORREOS PERSONALES
// ============================================
function esCorreoPersonal($email) {
    $dominiosPermitidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'icloud.com'];
    $dominio = substr(strrchr($email, "@"), 1);
    return in_array(strtolower($dominio), $dominiosPermitidos);
}

?>