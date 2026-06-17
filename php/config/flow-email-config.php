<?php
// Configuración temporal de emails válidos para Flow
// Archivo: config/flow-email-config.php

// Lista de emails que Flow acepta actualmente
// TEMPORAL: Hasta que Flow configure la cuenta correctamente
$FLOW_VALID_EMAILS = [
    'felipevegaesparza@gmail.com',
    'admin@ipstream.cl',
    'soporte@ipstream.cl',
    'test@ipstream.cl'
    // Agregar más emails según sea necesario
];

// Función para validar si un email es válido para Flow
function isValidFlowEmail($email) {
    global $FLOW_VALID_EMAILS;
    return in_array(strtolower($email), array_map('strtolower', $FLOW_VALID_EMAILS));
}

// Función para obtener un email válido por defecto
function getDefaultFlowEmail() {
    global $FLOW_VALID_EMAILS;
    return $FLOW_VALID_EMAILS[0]; // Retorna el primero de la lista
}

// Función para agregar un email válido
function addValidFlowEmail($email) {
    global $FLOW_VALID_EMAILS;
    if (!in_array($email, $FLOW_VALID_EMAILS)) {
        $FLOW_VALID_EMAILS[] = $email;
        return true;
    }
    return false;
}

// Configuración de comportamiento
define('FLOW_EMAIL_STRICT_MODE', true); // Cambiar a false para permitir cualquier email
define('FLOW_EMAIL_FALLBACK', 'felipevegaesparza@gmail.com'); // Email de respaldo

?>