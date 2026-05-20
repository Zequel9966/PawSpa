<?php
require_once 'enviar_correo_token.php';

$email = 'zequel65cap@gmail.com';  // ← Cambia por tu Gmail real
$nombre = 'Test';
$token = sprintf("%06d", mt_rand(1, 999999));

echo "Enviando token $token a $email...<br>";

$resultado = enviarTokenPorCorreo($email, $nombre, $token);

if ($resultado['success']) {
    echo "✅ Enviado correctamente. Revisa tu correo.";
} else {
    echo "❌ Error: " . $resultado['error'];
}
?>