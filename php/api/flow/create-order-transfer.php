<?php
// API para crear orden de nueva suscripción con transferencia
// Archivo: php/api/flow/create-order-transfer.php

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

try {
    error_log('📝 Creando orden para transferencia...');
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validar datos requeridos
    $required = ['firstName', 'lastName', 'email', 'whatsapp', 'projectName', 'plan'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    error_log("📧 Creando orden para: {$input['email']}");
    
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
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        throw new Exception("Plan no encontrado: $planKey");
    }
    
    // Determinar el precio según el tipo de facturación
    if ($billingType === 'annual' && $plan['annual_price']) {
        $amount = $plan['annual_price'];
    } elseif ($plan['monthly_price']) {
        $amount = $plan['monthly_price'];
    } else {
        $amount = $plan['price'];
    }
    
    $fullPlanName = $plan['title'] ?: $plan['plan_name'];
    
    // Generar número de orden único
    $commerceOrder = 'IPStream-NewSub-Transfer-' . time() . '-' . rand(100, 999);
    
    // Crear orden en la base de datos
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            first_name, last_name, email, whatsapp, project_name,
            plan_type, plan_name, billing_type, amount, status,
            commerce_order, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    
    $stmt->execute([
        $input['firstName'],
        $input['lastName'],
        $input['email'],
        $input['whatsapp'],
        $input['projectName'],
        $planKey,
        $fullPlanName,
        $billingType,
        $amount,
        $commerceOrder
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    error_log("✅ Orden creada: $commerceOrder (ID: $orderId)");
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'commerce_order' => $commerceOrder,
        'plan_name' => $fullPlanName,
        'amount' => $amount
    ]);
    
} catch (Exception $e) {
    error_log('💥 Error al crear orden: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
