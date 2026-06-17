<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? null;

if (empty($order_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de orden no válido.']);
    exit;
}

try {
    $pdo = getDatabase();

    // Get original order and plan details to find the correct price
    $stmt = $pdo->prepare("
        SELECT o.email, p.price, p.plan_name
        FROM orders o
        JOIN plans p ON o.plan_id = p.id
        WHERE o.id = ? AND o.status = 'active' AND o.billing_type = 'monthly'
    ");
    $stmt->execute([$order_id]);
    $subscription_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription_data) {
        throw new Exception('No se encontró una suscripción activa para esta orden.');
    }

    $amount = $subscription_data['price'];
    $email = $subscription_data['email'];
    $subject = 'Pago mensual para: ' . $subscription_data['plan_name'];

    // Create a new unique commerce order for this monthly payment
    $commerceOrder = 'IPSM-' . $order_id . '-' . time();

    // Insert a new record into monthly_payments
    $stmt = $pdo->prepare("
        INSERT INTO monthly_payments (order_id, commerce_order, amount, status, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$order_id, $commerceOrder, $amount]);

    // --- Flow Payment Creation ---
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    
    $params = [
        'apiKey' => FLOW_API_KEY,
        'commerceOrder' => $commerceOrder,
        'subject' => $subject,
        'currency' => 'CLP',
        'amount' => $amount,
        'email' => $email,
        'urlConfirmation' => $baseUrl . '/api/webhook-monthly.php', // Use the new webhook
        'urlReturn' => $baseUrl . '/pago-mensual-exitoso.html' // A dedicated success page
    ];

    // Create signature
    ksort($params);
    $paramString = http_build_query($params);
    $signature = hash_hmac('sha256', $paramString, FLOW_SECRET_KEY);
    $params['s'] = $signature;

    // Send to Flow
    $ch = curl_init(FLOW_API_URL . '/payment/create');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Flow API error: HTTP ' . $httpCode . ' - ' . $response);
    }

    $flowResult = json_decode($response, true);
    if (!$flowResult || !isset($flowResult['url']) || !isset($flowResult['token'])) {
        throw new Exception('Respuesta inválida de Flow: ' . $response);
    }

    // Update the monthly_payment record with the flow token
    $stmt = $pdo->prepare("UPDATE monthly_payments SET flow_token = ? WHERE commerce_order = ?");
    $stmt->execute([$flowResult['token'], $commerceOrder]);

    // Return payment URL to frontend
    echo json_encode([
        'success' => true,
        'paymentUrl' => $flowResult['url'] . '?token=' . $flowResult['token']
    ]);

} catch (Exception $e) {
    error_log('Error in create-monthly-payment.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ocurrió un error en el servidor al crear el pago.']);
}
?>