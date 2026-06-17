<?php
// Configuración de desarrollo con Docker
// Este archivo se usa automáticamente en desarrollo local

// Cargar variables de entorno desde .env.development
function loadEnvFile($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear línea KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover comillas si existen
            $value = trim($value, '"\'');
            
            // Establecer variable de entorno
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Cargar .env.development
$envPath = __DIR__ . '/../../.env.development';
loadEnvFile($envPath);

// Flow.cl Configuration (Sandbox para desarrollo)
define('FLOW_API_KEY', getenv('FLOW_API_KEY') ?: 'F8F39E1E-2DC4-4B2C-7FE7-A39L0990567F');
define('FLOW_SECRET_KEY', getenv('FLOW_SECRET_KEY') ?: 'c920620fe6fcae034893e3fa3c41fe7748244e65');
define('FLOW_API_URL', getenv('FLOW_API_URL') ?: 'https://sandbox.flow.cl/api');

// MySQL Database Configuration (Docker)
// Cuando PHP corre en Docker, debe conectarse al contenedor mysql
$dbHost = getenv('DB_HOST') ?: 'localhost';

// Si estamos en el contenedor PHP de Docker, usar el nombre del servicio
if (gethostname() === 'ipstream_php') {
    $dbHost = 'mysql';
}

define('DB_HOST', $dbHost);
define('DB_USER', getenv('DB_USER') ?: 'ipstream_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'ipstream_pass_2024');
define('DB_NAME', getenv('DB_NAME') ?: 'ipstream_db');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Site Configuration
define('SITE_URL', getenv('PUBLIC_SITE_URL') ?: 'http://localhost:4321');
define('ADMIN_EMAIL', 'dev@ipstream.local');
define('SUPPORT_WHATSAPP', '+56921911216');

// Admin Credentials (desarrollo)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

// Configuración de errores (modo desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Timezone
date_default_timezone_set('America/Santiago');

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN'); // Más permisivo en desarrollo
header('X-XSS-Protection: 1; mode=block');

// Debug info
if (getenv('NODE_ENV') === 'development') {
    error_log('🐳 Usando configuración de desarrollo con Docker');
    error_log('   DB Host: ' . DB_HOST);
    error_log('   DB Name: ' . DB_NAME);
    error_log('   DB User: ' . DB_USER);
    error_log('   Flow API: ' . FLOW_API_URL);
}
?>
