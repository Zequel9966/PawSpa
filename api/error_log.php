<?php
// Configurar PHP para guardar errores en archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

// Función para manejar errores personalizados
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/../logs/error.log';
    $errorType = '';
    switch ($errno) {
        case E_ERROR: $errorType = 'FATAL ERROR'; break;
        case E_WARNING: $errorType = 'WARNING'; break;
        case E_NOTICE: $errorType = 'NOTICE'; break;
        default: $errorType = 'UNKNOWN ERROR';
    }
    
    $logEntry = date('Y-m-d H:i:s') . " | [{$errorType}] | {$errstr} | File: {$errfile} | Line: {$errline}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return true;
}

set_error_handler('customErrorHandler');
?>