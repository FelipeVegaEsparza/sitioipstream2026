<?php
// API específica para pagos mensuales de clientes existentes
// Archivo: api/flow/monthly-payment.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

// Cargar configuración
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/flow-email-config.php';

try {
    error_log('🔄 Iniciando pago mensual...');
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    error_log('📥 Datos recibidos para pago mensual: ' . json_encode($input));
    
    // Validar datos requeridos para pago mensual
    $required = ['plan', 'firstName', 'lastName', 'email', 'whatsapp', 'projectName', 'billingType'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Determinar precio según plan (solo mensual para pagos recurrentes)
    $prices = [
        'radio' => ['monthly' => 29990],
        'tv' => ['monthly' => 39990]
    ];
    
    $plan = $input['plan'];
    $billing = 'monthly'; // Forzar mensual para pagos recurrentes
    
    if (!isset($prices[$plan][$billing])) {
        throw new Exception('Invalid plan for monthly payment');
    }
    
    $amount = $prices[$plan][$billing];
    $planName = ($plan === 'radio' ? 'Radio Online' : 'Radio + TV Online') . ' - Mensual';
    $subject = "IPStream - Pago Mensual - $planName";
    
    error_log("💰 Pago mensual: $amount para plan: $plan");
    
    // Generar número de orden único para pago mensual
    $commerceOrder = 'IPStream-Monthly-' . time() . '-' . rand(100, 999);
    
    // Conectar a MySQL
    $pdo = getDatabase();
    
    // Verificar si el cliente ya existe
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE email = ? AND project_name = ? AND plan_type = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$input['email'], $input['projectName'], $plan]);
    $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingOrder) {
        throw new Exception('No se encontró una suscripción activa para este cliente. Debe crear una suscripción nueva primero.');
    }
    
    error_log("👤 Cliente existente encontrado: {$existingOrder['commerce_order']} (ID: {$existingOrder['id']})");
    
    // Actualizar información del cliente si es necesario
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET first_name = ?, last_name = ?, whatsapp = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $input['firstName'],
        $input['lastName'], 
        $input['whatsapp'],
        $existingOrder['id']
    ]);
    
    // Guardar pago mensual en tabla monthly_payments
    error_log('💾 Guardando pago mensual en tabla monthly_payments...');
    $stmt = $pdo->prepare("
        INSERT INTO monthly_payments (
            order_id, commerce_order, amount, status, created_at
        ) VALUES (?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $existingOrder['id'],
        $commerceOrder,
        $amount
    ]);
    
    error_log("✅ Pago mensual guardado en monthly_payments: $commerceOrder (order_id: {$existingOrder['id']})");
    
    // Preparar datos para Flow
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'];
    
    // Manejar restricción temporal de emails en Flow
    $flowEmail = $input['email'];
    if (FLOW_EMAIL_STRICT_MODE && !isValidFlowEmail($input['email'])) {
        error_log("⚠️ Email {$input['email']} no válido para Flow, usando fallback: " . FLOW_EMAIL_FALLBACK);
        $flowEmail = FLOW_EMAIL_FALLBACK;
        
        // Log para seguimiento
        logPaymentEvent($pdo, $commerceOrder, 'email_fallback', [
            'originalEmail' => $input['email'],
            'flowEmail' => $flowEmail,
            'reason' => 'Monthly payment - Email not in Flow whitelist'
        ]);
    }
    
    $params = [
        'apiKey' => FLOW_API_KEY,
        'commerceOrder' => $commerceOrder,
        'subject' => $subject,
        'currency' => 'CLP',
        'amount' => $amount,
        'email' => $flowEmail,
        'urlConfirmation' => $baseUrl . '/php/api/flow/webhook.php',
        'urlReturn' => $baseUrl . '/pago-exitoso.html?type=monthly'
    ];
    
    error_log('🔍 Datos para Flow (pago mensual): ' . json_encode($params));
    
    // Generar firma HMAC-SHA256 (método correcto - sin encoding)
    $paramsForSignature = $params;
    ksort($paramsForSignature);
    
    // Método sin URL encoding (confirmado que funciona)
    $queryParts = [];
    foreach ($paramsForSignature as $key => $value) {
        $queryParts[] = $key . '=' . $value;
    }
    $paramString = implode('&', $queryParts);
    
    $signature = hash_hmac('sha256', $paramString, FLOW_SECRET_KEY);
    $params['s'] = $signature;
    
    error_log('🚀 Enviando pago mensual a Flow...');
    
    // Enviar a Flow
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, FLOW_API_URL . '/payment/create');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('CURL Error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        error_log("❌ Flow API Error: HTTP $httpCode - $response");
        throw new Exception('Flow API error: HTTP ' . $httpCode);
    }
    
    $flowResult = json_decode($response, true);
    error_log('📨 Respuesta de Flow (pago mensual): ' . json_encode($flowResult));
    
    if (!$flowResult || !isset($flowResult['url']) || !isset($flowResult['token'])) {
        throw new Exception('Invalid Flow response: ' . $response);
    }
    
    // Log del evento
    logPaymentEvent($pdo, $commerceOrder, 'monthly_payment_sent', [
        'flowToken' => $flowResult['token'],
        'amount' => $amount,
        'customerEmail' => $input['email'],
        'projectName' => $input['projectName'],
        'existingCustomer' => $existingOrder ? true : false
    ]);
    
    error_log("✅ Pago mensual enviado a Flow: Token {$flowResult['token']}");
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'paymentUrl' => $flowResult['url'] . '?token=' . $flowResult['token'],
        'token' => $flowResult['token'],
        'commerceOrder' => $commerceOrder,
        'paymentType' => 'monthly_renewal',
        'amount' => $amount,
        'planName' => $planName
    ]);
    
} catch (Exception $e) {
    error_log('💥 Error en pago mensual: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

require_once __DIR__ . '/helpers.php';
?>