<?php
// Configuración principal
// Archivo: config/config.php

// Detectar entorno y cargar configuración apropiada
$isDevelopment = (
    getenv('NODE_ENV') === 'development' || 
    $_SERVER['HTTP_HOST'] === 'localhost:4321' ||
    $_SERVER['HTTP_HOST'] === 'localhost:8000' ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
);

if ($isDevelopment && file_exists(__DIR__ . '/config.development.php')) {
    // Usar configuración de desarrollo con Docker
    require_once __DIR__ . '/config.development.php';
} else {
    // Configuración de producción
    // Las variables de entorno tienen prioridad (Dokploy), valores hardcodeados como fallback (cPanel)

    // Flow.cl Configuration (Producción)
    define('FLOW_API_KEY', getenv('FLOW_API_KEY') ?: '567F8F39-E1E2-4DC4-B2C4-7FE7A39L0990');
    define('FLOW_SECRET_KEY', getenv('FLOW_SECRET_KEY') ?: '071691ba89e4fa5ae23ed4aa9149b33b63919dcd');
    define('FLOW_API_URL', getenv('FLOW_API_URL') ?: 'https://www.flow.cl/api');

    // MySQL Database Configuration (Producción)
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'ipstream_db2');
    define('DB_PASS', getenv('DB_PASSWORD') ?: '3517707aaAA@@');
    define('DB_NAME', getenv('DB_NAME') ?: 'ipstream_db');
    define('DB_PORT', getenv('DB_PORT') ?: 3306);

    // Site Configuration
    define('SITE_URL', getenv('SITE_URL') ?: 'https://ipstream.cl');
    define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'contacto@ipstream.cl');
    define('SUPPORT_WHATSAPP', getenv('SUPPORT_WHATSAPP') ?: '+56966297436');

    // Admin Credentials
    define('ADMIN_USER', getenv('ADMIN_USER') ?: 'contacto@ipstream.cl');
    define('ADMIN_PASS', getenv('ADMIN_PASS') ?: '3517707aaAA');

    // Configuración de errores (producción)
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

    // Timezone
    date_default_timezone_set('America/Santiago');

    // Headers de seguridad
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}
?>