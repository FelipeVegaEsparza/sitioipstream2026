<?php
require_once 'auth.php';
require_once __DIR__ . '/../php/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

requireCsrf();

$plan_id = $_POST['plan_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$plan_id || $status === null) {
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("UPDATE plans SET is_active = ? WHERE id = ?");
    $stmt->execute([$status, $plan_id]);
    
    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
?>
