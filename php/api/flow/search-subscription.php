<?php
// API para buscar suscripción activa por email
// Archivo: php/api/flow/search-subscription.php

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
    error_log('🔍 Buscando suscripción...');
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['email'])) {
        throw new Exception('Email es requerido');
    }
    
    $email = trim($input['email']);
    error_log("📧 Buscando suscripción para: $email");
    
    // Conectar a MySQL
    $pdo = getDatabase();
    
    // Buscar suscripción activa más reciente
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.first_name,
            o.last_name,
            o.email,
            o.whatsapp,
            o.project_name,
            o.plan_type,
            o.plan_name,
            o.billing_type,
            p.monthly_price as amount,
            p.title as plan_title,
            p.description as plan_description,
            pc.name as category_name,
            o.status,
            o.created_at
        FROM orders o
        JOIN plans p ON o.plan_id = p.id
        LEFT JOIN plan_categories pc ON p.category_id = pc.id
        WHERE o.email = ? 
          AND o.status = 'active'
          AND o.billing_type = 'monthly'
          AND o.payment_type = 'new_subscription'
        ORDER BY o.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$email]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        error_log("❌ No se encontró suscripción activa para: $email");
        echo json_encode([
            'success' => false,
            'error' => 'No se encontró una suscripción activa con este email. Verifica que tu email sea correcto y que tu suscripción esté activa.'
        ]);
        exit;
    }
    
    error_log("✅ Suscripción encontrada: {$subscription['project_name']} - {$subscription['plan_name']}");
    
    // Buscar el último pago mensual
    $stmt = $pdo->prepare("
        SELECT 
            paid_at,
            amount,
            status
        FROM monthly_payments
        WHERE order_id = ? AND status = 'paid'
        ORDER BY paid_at DESC
        LIMIT 1
    ");
    $stmt->execute([$subscription['id']]);
    $lastPayment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular período actual y próximo
    $lastPaymentDate = $lastPayment ? new DateTime($lastPayment['paid_at']) : new DateTime($subscription['created_at']);
    $nextPaymentDate = clone $lastPaymentDate;
    $nextPaymentDate->modify('+1 month');
    
    // Calcular el período que está pagando ahora
    $currentPeriodStart = clone $nextPaymentDate;
    $currentPeriodEnd = clone $nextPaymentDate;
    $currentPeriodEnd->modify('+1 month');
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'subscription' => [
            'id' => $subscription['id'],
            'first_name' => $subscription['first_name'],
            'last_name' => $subscription['last_name'],
            'email' => $subscription['email'],
            'whatsapp' => $subscription['whatsapp'],
            'project_name' => $subscription['project_name'],
            'plan_type' => $subscription['plan_type'],
            'plan_name' => $subscription['plan_name'],
            'plan_title' => $subscription['plan_title'],
            'plan_description' => $subscription['plan_description'],
            'category_name' => $subscription['category_name'],
            'billing_type' => $subscription['billing_type'],
            'amount' => (int)$subscription['amount'],
            'status' => $subscription['status'],
            'last_payment_date' => $lastPayment ? $lastPayment['paid_at'] : null,
            'last_payment_amount' => $lastPayment ? (int)$lastPayment['amount'] : null,
            'current_period_start' => $currentPeriodStart->format('Y-m-d'),
            'current_period_end' => $currentPeriodEnd->format('Y-m-d'),
            'next_payment_due' => $nextPaymentDate->format('Y-m-d')
        ]
    ]);
    
} catch (Exception $e) {
    error_log('💥 Error buscando suscripción: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
