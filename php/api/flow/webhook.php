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
    
    // Validar firma del webhook (método correcto - sin encoding)
    $webhookParams = ['token' => $token];
    ksort($webhookParams);
    
    // Método sin URL encoding
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
    
    $statusParamsForSignature = $statusParams; // Copia para firma
    ksort($statusParamsForSignature);
    
    // Método sin URL encoding
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
        
        // Detectar si es un pago mensual por el prefijo del commerce_order
        $isMonthlyPayment = strpos($commerceOrder, 'IPStream-Monthly-') === 0;
        
        if ($isMonthlyPayment) {
            error_log("💳 Detectado pago mensual recurrente: $commerceOrder");
            
            // Buscar en tabla monthly_payments
            $stmt = $pdo->prepare("SELECT * FROM monthly_payments WHERE commerce_order = ? LIMIT 1");
            $stmt->execute([$commerceOrder]);
            $monthlyPayment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$monthlyPayment) {
                error_log("❌ Pago mensual no encontrado: $commerceOrder");
                http_response_code(404);
                echo 'Monthly payment not found';
                exit;
            }
            
            // Verificar si ya fue procesado
            if ($monthlyPayment['status'] === 'paid') {
                error_log("⚠️ Pago mensual ya procesado: $commerceOrder");
                echo 'OK - Already processed';
                exit;
            }
            
            error_log("✅ Pago mensual encontrado (order_id: {$monthlyPayment['order_id']})");
            
            // Actualizar estado del pago mensual
            $stmt = $pdo->prepare("
                UPDATE monthly_payments 
                SET status = 'paid', 
                    flow_payment_data = ?,
                    paid_at = NOW()
                WHERE commerce_order = ?
            ");
            
            $stmt->execute([
                json_encode($paymentData),
                $commerceOrder
            ]);
            
            // Log del evento
            logPaymentEvent($pdo, $commerceOrder, 'monthly_payment_confirmed', [
                'flowStatus' => $status,
                'amount' => $amount,
                'token' => $token,
                'orderId' => $monthlyPayment['order_id']
            ]);
            
            // Obtener datos del cliente para notificación
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$monthlyPayment['order_id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                sendMonthlyPaymentNotification($order, $paymentData, $amount);
            }
            
            error_log("🚀 Pago mensual confirmado para: {$order['email']}");
            
        } else {
            error_log("🆕 Detectado pago de nueva suscripción: $commerceOrder");
            
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
            
            // Enviar email de confirmación (simulado)
            sendNotificationEmail($order, $paymentData);
            
            // Activar servicio
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'active', activated_at = NOW(), updated_at = NOW()
                WHERE commerce_order = ?
            ");
            $stmt->execute([$commerceOrder]);
            
            error_log("🚀 Servicio activado para: {$order['email']}");
        }
        
        echo 'OK';
        
    } elseif ($status === 3) {
        // Pago rechazado
        error_log("❌ Pago rechazado: $commerceOrder");
        
        if ($commerceOrder) {
            // Detectar si es un pago mensual
            $isMonthlyPayment = strpos($commerceOrder, 'IPStream-Monthly-') === 0;
            
            if ($isMonthlyPayment) {
                // Actualizar monthly_payments
                $stmt = $pdo->prepare("
                    UPDATE monthly_payments 
                    SET status = 'failed', 
                        flow_payment_data = ?
                    WHERE commerce_order = ?
                ");
                
                $stmt->execute([
                    json_encode($paymentData),
                    $commerceOrder
                ]);
                
                logPaymentEvent($pdo, $commerceOrder, 'monthly_payment_rejected', [
                    'flowStatus' => $status,
                    'token' => $token
                ]);
            } else {
                // Actualizar orders
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

// Función para enviar email (simulada)
function sendNotificationEmail($order, $paymentData) {
    try {
        // Aquí implementarías el envío real de email
        // Por ejemplo, usando PHPMailer o mail() de PHP
        
        $subject = "¡Bienvenido a IPStream! - Pago confirmado";
        $message = "
        Hola {$order['first_name']},
        
        ¡Tu pago ha sido confirmado exitosamente!
        
        Detalles:
        - Plan: {$order['plan_name']}
        - Monto: $" . number_format($order['amount'], 0, ',', '.') . " CLP
        - Proyecto: {$order['project_name']}
        - Orden: {$order['commerce_order']}
        
        Nuestro equipo te contactará pronto para activar tu servicio.
        
        ¡Gracias por confiar en IPStream!
        ";
        
        // Enviar email (descomenta cuando configures el servidor de email)
        // mail($order['email'], $subject, $message);
        
        error_log("📧 Email enviado a: {$order['email']}");
        
    } catch (Exception $e) {
        error_log('Error enviando email: ' . $e->getMessage());
    }
}

// Función para notificar pago mensual
function sendMonthlyPaymentNotification($order, $paymentData, $amount) {
    try {
        $subject = "IPStream - Pago mensual confirmado";
        $message = "
        Hola {$order['first_name']},
        
        ¡Tu pago mensual ha sido confirmado exitosamente!
        
        Detalles:
        - Plan: {$order['plan_name']}
        - Monto: $" . number_format($amount, 0, ',', '.') . " CLP
        - Proyecto: {$order['project_name']}
        - Fecha: " . date('d/m/Y H:i') . "
        
        Tu servicio continúa activo sin interrupciones.
        
        ¡Gracias por seguir confiando en IPStream!
        ";
        
        // Enviar email (descomenta cuando configures el servidor de email)
        // mail($order['email'], $subject, $message);
        
        error_log("📧 Email de pago mensual enviado a: {$order['email']}");
        
    } catch (Exception $e) {
        error_log('Error enviando email de pago mensual: ' . $e->getMessage());
    }
}
?>