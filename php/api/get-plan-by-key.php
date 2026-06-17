<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $planKey = $_GET['plan_key'] ?? '';
    
    if (empty($planKey)) {
        throw new Exception('plan_key es requerido');
    }
    
    $db = getDBConnection();
    
    // Obtener plan por plan_key
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
        FROM plans p
        LEFT JOIN plan_categories c ON p.category_id = c.id
        WHERE p.plan_key = :plan_key AND p.is_active = 1
        LIMIT 1
    ");
    $stmt->execute(['plan_key' => $planKey]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        throw new Exception('Plan no encontrado');
    }
    
    // Parsear features JSON
    if (isset($plan['features']) && is_string($plan['features'])) {
        $plan['features'] = json_decode($plan['features'], true);
    }
    
    echo json_encode([
        'success' => true,
        'plan' => $plan
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
