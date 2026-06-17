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
$email = $input['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Por favor, introduce un email válido.']);
    exit;
}

try {
    $pdo = getDatabase();
    
    // Find an active, monthly subscription for the given email
    $stmt = $pdo->prepare("
        SELECT o.id, o.commerce_order, o.first_name, o.last_name, p.plan_name, p.price
        FROM orders o
        JOIN plans p ON o.plan_id = p.id
        WHERE o.email = ? 
        AND o.status = 'active' 
        AND o.billing_type = 'monthly'
        ORDER BY o.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subscription) {
        echo json_encode([
            'success' => true,
            'subscription' => $subscription
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró una suscripción mensual activa para este correo.']);
    }

} catch (Exception $e) {
    error_log('Error in find_subscription.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ocurrió un error en el servidor. Por favor, inténtalo de nuevo más tarde.']);
}
?>