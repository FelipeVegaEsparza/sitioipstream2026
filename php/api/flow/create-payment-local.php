<?php
// API simplificada para desarrollo local
// Archivo: api/flow/create-payment-local.php

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Mostrar errores para debug local
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en output, solo en logs
ini_set('log_errors', 1);

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Log para debug
    error_log('🚀 API Local: Iniciando create-payment-local.php');
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    error_log('📥 Datos recibidos: ' . json_encode($input));
    
    // Validar datos requeridos
    $required = ['plan', 'firstName', 'lastName', 'email', 'whatsapp', 'projectName', 'billingType'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Determinar precio según plan
    $prices = [
        'radio' => [
            'monthly' => 29990, 
            'annual' => 269990
        ],
        'tv' => [
            'monthly' => 39990, 
            'annual' => 359990
        ]
    ];
    
    $plan = $input['plan'];
    $billing = $input['billingType'];
    
    if (!isset($prices[$plan][$billing])) {
        throw new Exception('Invalid plan or billing type');
    }
    
    $amount = $prices[$plan][$billing];
    $planName = ($plan === 'radio' ? 'Radio Online' : 'Radio + TV Online') . 
                ' - ' . ($billing === 'annual' ? 'Anual' : 'Mensual');
    
    error_log("💰 Precio calculado: $amount para plan: $plan billing: $billing");
    
    // Generar número de orden único
    $commerceOrder = 'IPStream-Local-' . time() . '-' . rand(100, 999);
    
    // En desarrollo local, simular respuesta exitosa de Flow
    $mockFlowResponse = [
        'url' => 'https://sandbox.flow.cl/payment/pay',
        'token' => 'mock-token-' . time(),
        'flowOrder' => 'FLOW-' . rand(100000, 999999)
    ];
    
    error_log("✅ Simulando pago exitoso: Token {$mockFlowResponse['token']}");
    
    // Respuesta exitosa simulada
    echo json_encode([
        'success' => true,
        'paymentUrl' => $mockFlowResponse['url'] . '?token=' . $mockFlowResponse['token'],
        'token' => $mockFlowResponse['token'],
        'commerceOrder' => $commerceOrder,
        'message' => 'Pago simulado para desarrollo local',
        'debug' => [
            'plan' => $planName,
            'amount' => $amount,
            'customer' => $input['firstName'] . ' ' . $input['lastName'],
            'project' => $input['projectName']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('💥 Error en create-payment-local: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => 'Error en desarrollo local - revisa los logs'
    ]);
}
?>