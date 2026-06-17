<?php
// Webhook de Flow en PHP
// Archivo: api/flow/webhook.php

header('Content-Type: text/plain');

// Cargar configuración
require_once '../../config/config.php';
require_once '../../config/database.php';

try {
    error_log('🎉 Webhook de Flow recibido');
    
    // Obtener datos del webhook
    $token = $_POST['token'] ?? null;
    $signature = $_POST['s'] ?? null;
    
    if (!$token || !$signature) {
        error_log('❌ Webhook sin token o firma');
        http_response_code(400);
        echo 'Missing token or signature';
        exit;
    }
    
    error_log("🔍 Token recibido: $token");
    
    // Validar firma del webhook
    $webhookParams = ['token' => $token];
    ksort($webhookParams);
    
    $queryParts = [];
    foreach ($webhookParams as $key => $value) {
        $queryParts[] = $key . '=' . $value;
    }
    $paramString = implode('&', $queryParts);
    
    $calculatedSignature = hash_hmac('sha256', $paramString, FLOW_SECRET_KEY);
    
    if ($calculatedSignature !== $signature) {
        error_log('❌ Firma de webhook inválida');
        http_response_code(401);
        echo 'Invalid signature';
        exit;
    }
    
    // Consultar estado del pago en Flow
    $statusParams = [
        'apiKey' => FLOW_API_KEY,
        'token' => $token
    ];
    
    $statusParamsForSignature = $statusParams;
    ksort($statusParamsForSignature);
    
    $queryParts = [];
    foreach ($statusParamsForSignature as $key => $value) {
        $queryParts[] = $key . '=' . $value;
    }
    $statusParamString = implode('&', $queryParts);
    
    $statusSignature = hash_hmac('sha256', $statusParamString, FLOW_SECRET_KEY);
    $statusParams['s'] = $statusSignature;
    
    // Consultar Flow
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, FLOW_API_URL . '/payment/getStatus');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($statusParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("❌ Error consultando Flow: HTTP $httpCode");
        http_response_code(500);
        echo 'Payment query failed';
        exit;
    }
    
    $paymentData = json_decode($response, true);
    
    if (!$paymentData) {
        error_log('❌ Respuesta inválida de Flow');
        http_response_code(500);
        echo 'Invalid Flow response';
        exit;
    }
    
    $status = $paymentData['status'] ?? 0;
    $amount = $paymentData['amount'] ?? 0;
    $commerceOrder = $paymentData['commerceOrder'] ?? null;
    
    error_log("📊 Estado del pago: $status, Monto: $amount, Orden: $commerceOrder");
    
    // Conectar a base de datos
    $pdo = getDatabase();
    
    // Procesar solo pagos exitosos
    if ($status === 2) { // 2 = Pago exitoso en Flow
        error_log('🎉 Pago confirmado');
        
        if (!$commerceOrder) {
            error_log('❌ commerceOrder no encontrado en respuesta de Flow');
            http_response_code(400);
            echo 'Missing commerceOrder';
            exit;
        }
        
        // Buscar orden en base de datos
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE commerce_order = ? LIMIT 1");
        $stmt->execute([$commerceOrder]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("❌ Orden no encontrada: $commerceOrder");
            http_response_code(404);
            echo 'Order not found';
            exit;
        }
        
        error_log("✅ Orden encontrada: {$order['email']}");
        
        // Actualizar estado de la orden a pagado
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'paid', 
                flow_status = ?, 
                flow_token = ?,
                flow_payment_data = ?,
                paid_at = NOW(),
                updated_at = NOW()
            WHERE commerce_order = ?
        ");
        
        $stmt->execute([
            $status,
            $token,
            json_encode($paymentData),
            $commerceOrder
        ]);
        
        // Log del evento
        logPaymentEvent($pdo, $commerceOrder, 'payment_confirmed', [
            'flowStatus' => $status,
            'amount' => $amount,
            'token' => $token
        ]);
        
        // Activar servicio
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'active', activated_at = NOW(), updated_at = NOW()
            WHERE commerce_order = ?
        ");
        $stmt->execute([$commerceOrder]);
        
        error_log("🚀 Servicio activado para: {$order['email']}");
        
        echo 'OK';
        
    } elseif ($status === 3) {
        // Pago rechazado
        error_log("❌ Pago rechazado: $commerceOrder");
        
        if ($commerceOrder) {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'failed', 
                    flow_status = ?, 
                    flow_token = ?,
                    flow_payment_data = ?,
                    updated_at = NOW()
                WHERE commerce_order = ?
            ");
            
            $stmt->execute([
                $status,
                $token,
                json_encode($paymentData),
                $commerceOrder
            ]);
            
            logPaymentEvent($pdo, $commerceOrder, 'payment_rejected', [
                'flowStatus' => $status,
                'token' => $token
            ]);
        }
        
        echo 'Payment rejected';
        
    } else {
        // Otros estados
        error_log("ℹ️ Pago en estado: $status");
        echo 'Payment pending';
    }
    
} catch (Exception $e) {
    error_log('💥 Error en webhook Flow: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}

// Función para log de eventos
function logPaymentEvent($pdo, $commerceOrder, $eventType, $eventData) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_logs (commerce_order, event_type, event_data, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$commerceOrder, $eventType, json_encode($eventData)]);
    } catch (Exception $e) {
        error_log('Error logging event: ' . $e->getMessage());
    }
}
?>