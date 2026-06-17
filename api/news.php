<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: max-age=300');

try {
    require_once __DIR__ . '/../php/config/config.php';

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
    $stmt = $pdo->prepare("SELECT id, title, slug, excerpt, image, author, published_at FROM news WHERE is_active = 1 ORDER BY published_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $news = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $news]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
