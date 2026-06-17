<?php
// Configuración de PRODUCCIÓN - EJEMPLO
// Archivo: config/config.production.example.php
// RENOMBRAR A config.php y completar con datos reales

// Flow.cl Configuration - PRODUCCIÓN
define('FLOW_API_KEY', 'TU_API_KEY_REAL_DE_PRODUCCION');        // ← CAMBIAR
define('FLOW_SECRET_KEY', 'TU_SECRET_KEY_REAL_DE_PRODUCCION');  // ← CAMBIAR
define('FLOW_API_URL', 'https://www.flow.cl/api'); // URL de producción (NO sandbox)

// MySQL Database Configuration - PRODUCCIÓN
define('DB_HOST', 'localhost');                    // Generalmente localhost en cPanel
define('DB_USER', 'tuusuario_mysql');            // ← Usuario de cPanel MySQL
define('DB_PASS', 'tu_password_mysql');          // ← Password de cPanel MySQL
define('DB_NAME', 'tuusuario_ipstream');         // ← Nombre de BD en cPanel
define('DB_PORT', 3306);

// Site Configuration - PRODUCCIÓN
define('SITE_URL', 'https://tudominio.com');     // ← TU DOMINIO REAL
define('ADMIN_EMAIL', 'soporte@tudominio.com');  // ← TU EMAIL DE SOPORTE
define('SUPPORT_WHATSAPP', '+56921911216');      // ← TU WHATSAPP DE SOPORTE

// Configuración de errores - PRODUCCIÓN
error_reporting(E_ALL);
ini_set('display_errors', 0);  // IMPORTANTE: 0 en producción (no mostrar errores)
ini_set('log_errors', 1);      // Sí guardar logs
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Timezone
date_default_timezone_set('America/Santiago');

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// INSTRUCCIONES:
// 1. Renombrar este archivo a: config.php
// 2. Completar todas las variables marcadas con ← CAMBIAR
// 3. Obtener credenciales reales de Flow.cl (no sandbox)
// 4. Configurar base de datos de cPanel
// 5. Cambiar SITE_URL por tu dominio real
// 6. Verificar que display_errors esté en 0 para producción
?>