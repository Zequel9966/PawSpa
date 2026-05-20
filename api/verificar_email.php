<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("❌ Token de verificación no válido.");
}

$stmt = $conn->prepare("SELECT id, email, nombre FROM usuarios WHERE verification_token = ? AND token_expira > NOW() AND verified = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // Marcar como verificado
    $stmt = $conn->prepare("UPDATE usuarios SET verified = 1, verification_token = NULL, token_expira = NULL WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $stmt->close();
    
    // Mostrar mensaje de éxito
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Cuenta verificada - PawSpa</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #FAF6F0; }
            .card { background: white; max-width: 500px; margin: auto; padding: 30px; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
            h1 { color: #2D7A6B; }
            .btn { background: #2D7A6B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>✅ ¡Cuenta verificada!</h1>
            <p>Hola <strong>" . htmlspecialchars($user['nombre']) . "</strong>, tu correo electrónico ha sido verificado correctamente.</p>
            <p>Ya puedes iniciar sesión en PawSpa.</p>
            <a href='/pawspa/' class='btn'>Ir al inicio de sesión</a>
        </div>
    </body>
    </html>";
} else {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error de verificación - PawSpa</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #FAF6F0; }
            .card { background: white; max-width: 500px; margin: auto; padding: 30px; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
            h1 { color: #C4532A; }
            .btn { background: #2D7A6B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>❌ Error de verificación</h1>
            <p>El enlace ha expirado o no es válido.</p>
            <p>Por favor, regístrate nuevamente o contacta al administrador.</p>
            <a href='/pawspa/' class='btn'>Volver al inicio</a>
        </div>
    </body>
    </html>";
}
?>