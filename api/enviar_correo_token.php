<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar PHPMailer
$vendorPath = __DIR__ . '/../libs/vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    $vendorPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
    }
}

/**
 * Envía un token de 6 dígitos por correo usando SMTP real (no Mailtrap)
 * Solo envía a correos personales (Gmail, Hotmail, Outlook, etc.)
 */
function enviarTokenPorCorreo($emailDestino, $nombre, $token, $expiraEnMinutos = 15) {
    // Verificar si es correo personal
    if (!esCorreoPersonal($emailDestino)) {
        error_log("No se envió token a correo no personal: $emailDestino");
        return [
            'success' => false, 
            'error' => 'Solo se envían códigos a correos personales (Gmail, Hotmail, Outlook, etc.)'
        ];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP para correo REAL
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->SMTPDebug  = 0;  // Cambia a 2 para depuración
        $mail->setLanguage('es');
        
        // Remitente y destinatario
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($emailDestino, $nombre);
        
        // Construir enlace de verificación
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        // Eliminar /api del path si existe
        $basePath = str_replace('/api', '', dirname($_SERVER['SCRIPT_NAME']));
        $verification_link = $protocol . $host . $basePath . "/verificar_cuenta.html?email=" . urlencode($emailDestino);
        
        // Contenido del correo con BOTÓN VERIFICAR
        $mail->isHTML(true);
        $mail->Subject = '🐾 PawSpa - Verifica tu cuenta con el código de 6 dígitos';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verifica tu cuenta - PawSpa</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Inter', Arial, sans-serif; background-color: #f4f7fb; margin: 0; padding: 20px; }
                .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #2D7A6B 0%, #1A5A4E 100%); padding: 32px 24px; text-align: center; }
                .logo { font-size: 48px; margin-bottom: 12px; }
                .header h1 { color: white; font-size: 28px; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
                .header p { color: rgba(255,255,255,0.85); font-size: 15px; margin-top: 8px; }
                .content { padding: 32px 28px; }
                .greeting { font-size: 22px; font-weight: 600; color: #1a2a3a; margin-bottom: 12px; }
                .message { color: #4a5568; line-height: 1.5; margin-bottom: 24px; font-size: 15px; }
                .code-container { background: #f0f7f4; border-radius: 16px; padding: 20px; text-align: center; margin: 24px 0; border: 1px solid #d4e8e2; }
                .code-label { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #2D7A6B; font-weight: 600; margin-bottom: 10px; }
                .code { font-family: 'Courier New', monospace; font-size: 42px; font-weight: 800; letter-spacing: 12px; color: #2D7A6B; background: white; padding: 15px 10px; border-radius: 12px; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
                .divider { text-align: center; margin: 20px 0; position: relative; }
                .divider span { background: #e2e8f0; padding: 8px 16px; border-radius: 20px; font-size: 12px; color: #64748b; }
                .btn { display: inline-block; background: linear-gradient(135deg, #2D7A6B, #3D9B8A); color: white; text-decoration: none; padding: 14px 32px; border-radius: 40px; font-weight: 600; font-size: 16px; margin: 16px 0; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(45,122,107,0.3); }
                .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(45,122,107,0.4); }
                .alert { background: #fff8e7; border-left: 4px solid #f5a623; padding: 14px 18px; border-radius: 12px; margin: 20px 0; font-size: 13px; color: #8b6914; }
                .footer { background: #f8fafc; padding: 20px 28px; text-align: center; border-top: 1px solid #e2e8f0; }
                .footer p { color: #94a3b8; font-size: 12px; margin: 5px 0; }
                .whatsapp { display: inline-flex; align-items: center; gap: 8px; color: #25D366; text-decoration: none; font-weight: 500; font-size: 13px; margin-top: 10px; }
                @media (max-width: 480px) {
                    .content { padding: 24px 20px; }
                    .code { font-size: 28px; letter-spacing: 6px; }
                    .btn { padding: 12px 24px; font-size: 14px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🐾</div>
                    <h1>PawSpa</h1>
                    <p>Cuidado y bienestar para tu mascota</p>
                </div>
                
                <div class='content'>
                    <div class='greeting'>¡Hola " . htmlspecialchars($nombre) . "! 👋</div>
                    <div class='message'>
                        Gracias por registrarte en <strong>PawSpa</strong>. Para completar tu registro y empezar a disfrutar de nuestros servicios, necesitamos verificar tu cuenta.
                    </div>
                    
                    <div class='code-container'>
                        <div class='code-label'>🔐 TU CÓDIGO DE VERIFICACIÓN</div>
                        <div class='code'>" . $token . "</div>
                        <div style='font-size: 12px; color: #666; margin-top: 12px;'>
                            ⏱️ Válido por <strong>{$expiraEnMinutos} minutos</strong>
                        </div>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='" . $verification_link . "' class='btn'>✅ Verificar mi cuenta</a>
                    </div>
                    
                    <div class='divider'>
                        <span>O ingresa manualmente</span>
                    </div>
                    
                    <div class='alert'>
                        <strong>📋 ¿Cómo verificar tu cuenta?</strong><br>
                        1. Haz clic en el botón <strong>\"Verificar mi cuenta\"</strong><br>
                        2. Ingresa el código de 6 dígitos: <strong>" . $token . "</strong><br>
                        3. ¡Listo! Ya puedes iniciar sesión en PawSpa
                    </div>
                    
                    <div class='message' style='font-size: 14px; margin-top: 16px;'>
                        <strong>💡 ¿No solicitaste este código?</strong><br>
                        Puedes ignorar este correo. Alguien pudo haber ingresado tu dirección por error.
                    </div>
                </div>
                
                <div class='footer'>
                    <p>🐾 <strong>PawSpa</strong> - Donde tu mascota es nuestra familia</p>
                    <p>📧 ¿Preguntas? <a href='mailto:hola@pawspa.com' style='color:#2D7A6B;'>hola@pawspa.com</a></p>
                    <a href='https://wa.me/59172345678' class='whatsapp'>📱 Contáctanos por WhatsApp</a>
                    <p style='margin-top: 16px; font-size: 11px;'>Este es un correo automático, por favor no responder.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Hola {$nombre},\n\nGracias por registrarte en PawSpa.\n\nTu código de verificación es: {$token}\n\nPara verificar tu cuenta:\n1. Ve a: {$verification_link}\n2. Ingresa el código: {$token}\n\nEste código expirará en {$expiraEnMinutos} minutos.\n\nSi no solicitaste este código, ignora este mensaje.\n\nSaludos,\nEquipo PawSpa\n\nhttps://wa.me/59172345678";
        
        $mail->send();
        return [
            'success' => true, 
            'message' => 'Código enviado correctamente a ' . $emailDestino
        ];
        
    } catch (Exception $e) {
        error_log("Error SMTP: " . $mail->ErrorInfo);
        return [
            'success' => false, 
            'error' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo
        ];
    }
}
?>