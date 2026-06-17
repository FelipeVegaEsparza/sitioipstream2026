<?php
require_once 'auth.php';

$category_id = $_GET['id'] ?? null;

if (!$category_id) {
    header('Location: categorias.php');
    exit;
}

$pdo = getDatabase();

try {
    // Verificar que no tenga planes asociados
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE category_id = ?");
    $stmt_check->execute([$category_id]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $_SESSION['error'] = "No se puede eliminar la categoría porque tiene planes asociados";
    } else {
        $stmt = $pdo->prepare("DELETE FROM plan_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $_SESSION['success'] = "Categoría eliminada exitosamente";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
}

header('Location: categorias.php');
exit;
?>
