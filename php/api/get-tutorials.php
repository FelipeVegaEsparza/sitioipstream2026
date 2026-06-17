<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // Obtener categorías de tutoriales
    $categoriesStmt = $db->prepare("
        SELECT * FROM tutorial_categories 
        ORDER BY display_order ASC
    ");
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tutoriales activos con información de categoría
    $tutorialsStmt = $db->prepare("
        SELECT t.*, c.name as category_name, c.color as category_color, c.slug as category_slug
        FROM tutorials t
        INNER JOIN tutorial_categories c ON t.category_id = c.id
        WHERE t.is_active = 1
        ORDER BY c.display_order ASC, t.display_order ASC
    ");
    $tutorialsStmt->execute();
    $tutorials = $tutorialsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar tutoriales por categoría
    $tutorialsByCategory = [];
    foreach ($tutorials as $tutorial) {
        $catId = $tutorial['category_id'];
        if (!isset($tutorialsByCategory[$catId])) {
            $tutorialsByCategory[$catId] = [];
        }
        $tutorialsByCategory[$catId][] = $tutorial;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'tutorials' => $tutorials,
        'tutorialsByCategory' => $tutorialsByCategory
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los tutoriales: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
