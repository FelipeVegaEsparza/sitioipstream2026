<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin

require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

try {
    $pdo = getDatabase();
    $stmt = $pdo->query("SELECT plan_key, price FROM plans WHERE is_active = TRUE");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $prices = [];
    foreach ($plans as $plan) {
        $prices[$plan['plan_key']] = $plan['price'];
    }

    echo json_encode([
        'success' => true,
        'prices' => $prices
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not fetch prices.'
    ]);
}
?>