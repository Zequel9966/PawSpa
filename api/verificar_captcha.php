<?php
require_once 'config.php';

define('RECAPTCHA_SECRET_KEY', '6LfXRtOsAAAAAPty-mIEWqqx424NPwUhPITndqP');
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');

function verificarRecaptcha($token) {
    if (empty($token)) {
        return ['success' => false, 'error' => 'missing-input-response'];
    }
    
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents(RECAPTCHA_VERIFY_URL, false, $context);
    
    if ($result === false) {
        return ['success' => false, 'error' => 'connection_failed'];
    }
    
    return json_decode($result, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'Token requerido']);
    exit;
}

$verification = verificarRecaptcha($token);

if ($verification['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Captcha verificado correctamente',
        'score' => $verification['score'] ?? null
    ]);
} else {
    $error = $verification['error-codes'][0] ?? 'unknown';
    echo json_encode([
        'success' => false,
        'error' => 'Error de verificación: ' . $error
    ]);
}
?>