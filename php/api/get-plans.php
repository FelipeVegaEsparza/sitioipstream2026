<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // Obtener categorías activas
    $categoriesStmt = $db->prepare("
        SELECT * FROM plan_categories 
        WHERE is_active = 1 
        ORDER BY display_order ASC, name ASC
    ");
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todos los planes activos con información de categoría
    $plansStmt = $db->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
        FROM plans p
        LEFT JOIN plan_categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY c.display_order ASC, p.id ASC
    ");
    $plansStmt->execute();
    $plans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parsear features JSON
    foreach ($plans as &$plan) {
        if (isset($plan['features']) && is_string($plan['features'])) {
            $plan['features'] = json_decode($plan['features'], true);
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'plans' => $plans
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los planes: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
