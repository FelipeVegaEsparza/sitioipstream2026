<?php
// API de Flow en PHP para cPanel
// Archivo: api/flow/create-payment.php

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
    error_log('🚀 Iniciando create-payment PHP...');
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    error_log('📥 Datos recibidos: ' . json_encode($input));
    
    // Validar datos requeridos
    $required = ['plan', 'firstName', 'lastName', 'email', 'whatsapp', 'projectName'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Conectar a MySQL
    $pdo = getDatabase();
    
    // Obtener el plan desde la base de datos usando plan_key
    $planKey = $input['plan'];
    $billingType = $input['billingType'] ?? 'monthly';
    
    $stmt = $pdo->prepare("
        SELECT id, plan_key, plan_name, title, monthly_price, annual_price, price 
        FROM plans 
        WHERE plan_key = ? AND is_active = 1
    ");
    $stmt->execute([$planKey]);
    $dbPlan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dbPlan) {
        throw new Exception("Plan not found: $planKey or plan is not active.");
    }
    
    // Determinar el precio según el tipo de facturación
    if ($billingType === 'annual' && $dbPlan['annual_price']) {
        $amount = $dbPlan['annual_price'];
    } elseif ($dbPlan['monthly_price']) {
        $amount = $dbPlan['monthly_price'];
    } else {
        $amount = $dbPlan['price'];
    }
    
    $planName = $dbPlan['title'] ?: $dbPlan['plan_name'];
    $subject = "IPStream - $planName";
    
    error_log("💰 Precio calculado: $amount para plan: $plan billing: $billing");
    
    // Generar número de orden único
    $commerceOrder = 'IPStream-' . time() . '-' . rand(100, 999);
    
    // Conectar a MySQL
    $pdo = getDatabase();
    
    // Guardar orden en base de datos
    error_log('💾 Guardando orden en BD...');
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            commerce_order, first_name, last_name, email, whatsapp, 
            project_name, plan_type, billing_type, plan_name, amount, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $commerceOrder,
        $input['firstName'],
        $input['lastName'],
        $input['email'],
        $input['whatsapp'],
        $input['projectName'],
        $planKey,
        $billingType,
        $planName,
        $amount
    ]);
    
    error_log("✅ Orden guardada en BD: $commerceOrder");
    
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
            'reason' => 'Email not in Flow whitelist'
        ]);
    }
    
    $params = [
        'apiKey' => FLOW_API_KEY,
        'commerceOrder' => $commerceOrder,
        'subject' => $subject,
        'currency' => 'CLP',
        'amount' => $amount,
        'email' => $flowEmail, // Usar email validado
        'urlConfirmation' => $baseUrl . '/php/api/flow/webhook.php',
        'urlReturn' => $baseUrl . '/pago-exitoso.html'
    ];
    
    error_log('🔍 Datos para Flow: ' . json_encode($params));
    
    // Generar firma HMAC-SHA256 (método correcto - sin encoding)
    $paramsForSignature = $params; // Copia para no modificar el original
    ksort($paramsForSignature);
    
    // Método 1: Sin URL encoding (confirmado que funciona)
    $queryParts = [];
    foreach ($paramsForSignature as $key => $value) {
        $queryParts[] = $key . '=' . $value;
    }
    $paramString = implode('&', $queryParts);
    
    $signature = hash_hmac('sha256', $paramString, FLOW_SECRET_KEY);
    $params['s'] = $signature;
    
    error_log('🚀 Enviando a Flow...');
    
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
    error_log('📨 Respuesta de Flow: ' . json_encode($flowResult));
    
    if (!$flowResult || !isset($flowResult['url']) || !isset($flowResult['token'])) {
        throw new Exception('Invalid Flow response: ' . $response);
    }
    
    // Log del evento
    logPaymentEvent($pdo, $commerceOrder, 'payment_sent', [
        'flowToken' => $flowResult['token'],
        'amount' => $amount,
        'customerEmail' => $input['email']
    ]);
    
    error_log("✅ Pago enviado a Flow: Token {$flowResult['token']}");
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'paymentUrl' => $flowResult['url'] . '?token=' . $flowResult['token'],
        'token' => $flowResult['token'],
        'commerceOrder' => $commerceOrder
    ]);
    
} catch (Exception $e) {
    error_log('💥 Error en create-payment: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

require_once __DIR__ . '/helpers.php';
?>