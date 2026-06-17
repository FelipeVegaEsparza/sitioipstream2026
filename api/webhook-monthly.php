<?php
require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

// This script handles the payment confirmation from Flow

$token = $_POST['token'] ?? null;
if (!$token) {
    http_response_code(400);
    error_log('Webhook Error: No token received.');
    exit('No token');
}

try {
    // We need to get the payment status from Flow, not just trust the webhook call
    $params = ['apiKey' => FLOW_API_KEY, 'token' => $token];
    ksort($params);
    $paramString = http_build_query($params);
    $signature = hash_hmac('sha256', $paramString, FLOW_SECRET_KEY);
    $params['s'] = $signature;

    $ch = curl_init(FLOW_API_URL . '/payment/getStatus');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Flow getStatus API error: HTTP ' . $httpCode . ' - ' . $response);
    }

    $paymentData = json_decode($response, true);
    if (!$paymentData) {
        throw new Exception('Invalid JSON response from Flow getStatus');
    }

    $commerceOrder = $paymentData['commerceOrder'] ?? null;
    $status = $paymentData['status'] ?? 0; // 2 = PAID, 3 = REJECTED

    if (!$commerceOrder) {
        throw new Exception('No commerceOrder in Flow response');
    }

    $pdo = getDatabase();
    
    // Find the corresponding monthly payment record
    $stmt = $pdo->prepare("SELECT * FROM monthly_payments WHERE commerce_order = ?");
    $stmt->execute([$commerceOrder]);
    $payment_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment_record) {
        throw new Exception("Monthly payment record not found for commerceOrder: $commerceOrder");
    }

    // If already paid, do nothing
    if ($payment_record['status'] === 'paid') {
        http_response_code(200);
        exit('OK');
    }

    $new_status = 'pending';
    if ($status === 2) {
        $new_status = 'paid';
    } elseif ($status === 3 || $status === 4) { // 3=rejected, 4=annulled
        $new_status = 'failed';
    }

    // Update the monthly_payments table
    $update_stmt = $pdo->prepare("
        UPDATE monthly_payments 
        SET status = ?, flow_payment_data = ?, paid_at = ?
        WHERE commerce_order = ?
    ");
    
    $paid_at = ($new_status === 'paid') ? date('Y-m-d H:i:s') : null;
    $update_stmt->execute([$new_status, json_encode($paymentData), $paid_at, $commerceOrder]);

    error_log("Webhook processed for monthly payment. CommerceOrder: $commerceOrder, New Status: $new_status");

    http_response_code(200);
    echo 'OK';

} catch (Exception $e) {
    error_log('Error in webhook-monthly.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Internal Server Error');
}
?>